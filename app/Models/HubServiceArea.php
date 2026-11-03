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

    public function barangays()
    {
        return $this->hasMany(HubServiceAreaBarangay::class);
    }

    /** Normalized name used for every place key so case and extra spaces do not create duplicates. */
    public static function nameKey(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    public static function keyFor(?string $province, ?string $municipality): string
    {
        return self::nameKey($province).'|'.self::nameKey($municipality);
    }

    public static function barangayKey(?string $barangay): string
    {
        return self::nameKey($barangay);
    }

    public static function placeKey(?string $province, ?string $municipality, ?string $barangay): string
    {
        return self::nameKey($province).'|'.self::nameKey($municipality).'|'.self::nameKey($barangay);
    }

    public function getAreaKeyAttribute(): string
    {
        return self::keyFor($this->province, $this->municipality);
    }

    /**
     * Suggested rider for a barangay: the barangay's own rider first, then the
     * municipality rider. Riders who are not approved and active are skipped.
     * Load `rider` and `barangays.rider` before calling this in a loop.
     */
    public function riderFor(?string $barangay): ?CourierProfile
    {
        $key = self::barangayKey($barangay);
        $candidates = [];

        if ($key !== '') {
            $mapped = $this->barangays->first(fn ($b) => self::barangayKey($b->barangay) === $key);
            $candidates[] = $mapped?->rider;
        }

        $candidates[] = $this->rider;

        foreach ($candidates as $rider) {
            if ($rider && $rider->status === 'approved' && $rider->is_active) {
                return $rider;
            }
        }

        return null;
    }
}
