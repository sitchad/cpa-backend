<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Click extends Model
{
    protected $fillable = [
        'user_id','offer_id','click_id','ip_address',
        'user_agent','device_fingerprint','country',
        'status','converted_at',
    ];

    protected $casts = ['converted_at' => 'datetime'];

    public function user()  { return $this->belongsTo(User::class); }
    public function offer() { return $this->belongsTo(Offer::class); }
}
