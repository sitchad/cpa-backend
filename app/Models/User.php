<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name','email','password','role','status',
        'referral_code','referred_by','country',
        'ip_address','device_fingerprint','ban_reason',
    ];

    protected $hidden = ['password','remember_token'];
    protected $casts  = ['email_verified_at' => 'datetime'];

    public function wallet()       { return $this->hasOne(Wallet::class); }
    public function clicks()       { return $this->hasMany(Click::class); }
    public function postbacks()    { return $this->hasMany(Postback::class); }
    public function withdrawals()  { return $this->hasMany(Withdrawal::class); }
    public function fraudLogs()    { return $this->hasMany(FraudLog::class); }
    public function transactions() { return $this->hasMany(WalletTransaction::class); }
}
