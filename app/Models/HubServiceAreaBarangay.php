<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HubServiceAreaBarangay extends Model
{
    protected $fillable = ['hub_service_area_id', 'barangay', 'rider_id'];

    public function area()
    {
        return $this->belongsTo(HubServiceArea::class, 'hub_service_area_id');
    }

    public function rider()
    {
        return $this->belongsTo(CourierProfile::class, 'rider_id');
    }
}
