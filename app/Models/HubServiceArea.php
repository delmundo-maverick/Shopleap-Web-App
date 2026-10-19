<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubServiceArea extends Model
{
    protected $fillable = ['logistics_profile_id', 'province', 'municipality', 'zone_name', 'rider_id'];

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function rider()
    {
        return $this->belongsTo(CourierProfile::class, 'rider_id');
    }

    public static function keyFor(?string $province, ?string $municipality): string
    {
        return mb_strtolower(trim((string) $province)) . '|' . mb_strtolower(trim((string) $municipality));
    }

    public function getAreaKeyAttribute(): string
    {
        return self::keyFor($this->province, $this->municipality);
    }
}
