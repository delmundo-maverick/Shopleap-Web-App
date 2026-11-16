<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\HubServiceAreaBarangay;
use App\Models\LogisticsProfile;
use App\Models\PsgcBarangay;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class CompanyCoverage
{
    public function owned(Company $company): Builder
    {
        $barangays = (new HubServiceAreaBarangay)->getTable();
        $areas = (new HubServiceArea)->getTable();
        $hubs = (new LogisticsProfile)->getTable();
        $riders = (new CourierProfile)->getTable();

        return DB::table($barangays.' as covered')
            ->join($areas.' as area', 'area.id', '=', 'covered.hub_service_area_id')
            ->join($hubs.' as hub', 'hub.id', '=', 'area.logistics_profile_id')->where('hub.company_id', $company->id)
            ->leftJoin($riders.' as assigned', function (JoinClause $join): void {
                $join->on('assigned.id', '=', 'covered.rider_id')->on('assigned.logistics_profile_id', '=', 'hub.id')
                    ->where('assigned.status', 'approved')->where('assigned.is_active', true);
            })->leftJoin($riders.' as fallback', function (JoinClause $join): void {
                $join->on('fallback.id', '=', 'area.rider_id')->on('fallback.logistics_profile_id', '=', 'hub.id')
                    ->where('fallback.status', 'approved')->where('fallback.is_active', true);
            })
            ->select(['covered.province_key', 'covered.municipality_key', 'covered.barangay_key', 'covered.barangay',
                'area.province', 'area.municipality', 'hub.id as hub_id', 'hub.public_code as hub_code', 'hub.business_name as hub_name'])
            ->selectRaw('COALESCE(assigned.id, fallback.id) AS rider_id')
            ->selectRaw("CASE WHEN assigned.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', assigned.first_name, assigned.last_name)) WHEN fallback.id IS NOT NULL THEN TRIM(CONCAT_WS(' ', fallback.first_name, fallback.last_name)) END AS rider_name");
    }

    public function gaps(Company $company): Builder
    {
        return DB::query()->fromSub($this->owned($company), 'coverage')->whereNull('rider_id');
    }

    /** @param array<string, mixed> $filters */
    public function listing(Company $company, array $filters): Builder
    {
        $reference = (new PsgcBarangay)->getTable();
        $hubs = (new LogisticsProfile)->getTable();
        $places = DB::table($reference.' as reference')->whereExists(function (Builder $query) use ($hubs, $company): void {
            $query->selectRaw('1')->from($hubs.' as local_hub')->where('local_hub.company_id', $company->id)
                ->whereRaw($this->placeKey('local_hub.province').' = '.$this->placeKey('reference.province'))
                ->whereRaw($this->placeKey('local_hub.municipality').' = '.$this->placeKey('reference.municipality'));
        })->select(['reference.province', 'reference.municipality', 'reference.barangay'])
            ->selectRaw($this->placeKey('reference.province').' AS province_key, '.$this->placeKey('reference.municipality').' AS municipality_key, '.$this->placeKey('reference.barangay').' AS barangay_key');
        $stored = DB::query()->fromSub($this->owned($company), 'stored')->select(['province', 'municipality', 'barangay', 'province_key', 'municipality_key', 'barangay_key']);
        $places->union($stored);
        $unique = DB::query()->fromSub($places, 'places')->select(['province_key', 'municipality_key', 'barangay_key'])
            ->selectRaw('MIN(province) AS province, MIN(municipality) AS municipality, MIN(barangay) AS barangay')
            ->groupBy('province_key', 'municipality_key', 'barangay_key');
        $query = DB::query()->fromSub($unique, 'places')->leftJoinSub($this->owned($company), 'ownership', function (JoinClause $join): void {
            $join->on('ownership.province_key', '=', 'places.province_key')->on('ownership.municipality_key', '=', 'places.municipality_key')->on('ownership.barangay_key', '=', 'places.barangay_key');
        })->select(['places.province', 'places.municipality', 'places.barangay', 'ownership.hub_name', 'ownership.hub_code', 'ownership.rider_id', 'ownership.rider_name']);
        foreach (['province', 'municipality'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('places.'.$field.'_key', HubServiceArea::nameKey($filters[$field]));
            }
        }
        if (! empty($filters['needs_rider'])) {
            $query->whereNotNull('ownership.hub_id')->whereNull('ownership.rider_id');
        }

        return $query;
    }

    public function hasMissingReference(Company $company): bool
    {
        $reference = (new PsgcBarangay)->getTable();
        $hubs = (new LogisticsProfile)->getTable();

        return $company->hubs()->whereNotExists(function (Builder $query) use ($reference, $hubs): void {
            $query->selectRaw('1')->from($reference.' as reference')
                ->whereRaw($this->placeKey('reference.province').' = '.$this->placeKey($hubs.'.province'))
                ->whereRaw($this->placeKey('reference.municipality').' = '.$this->placeKey($hubs.'.municipality'));
        })->exists();
    }

    private function placeKey(string $column): string
    {
        return "LOWER(REGEXP_REPLACE(TRIM({$column}), '\\s+', ' ', 'g'))";
    }
}
