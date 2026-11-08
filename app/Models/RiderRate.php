<?php

namespace App\Models;

use Database\Factories\RiderRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiderRate extends Model
{
    /** @use HasFactory<RiderRateFactory> */
    use HasFactory;

    protected $fillable = ['logistics_profile_id', 'amount', 'effective_from', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'effective_from' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Rider rates are append-only.'));
        static::deleting(fn () => throw new \LogicException('Rider rates are append-only.'));
    }
}
