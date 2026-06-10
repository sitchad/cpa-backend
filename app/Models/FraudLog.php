<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudLog extends Model
{
    protected $fillable = [
        'user_id','ip_address','device_fingerprint',
        'type','fraud_score','details','action_taken',
    ];

    protected $casts = ['details' => 'array', 'fraud_score' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
}
