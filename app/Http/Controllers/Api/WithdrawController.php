<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WithdrawController extends Controller
{
    const MIN = 10.00;
    public function __construct(private WalletService $walletService) {}

    public function index(Request $request)
    {
        $w = Withdrawal::where('user_id', $request->user()->id)->orderByDesc('created_at')->paginate(15);
        return response()->json(['success' => true, 'data' => $w->through(fn($x) => $this->fmt($x))]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), ['amount' => 'required|numeric|min:' . self::MIN, 'wallet_address' => ['required', 'string', 'regex:/^T[1-9A-HJ-NP-Za-km-z]{33}$/']]);
        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user = $request->user();
        if ((float)$user->wallet->balance < (float)$request->amount) return response()->json(['success' => false, 'message' => 'Insufficient balance.'], 422);
        if (Withdrawal::where('user_id', $user->id)->whereIn('status', ['pending', 'processing'])->exists()) return response()->json(['success' => false, 'message' => 'Pending withdrawal exists.'], 429);

        $w = DB::transaction(function () use ($user, $request) {
            $this->walletService->debit($user->id, (float)$request->amount, 'Withdrawal USDT TRC20', 'withdraw_' . time(), ['wallet_address' => $request->wallet_address]);
            return Withdrawal::create(['user_id' => $user->id, 'amount' => $request->amount, 'method' => 'USDT_TRC20', 'wallet_address' => $request->wallet_address, 'status' => 'pending', 'ip_address' => $request->ip()]);
        });
        return response()->json(['success' => true, 'message' => 'Withdrawal submitted.', 'data' => $this->fmt($w)], 201);
    }

    public function show(Request $request, int $id)
    {
        return response()->json(['success' => true, 'data' => $this->fmt(Withdrawal::where('user_id', $request->user()->id)->findOrFail($id))]);
    }

    private function fmt(Withdrawal $w): array
    {
        return ['id' => $w->id, 'amount' => (float)$w->amount, 'method' => $w->method, 'wallet_address' => $w->wallet_address, 'status' => $w->status, 'tx_hash' => $w->tx_hash, 'created_at' => $w->created_at?->toISOString(), 'processed_at' => $w->processed_at?->toISOString()];
    }
}
