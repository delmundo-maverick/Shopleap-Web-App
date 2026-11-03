<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class HubServiceAreaBarangay extends Model
{
    protected $fillable = [
        'hub_service_area_id',
        'barangay',
        'province_key',
        'municipality_key',
        'barangay_key',
        'rider_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $area = $row->area;

            if (! $area) {
                throw new InvalidArgumentException('A barangay must belong to a hub service area.');
            }

            $row->barangay = trim($row->barangay);
            $row->province_key = HubServiceArea::nameKey($area->province);
            $row->municipality_key = HubServiceArea::nameKey($area->municipality);
            $row->barangay_key = HubServiceArea::nameKey($row->barangay);
        });
    }

    public function area()
    {
        return $this->belongsTo(HubServiceArea::class, 'hub_service_area_id');
    }

    public function rider()
    {
        return $this->belongsTo(CourierProfile::class, 'rider_id');
    }
}
