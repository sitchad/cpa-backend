<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;
        
        // ✅ Vérifier si le wallet existe
        if (!$wallet) {
            return response()->json([
                'success' => true, 
                'data' => [
                    'balance' => 0,
                    'pending' => 0,
                    'total_earned' => 0,
                    'total_withdrawn' => 0,
                    'currency' => 'EUR',
                    'has_wallet' => false  // ← Important pour le frontend
                ]
            ]);
        }
        
        // ✅ Wallet existe
        return response()->json([
            'success' => true, 
            'data' => [
                'balance' => (float)$wallet->balance,
                'pending' => (float)$wallet->pending,
                'total_earned' => (float)$wallet->total_earned,
                'total_withdrawn' => (float)$wallet->total_withdrawn,
                'currency' => $wallet->currency,
                'has_wallet' => true
            ]
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        
        // ✅ Vérifier si le wallet existe
        if (!$user->wallet) {
            return response()->json([
                'success' => true,
                'data' => [],
                'current_page' => 1,
                'per_page' => 20,
                'total' => 0,
                'last_page' => 1
            ]);
        }
        
        $txs = WalletTransaction::where('user_id', $user->id)
                                ->orderByDesc('created_at')
                                ->paginate(20);
                                
        return response()->json([
            'success' => true, 
            'data' => $txs->through(fn($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => (float)$t->amount,
                'balance_after' => (float)$t->balance_after,
                'description' => $t->description,
                'created_at' => $t->created_at?->toISOString()
            ]),
            'current_page' => $txs->currentPage(),
            'per_page' => $txs->perPage(),
            'total' => $txs->total(),
            'last_page' => $txs->lastPage()
        ]);
    }

  public function create(Request $request)
{
    $user = $request->user();
    
    // Vérifier si wallet existe déjà
    if ($user->wallet) {
        return response()->json([
            'success' => false,
            'message' => 'Wallet already exists'
        ], 400);
    }
    
    // Créer le wallet avec solde 0
    $wallet = $user->wallet()->create([
        'balance' => 0,
        'pending' => 0,
        'total_earned' => 0,
        'total_withdrawn' => 0,
        'currency' => 'EUR'
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Wallet created successfully',
        'data' => [
            'balance' => (float)$wallet->balance,
            'has_wallet' => true
        ]
    ]);
}
}