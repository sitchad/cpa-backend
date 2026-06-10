<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id','amount','method','wallet_address',
        'status','tx_hash','admin_note','processed_by',
        'processed_at','ip_address',
    ];

    protected $casts = [
        'amount'       => 'decimal:8',
        'processed_at' => 'datetime',
    ];

    public function user()        { return $this->belongsTo(User::class); }
    public function processedBy() { return $this->belongsTo(User::class, 'processed_by'); }
}
