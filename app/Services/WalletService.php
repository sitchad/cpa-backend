<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function credit(int $userId, float $amount, string $description = '', string $reference = '', array $meta = [], ?Model $morphModel = null): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $amount, $description, $reference, $meta, $morphModel) {
            $wallet = Wallet::lockForUpdate()->where('user_id', $userId)->firstOrFail();
            $before = (float) $wallet->balance;
            $after  = $before + $amount;
            $wallet->update(['balance' => $after, 'total_earned' => $wallet->total_earned + $amount]);
            $tx = WalletTransaction::create([
                'user_id'        => $userId,
                'type'           => 'credit',
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'description'    => $description,
                'reference'      => $reference ?: 'credit_' . uniqid(),
                'meta'           => $meta,
            ]);
            if ($morphModel) $tx->transactionable()->associate($morphModel)->save();
            return $tx;
        });
    }

    public function debit(int $userId, float $amount, string $description = '', string $reference = '', array $meta = []): WalletTransaction
    {
        return DB::transaction(function () use ($userId, $amount, $description, $reference, $meta) {
            $wallet = Wallet::lockForUpdate()->where('user_id', $userId)->firstOrFail();
            if ((float) $wallet->balance < $amount) throw new \Exception('Insufficient balance');
            $before = (float) $wallet->balance;
            $after  = $before - $amount;
            $wallet->update(['balance' => $after, 'total_withdrawn' => $wallet->total_withdrawn + $amount]);
            return WalletTransaction::create([
                'user_id'        => $userId,
                'type'           => 'debit',
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'description'    => $description,
                'reference'      => $reference ?: 'debit_' . uniqid(),
                'meta'           => $meta,
            ]);
        });
    }
}
