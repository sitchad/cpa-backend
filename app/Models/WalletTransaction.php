<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id','type','amount','balance_before',
        'balance_after','description','reference','meta',
    ];

    protected $casts = [
        'amount'         => 'decimal:8',
        'balance_before' => 'decimal:8',
        'balance_after'  => 'decimal:8',
        'meta'           => 'array',
    ];

    public function user()            { return $this->belongsTo(User::class); }
    public function transactionable() { return $this->morphTo(); }
}
