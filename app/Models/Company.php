<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = ['name', 'logo_path', 'address', 'contact_phone', 'contact_email', 'status', 'rejection_reason', 'approved_by', 'approved_at', 'default_rider_rate'];

    protected $attributes = ['status' => 'pending'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'default_rider_rate' => 'decimal:2'];
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
