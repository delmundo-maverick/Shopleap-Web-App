<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    const ZONE_SAME_MUNICIPALITY = 'same_municipality';
    const ZONE_SAME_PROVINCE = 'same_province';
    const ZONE_DIFFERENT_PROVINCE = 'different_province';

    const FALLBACK_FEES = [
        self::ZONE_SAME_MUNICIPALITY => 40,
        self::ZONE_SAME_PROVINCE => 70,
        self::ZONE_DIFFERENT_PROVINCE => 120,
    ];

    protected $fillable = ['zone', 'label', 'fee', 'is_active'];

    protected function casts(): array
    {
        return ['fee' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
