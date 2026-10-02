<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierProfile extends Model
{
    protected $fillable = [
        'user_id',
        'logistics_profile_id',
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
        'vehicle_type',
        'plate_number',
        'or_cr_upload_path',
        'license_upload_path',
        'delivery_area',
        'status',
        'is_active',
        'rejection_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'reviewed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logisticsProfile()
    {
        return $this->belongsTo(LogisticsProfile::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
