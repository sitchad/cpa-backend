<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FraudLog;
use App\Models\Postback;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function stats()
    {
        return response()->json(['success' => true, 'data' => [
            'users'       => [
                'total' => User::where('role','user')->count(), 
                'active' => User::where('role','user')->where('status','active')->count(), 
                'banned' => User::where('role','user')->where('status','banned')->count(), 
                'today' => User::where('role','user')->whereDate('created_at',today())->count()
            ],
            'conversions' => [
                'total' => Postback::where('status','approved')->count(), 
                'today' => Postback::where('status','approved')->whereDate('created_at',today())->count(), 
                'revenue' => Postback::where('status','approved')->sum('payout')
            ],
            'withdrawals' => [
                'pending' => Withdrawal::where('status','pending')->count(), 
                'total_paid' => Withdrawal::where('status','completed')->sum('amount')
            ],
            'fraud'       => [
                'logs_today' => FraudLog::whereDate('created_at',today())->count(), 
                'blocked' => FraudLog::where('action_taken','blocked')->count()
            ],
        ]]);
    }

    public function users(Request $request)
    {
        $users = User::where('role','user')
                    ->with('wallet')
                    ->when($request->search, fn($q) => $q->where('email','LIKE',"%{$request->search}%"))
                    ->orderByDesc('created_at')
                    ->paginate(25);
                    
        return response()->json([
            'success' => true, 
            'data' => $users->through(fn($u) => [
                'id' => $u->id, 
                'name' => $u->name, 
                'email' => $u->email, 
                'status' => $u->status, 
                'balance' => (float)($u->wallet?->balance ?? 0), // ✅ Déjà correct
                'has_wallet' => $u->wallet !== null, // ✅ Ajouté
                'created_at' => $u->created_at?->toISOString()
            ])
        ]);
    }

    public function userDetail(int $id)
    {
        $user = User::with(['wallet','withdrawals','clicks'])->findOrFail($id);
        
        // ✅ Protéger l'accès au wallet
        $walletData = null;
        if ($user->wallet) {
            $walletData = [
                'balance' => (float)$user->wallet->balance,
                'pending' => (float)$user->wallet->pending,
                'total_earned' => (float)$user->wallet->total_earned,
                'total_withdrawn' => (float)$user->wallet->total_withdrawn,
                'currency' => $user->wallet->currency
            ];
        }
        
        return response()->json([
            'success' => true, 
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'wallet' => $walletData, // ✅ Peut être null
                    'withdrawals' => $user->withdrawals,
                    'clicks' => $user->clicks
                ],
                'conversions' => Postback::where('user_id',$id)->where('status','approved')->count(),
                'fraud_logs' => FraudLog::where('user_id',$id)->get()
            ]
        ]);
    }

    public function banUser(Request $request, int $id)
    {
        $v = Validator::make($request->all(), ['reason' => 'required|string|min:5']);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        
        $user = User::findOrFail($id);
        $user->update(['status' => 'banned', 'ban_reason' => $request->reason]);
        $user->tokens()->delete();
        
        return response()->json(['success' => true, 'message' => "User #{$id} banned."]);
    }

    public function withdrawals(Request $request)
    {
        $w = Withdrawal::with('user')
                       ->when($request->status, fn($q) => $q->where('status',$request->status))
                       ->orderByDesc('created_at')
                       ->paginate(25);
                       
        return response()->json([
            'success' => true, 
            'data' => $w->through(fn($x) => [
                'id' => $x->id,
                'user' => [
                    'id' => $x->user_id,
                    'name' => $x->user->name,
                    'email' => $x->user->email
                ],
                'amount' => (float)$x->amount,
                'wallet_address' => $x->wallet_address,
                'status' => $x->status,
                'created_at' => $x->created_at?->toISOString()
            ])
        ]);
    }

    public function approveWithdrawal(Request $request, int $id)
    {
        $v = Validator::make($request->all(), ['tx_hash' => 'required|string|min:10']);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        
        $withdrawal = Withdrawal::where('status','pending')->findOrFail($id);
        $withdrawal->update([
            'status' => 'completed',
            'tx_hash' => $request->tx_hash,
            'processed_by' => $request->user()->id,
            'processed_at' => now()
        ]);
        
        return response()->json(['success' => true, 'message' => "Withdrawal #{$id} approved."]);
    }

    public function rejectWithdrawal(Request $request, int $id)
    {
        $v = Validator::make($request->all(), ['reason' => 'required|string|min:5']);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        
        $withdrawal = Withdrawal::whereIn('status',['pending','processing'])->findOrFail($id);
        
        DB::transaction(function () use ($withdrawal, $request) {
            // ✅ Vérifier si l'utilisateur a un wallet avant de créditer
            $user = User::find($withdrawal->user_id);
            
            if ($user->wallet) {
                $this->walletService->credit(
                    $withdrawal->user_id, 
                    (float)$withdrawal->amount, 
                    'Withdrawal rejected - refund', 
                    'refund_'.$withdrawal->id
                );
            } else {
                // Si pas de wallet, on le crée automatiquement avec le remboursement
                $wallet = $user->wallet()->create([
                    'balance' => (float)$withdrawal->amount,
                    'pending' => 0,
                    'total_earned' => (float)$withdrawal->amount,
                    'total_withdrawn' => 0,
                    'currency' => 'EUR'
                ]);
                
                // Log pour admin
                \Log::info("Wallet auto-créé pour user {$user->id} lors du rejet de withdrawal #{$withdrawal->id}");
            }
            
            $withdrawal->update([
                'status' => 'rejected',
                'admin_note' => $request->reason,
                'processed_by' => $request->user()->id,
                'processed_at' => now()
            ]);
        });
        
        return response()->json(['success' => true, 'message' => "Withdrawal #{$id} rejected. Refunded."]);
    }

    public function postbacks(Request $request)
    {
        return response()->json([
            'success' => true, 
            'data' => Postback::with('user')
                             ->when($request->status, fn($q) => $q->where('status',$request->status))
                             ->orderByDesc('created_at')
                             ->paginate(25)
        ]);
    }

    public function fraudLogs(Request $request)
    {
        return response()->json([
            'success' => true, 
            'data' => FraudLog::with('user')
                             ->orderByDesc('created_at')
                             ->paginate(25)
        ]);
    }
}