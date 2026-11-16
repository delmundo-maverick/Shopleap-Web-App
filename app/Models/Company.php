<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @var array<string, array{input: string, path: string, label: string}> */
    public const DOCUMENTS = [
        'id' => ['input' => 'id_upload', 'path' => 'id_upload_path', 'label' => 'Valid ID of the authorized representative'],
        'permit' => ['input' => 'business_permit', 'path' => 'business_permit_path', 'label' => "Mayor's / Business Permit"],
        'sec' => ['input' => 'sec_certificate', 'path' => 'sec_certificate_path', 'label' => 'SEC Certificate'],
        'bir' => ['input' => 'bir_registration', 'path' => 'bir_registration_path', 'label' => 'BIR Form 2303'],
    ];

    protected $fillable = ['name', 'logo_path', 'address', 'contact_phone', 'contact_email', 'status', 'rejection_reason', 'approved_by', 'approved_at', 'default_rider_rate', 'id_upload_path', 'business_permit_path', 'sec_certificate_path', 'bir_registration_path', 'first_name', 'last_name', 'middle_initial', 'sex', 'birthday', 'age', 'province', 'municipality', 'barangay', 'street_address', 'service_regions'];

    protected $hidden = ['id_upload_path', 'business_permit_path', 'sec_certificate_path', 'bir_registration_path', 'sex', 'birthday'];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'default_rider_rate' => 'decimal:2', 'birthday' => 'date', 'age' => 'integer', 'service_regions' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $company): void {
            $company->public_code = (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_code';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hubs(): HasMany
    {
        return $this->hasMany(LogisticsProfile::class);
    }

    public function riders(): HasManyThrough
    {
        return $this->hasManyThrough(CourierProfile::class, LogisticsProfile::class);
    }
}
