<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::memo()->rememberForever("platform_setting:{$key}", function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::memo()->forget("platform_setting:{$key}");
    }

    /** Commission rate as a decimal fraction (e.g. 0.10), not a whole percentage. */
    public static function commissionRate(): float
    {
        return ((float) static::get('commission_rate', 10)) / 100;
    }
}
