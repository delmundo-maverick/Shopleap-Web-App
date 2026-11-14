<?php

namespace App\Models;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Local mirror of PSGC barangay data. HubServiceAreaService fills one
 * municipality on demand, then every page and validation reads this table
 * instead of calling the external PSGC API again.
 */
class PsgcBarangay extends Model
{
    protected $fillable = [
        'province_code',
        'province',
        'municipality_code',
        'municipality',
        'barangay_code',
        'barangay',
    ];

    public $timestamps = true;

    protected static function booted(): void
    {
        $invalidate = function (self $barangay): void {
            $original = $barangay->getRawOriginal();
            $current = $barangay->getAttributes();
            $barangay->getConnection()->afterCommit(function () use ($original, $current): void {
                static::forgetReferenceCache($original);
                static::forgetReferenceCache($current);
            });
        };
        static::saved($invalidate);
        static::deleted($invalidate);
    }

    public static function referenceCache(): Repository
    {
        return Cache::store(config('cache.psgc_store', 'file'));
    }

    public static function hasMunicipality(string $province, string $municipality, bool $fresh = false): bool
    {
        if (! $fresh && (new static)->getConnection()->transactionLevel() === 0) {
            $rows = static::referenceCache()->get(static::municipalityCacheKey($province, $municipality));
            if (is_array($rows) && $rows !== []) {
                return true;
            }
        }

        return static::forMunicipalityQuery($province, $municipality)->exists();
    }

    /**
     * All barangays in a province/municipality, ordered alphabetically.
     */
    public static function forMunicipality(string $province, string $municipality): Collection
    {
        $rows = static::rememberReference(static::municipalityCacheKey($province, $municipality),
            fn (): array => static::forMunicipalityQuery($province, $municipality)
                ->orderBy('barangay')->get()->map(fn (self $row): array => $row->getAttributes())->all());

        return static::hydrate($rows);
    }

    /** @return array<int, array{name: string, code: string|null}> */
    public static function locationOptions(string $level, ?string $code = null): array
    {
        return static::rememberReference(static::optionsCacheKey($level, $code), function () use ($level, $code): array {
            [$nameField, $codeField] = match ($level) {
                'municipality' => ['municipality', 'municipality_code'],
                'barangay' => ['barangay', 'barangay_code'],
                default => ['province', 'province_code'],
            };

            return static::query()
                ->when($level === 'municipality', fn (Builder $query): Builder => $query->where('province_code', $code))
                ->when($level === 'barangay', fn (Builder $query): Builder => $query->where('municipality_code', $code))
                ->select($nameField.' as name', $codeField.' as code')->distinct()->get()->toArray();
        });
    }

    /** @param array<string, mixed> $attributes */
    public static function forgetReferenceCache(array $attributes): void
    {
        $cache = static::referenceCache();
        $cache->forget(static::municipalityCacheKey((string) ($attributes['province'] ?? ''), (string) ($attributes['municipality'] ?? '')));
        $cache->forget(static::optionsCacheKey('province'));
        $cache->forget(static::optionsCacheKey('municipality', $attributes['province_code'] ?? null));
        $cache->forget(static::optionsCacheKey('barangay', $attributes['municipality_code'] ?? null));
    }

    /** @return array<int, array<string, mixed>> */
    private static function rememberReference(string $key, Closure $load): array
    {
        if ((new static)->getConnection()->transactionLevel() > 0) {
            return $load();
        }

        return static::referenceCache()->remember($key, 3600, $load);
    }

    private static function municipalityCacheKey(string $province, string $municipality): string
    {
        return 'psgc:municipality:v1:'.hash('sha256', json_encode([mb_strtolower(trim($province)), mb_strtolower(trim($municipality))]));
    }

    private static function optionsCacheKey(string $level, ?string $code = null): string
    {
        return 'psgc:options:v1:'.$level.':'.($code ?? 'all');
    }

    protected static function forMunicipalityQuery(string $province, string $municipality): Builder
    {
        return static::whereRaw('LOWER(province) = LOWER(?)', [trim($province)])
            ->whereRaw('LOWER(municipality) = LOWER(?)', [trim($municipality)]);
    }
}
