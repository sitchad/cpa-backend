<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id','balance','pending',
        'total_earned','total_withdrawn','currency',
    ];

    protected $casts = [
        'balance'         => 'decimal:8',
        'pending'         => 'decimal:8',
        'total_earned'    => 'decimal:8',
        'total_withdrawn' => 'decimal:8',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
