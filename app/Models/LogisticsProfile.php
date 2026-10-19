<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsProfile extends Model
{
    protected $fillable = [
        'user_id',
        'last_name',
        'first_name',
        'middle_initial',
        'sex',
        'contact_no',
        'birthday',
        'age',
        'province',
        'municipality',
        'barangay',
        'street_address',
        'business_name',
        'company_name',
        'id_upload_path',
        'business_permit_path',
        'status',
        'rejection_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function riders()
    {
        return $this->hasMany(CourierProfile::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function serviceAreas()
    {
        return $this->hasMany(HubServiceArea::class);
    }

    /** Approved hubs whose user account is active. */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'approved')
            ->whereHas('user', fn($u) => $u->where('account_status', 'active'));
    }

    /**
     * Hubs that serve a municipality: it is either listed in their service areas,
     * or they have not configured any areas yet and it is their own base municipality.
     */
    public function scopeCovering($query, ?string $province, ?string $municipality)
    {
        $province = mb_strtolower(trim((string) $province));
        $municipality = mb_strtolower(trim((string) $municipality));

        return $query->where(function ($q) use ($province, $municipality) {
            $q->whereHas('serviceAreas', fn($a) => $a
                ->whereRaw('LOWER(hub_service_areas.province) = ?', [$province])
                ->whereRaw('LOWER(hub_service_areas.municipality) = ?', [$municipality]))
                ->orWhere(fn($own) => $own
                    ->whereDoesntHave('serviceAreas')
                    ->whereRaw('LOWER(logistics_profiles.province) = ?', [$province])
                    ->whereRaw('LOWER(logistics_profiles.municipality) = ?', [$municipality]));
        });
    }

    /** Approved, active hubs that cover BOTH the pickup area and the delivery area. */
    public function scopeServing($query, ?string $fromProvince, ?string $fromMunicipality, ?string $toProvince, ?string $toMunicipality)
    {
        return $query->available()
            ->covering($fromProvince, $fromMunicipality)
            ->covering($toProvince, $toMunicipality);
    }
}
