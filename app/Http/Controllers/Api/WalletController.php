<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $w = $request->user()->wallet;
        return response()->json(['success' => true, 'data' => ['balance' => (float)$w->balance, 'pending' => (float)$w->pending, 'total_earned' => (float)$w->total_earned, 'total_withdrawn' => (float)$w->total_withdrawn, 'currency' => $w->currency]]);
    }

    public function history(Request $request)
    {
        $txs = WalletTransaction::where('user_id', $request->user()->id)->orderByDesc('created_at')->paginate(20);
        return response()->json(['success' => true, 'data' => $txs->through(fn($t) => ['id' => $t->id, 'type' => $t->type, 'amount' => (float)$t->amount, 'balance_after' => (float)$t->balance_after, 'description' => $t->description, 'created_at' => $t->created_at?->toISOString()])]);
    }
}
