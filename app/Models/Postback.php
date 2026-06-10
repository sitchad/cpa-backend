<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postback extends Model
{
    protected $fillable = [
        'click_id','user_id','offer_id','click_db_id',
        'network','payout','currency','status',
        'raw_payload','ip_address','reject_reason','wallet_credited',
    ];

    protected $casts = [
        'raw_payload'     => 'array',
        'wallet_credited' => 'boolean',
        'payout'          => 'decimal:4',
    ];

    public function user()  { return $this->belongsTo(User::class); }
    public function offer() { return $this->belongsTo(Offer::class); }
    public function click() { return $this->belongsTo(Click::class, 'click_db_id'); }
    public function transaction() { return $this->morphOne(WalletTransaction::class, 'transactionable'); }
}
