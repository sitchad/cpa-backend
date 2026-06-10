<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title','description','network','external_offer_id',
        'payout','currency','category','country_targeting',
        'thumbnail_url','offer_url','is_active','daily_cap','conversions_today',
    ];

    protected $casts = ['payout' => 'decimal:4', 'is_active' => 'boolean'];

    public function clicks() { return $this->hasMany(Click::class); }
}
