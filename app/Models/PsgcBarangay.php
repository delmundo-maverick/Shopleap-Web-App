<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * All barangays in a province/municipality, ordered alphabetically.
     */
    public static function forMunicipality(string $province, string $municipality): Collection
    {
        return self::forMunicipalityQuery($province, $municipality)
            ->orderBy('barangay')
            ->get();
    }

    protected static function forMunicipalityQuery(string $province, string $municipality): Builder
    {
        return self::where('province', 'ilike', trim($province))
            ->where('municipality', 'ilike', trim($municipality));
    }
}
