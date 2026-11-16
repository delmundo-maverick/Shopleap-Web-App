<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'public_code',
        'latitude',
        'longitude',
        'manager_invite_token',
        'manager_invite_expires_at',
        'manager_password_set_at',
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
        'joining_existing_company',
        'id_upload_path',
        'business_permit_path',
        'sec_certificate_path',
        'bir_registration_path',
        'service_regions',
        'status',
        'rejection_reason',
        'reviewed_at',
    ];

    protected $hidden = ['manager_invite_token'];

    protected function casts(): array
    {
        return [
            'service_regions' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'manager_invite_expires_at' => 'datetime',
            'manager_password_set_at' => 'datetime',
            'birthday' => 'date',
            'reviewed_at' => 'datetime',
            'joining_existing_company' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** A hub can only route parcels while it is approved and its account is active. */
    public function isAvailable(): bool
    {
        return $this->status === 'approved'
            && $this->user?->account_status === 'active';
    }

    /**
     * Stable company identity, with normalized names for standalone hubs.
     * Hubs without a company name are treated as
     * their own company so two unnamed hubs never match by accident.
     */
    public function companyKey(): string
    {
        if ($this->company_id !== null) {
            return 'company:'.$this->company_id;
        }

        $name = $this->company_name ?: $this->business_name;

        return $name
            ? HubServiceArea::nameKey($name)
            : 'hub:'.$this->getKey();
    }

    /** Whether this hub and another hub belong to the same logistics company. */
    public function isSameCompanyAs(self $other): bool
    {
        if ($this->company_id !== null || $other->company_id !== null) {
            return $this->company_id !== null && $other->company_id !== null
                && (int) $this->company_id === (int) $other->company_id;
        }

        return $this->companyKey() === $other->companyKey();
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
            ->whereHas('user', fn ($u) => $u->where('account_status', 'active'));
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
            $q->whereHas('serviceAreas', fn ($a) => $a
                ->whereRaw('LOWER(hub_service_areas.province) = ?', [$province])
                ->whereRaw('LOWER(hub_service_areas.municipality) = ?', [$municipality]))
                ->orWhere(fn ($own) => $own
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
