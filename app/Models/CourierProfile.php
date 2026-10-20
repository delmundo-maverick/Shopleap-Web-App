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

    /**
     * Maps the rider's registration barangay to this rider, inside the hub's service area
     * for the same municipality. Never overwrites a barangay held by another approved,
     * active rider, and never creates a service area on its own.
     *
     * @return array{mapped: bool, message: string}
     */
    public function mapHomeBarangay(): array
    {
        $barangay = trim((string) $this->barangay);

        if ($barangay === '') {
            return ['mapped' => false, 'message' => 'No barangay on the rider\'s application, so none was mapped.'];
        }

        $key = HubServiceArea::keyFor($this->province, $this->municipality);

        $area = HubServiceArea::where('logistics_profile_id', $this->logistics_profile_id)
            ->get()
            ->first(fn($a) => $a->area_key === $key);

        if (! $area) {
            return [
                'mapped' => false,
                'message' => "{$this->municipality} is not in your service areas, so Brgy. {$barangay} was not mapped. Add it under Service Areas, then assign the barangay.",
            ];
        }

        $barangayKey = HubServiceArea::barangayKey($barangay);

        $existing = $area->barangays()->with('rider')->get()
            ->first(fn($b) => HubServiceArea::barangayKey($b->barangay) === $barangayKey);

        if ($existing && $existing->rider_id && $existing->rider_id !== $this->id) {
            $other = $existing->rider;

            if ($other && $other->status === 'approved' && $other->is_active) {
                return [
                    'mapped' => false,
                    'message' => "Brgy. {$barangay} is already assigned to {$other->full_name}, so it was left as is. You can change it under Service Areas.",
                ];
            }
        }

        if ($existing) {
            $existing->update(['rider_id' => $this->id]);
        } else {
            $area->barangays()->create(['barangay' => $barangay, 'rider_id' => $this->id]);
        }

        return ['mapped' => true, 'message' => "Brgy. {$barangay}, {$area->municipality} was assigned to them automatically."];
    }
}
