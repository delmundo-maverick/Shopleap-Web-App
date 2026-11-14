<?php

namespace App\Services;

use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\HubServiceAreaBarangay;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\PsgcBarangay;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Single ownership-check service for hub service areas and barangays.
 * One barangay = exactly one hub. Writes run in a transaction with row locks;
 * the unique index is the final guard against concurrent claims.
 */
class HubServiceAreaService
{
    private const PSGC_BASE = 'https://psgc.gitlab.io/api';

    /** An order in one of these statuses no longer depends on the hub's barangay mapping. */
    public const FINAL_STATUSES = [
        Order::STATUS_DELIVERED,
        Order::STATUS_COMPLETED,
        Order::STATUS_CANCELLED,
        Order::STATUS_RETURNED,
    ];

    // ── Barangay data (local table, filled on demand) ─────────────────────

    /** True when the hub's municipality has barangays locally, fetching them once if not. */
    public function ensureBarangayData(LogisticsProfile $hub): bool
    {
        $province = trim((string) $hub->province);
        $municipality = trim((string) $hub->municipality);

        if ($province === '' || $municipality === '') {
            return false;
        }

        return $this->hasLocalBarangayData($province, $municipality)
            || $this->fetchAndStoreBarangays($province, $municipality);
    }

    private function hasLocalBarangayData(string $province, string $municipality): bool
    {
        return PsgcBarangay::hasMunicipality($province, $municipality);
    }

    /** Fetches ONE municipality's barangays from PSGC and stores them under the hub's registered spelling. */
    private function fetchAndStoreBarangays(string $province, string $municipality): bool
    {
        try {
            $lock = Cache::lock(
                'psgc:fetch:'.sha1($this->placeKey($province).'|'.$this->placeKey($municipality)),
                60
            );
        } catch (\Throwable) {
            // The configured cache driver does not support locks; fetching once is still safe.
            return $this->downloadOnce($province, $municipality);
        }

        try {
            return (bool) $lock->block(20, fn () => $this->downloadOnce($province, $municipality));
        } catch (LockTimeoutException) {
            return $this->hasLocalBarangayData($province, $municipality);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function downloadOnce(string $province, string $municipality): bool
    {
        // Another request may have filled the table while we waited for the lock.
        if (PsgcBarangay::hasMunicipality($province, $municipality, fresh: true)) {
            return true;
        }

        return $this->downloadBarangays($province, $municipality);
    }

    private function downloadBarangays(string $province, string $municipality): bool
    {
        $http = Http::timeout(15)->retry(2, 300)->acceptJson();

        $place = $this->findApiPlace($http, $province, $municipality);

        if ($place === null) {
            return false;
        }

        $rows = $http->get(self::PSGC_BASE.'/cities-municipalities/'.$place['municipality_code'].'/barangays/')
            ->throw()
            ->json();

        $barangays = collect($rows)
            ->map(fn ($row) => [
                'name' => trim((string) ($row['name'] ?? '')),
                'code' => $row['code'] ?? null,
            ])
            ->filter(fn (array $row) => $row['name'] !== '')
            ->unique(fn (array $row) => HubServiceArea::nameKey($row['name']))
            ->values();

        if ($barangays->isEmpty()) {
            return false;
        }

        DB::transaction(function () use ($barangays, $province, $municipality, $place): void {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [
                    'psgc:import:'.$this->placeKey($province).'|'.$this->placeKey($municipality),
                ]);
            }

            if (PsgcBarangay::hasMunicipality($province, $municipality, fresh: true)) {
                return;
            }

            $now = now();
            $rows = $barangays->map(fn (array $barangay): array => [
                'province' => $province,
                'municipality' => $municipality,
                'barangay' => $barangay['name'],
                'province_code' => $place['province_code'],
                'municipality_code' => $place['municipality_code'],
                'barangay_code' => $barangay['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($rows->chunk(100) as $chunk) {
                PsgcBarangay::insert($chunk->all());
            }
            DB::afterCommit(fn () => PsgcBarangay::forgetReferenceCache([
                ...$place, 'province' => $province, 'municipality' => $municipality,
            ]));
        });

        return true;
    }

    /**
     * Resolves the PSGC province/region and municipality codes for the hub's
     * registered spelling. NCR has no province entry in PSGC, so regions are
     * checked as a fallback.
     *
     * @return array{province_code: string, municipality_code: string}|null
     */
    private function findApiPlace(PendingRequest $http, string $province, string $municipality): ?array
    {
        $provinces = $http->get(self::PSGC_BASE.'/provinces/')->throw()->json();
        $provinceMatch = $this->firstPlace($provinces, $province);

        if ($provinceMatch !== null) {
            return $this->findMunicipality(
                $http,
                '/provinces/'.$provinceMatch['code'].'/cities-municipalities/',
                $provinceMatch['code'],
                $municipality
            );
        }

        $regions = $http->get(self::PSGC_BASE.'/regions/')->throw()->json();
        $regionMatch = collect($regions)->first(
            fn ($region) => $this->matchesRegion($province, (string) ($region['name'] ?? ''))
        );

        if ($regionMatch === null) {
            return null;
        }

        return $this->findMunicipality(
            $http,
            '/regions/'.$regionMatch['code'].'/cities-municipalities/',
            $regionMatch['code'],
            $municipality
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $places
     * @return array<string, mixed>|null
     */
    private function firstPlace(array $places, string $name): ?array
    {
        $key = $this->placeKey($name);

        return collect($places)->first(
            fn ($place) => $this->placeKey((string) ($place['name'] ?? '')) === $key
        );
    }

    /** @return array{province_code: string, municipality_code: string}|null */
    private function findMunicipality(
        PendingRequest $http,
        string $path,
        string $provinceCode,
        string $municipality
    ): ?array {
        $towns = $http->get(self::PSGC_BASE.$path)->throw()->json();
        $town = $this->firstPlace($towns, $municipality);

        if ($town === null) {
            return null;
        }

        return [
            'province_code' => $provinceCode,
            'municipality_code' => $town['code'],
        ];
    }

    /** "City of Santa Rosa", "Santa Rosa City" and "Santa Rosa" all match; accents are ignored. */
    private function placeKey(string $value): string
    {
        $v = Str::lower(Str::ascii(trim($value)));
        $v = preg_replace('/\(.*?\)/', ' ', $v);
        $v = preg_replace('/\b(city of|municipality of|city|municipality)\b/', ' ', $v);

        return trim(preg_replace('/\s+/', ' ', $v));
    }

    private function matchesRegion(string $province, string $region): bool
    {
        $provinceKey = $this->placeKey($province);
        $regionKey = $this->placeKey($region);

        if ($provinceKey === $regionKey) {
            return true;
        }

        return $regionKey === 'national capital region'
            && in_array($provinceKey, ['metro manila', 'ncr', 'national capital region'], true);
    }

    public function hubLocationIsKnown(LogisticsProfile $hub): bool
    {
        return $this->ensureBarangayData($hub);
    }

    // ── Page data ─────────────────────────────────────────────────────────

    public function barangayStatus(LogisticsProfile $hub): array
    {
        if (! $this->ensureBarangayData($hub)) {
            return [];
        }

        $province = trim((string) $hub->province);
        $municipality = trim((string) $hub->municipality);

        $all = PsgcBarangay::forMunicipality($province, $municipality)->pluck('barangay')->all();

        $owned = HubServiceAreaBarangay::with(['area.logisticsProfile', 'rider'])
            ->where('province_key', HubServiceArea::nameKey($province))
            ->where('municipality_key', HubServiceArea::nameKey($municipality))
            ->whereIn('barangay_key', array_map(HubServiceArea::nameKey(...), $all))
            ->get()
            ->keyBy(fn ($row) => $row->barangay_key);

        $result = [];

        foreach ($all as $name) {
            $key = HubServiceArea::nameKey($name);
            $row = $owned->get($key);

            $base = [
                'barangay' => $name,
                'barangay_key' => $key,
                'status' => 'available',
                'owner_hub_name' => null,
                'rider_id' => null,
                'rider_name' => null,
            ];

            if ($row && (int) $row->area->logistics_profile_id === (int) $hub->id) {
                $base['status'] = 'yours';
                $base['rider_id'] = $row->rider_id;
                $base['rider_name'] = $row->rider?->full_name;
            } elseif ($row) {
                $base['status'] = 'taken';
                $base['owner_hub_name'] = $row->area->logisticsProfile?->business_name ?? 'another hub';
            }

            $result[] = $base;
        }

        return $result;
    }

    public function homeArea(LogisticsProfile $hub): HubServiceArea
    {
        $key = HubServiceArea::keyFor($hub->province, $hub->municipality);

        $area = $hub->serviceAreas()->get()->first(fn (HubServiceArea $a) => $a->area_key === $key);

        return $area ?? $hub->serviceAreas()->create([
            'province' => $hub->province,
            'municipality' => $hub->municipality,
            'zone_name' => null,
            'rider_id' => null,
        ]);
    }

    public function otherAreas(LogisticsProfile $hub): Collection
    {
        $homeKey = HubServiceArea::keyFor($hub->province, $hub->municipality);

        return $hub->serviceAreas()
            ->with(['rider', 'barangays.rider'])
            ->get()
            ->reject(fn (HubServiceArea $a) => $a->area_key === $homeKey)
            ->values();
    }

    public function hubRiders(LogisticsProfile $hub): Collection
    {
        $riders = CourierProfile::where('logistics_profile_id', $hub->id)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $covers = HubServiceAreaBarangay::whereIn('rider_id', $riders->pluck('id'))
            ->selectRaw('rider_id, COUNT(*) AS total')
            ->groupBy('rider_id')
            ->pluck('total', 'rider_id');

        return $riders->map(fn (CourierProfile $r) => [
            'id' => $r->id,
            'full_name' => $r->full_name,
            'covers' => (int) ($covers[$r->id] ?? 0),
        ]);
    }

    // ── Validation helpers ────────────────────────────────────────────────

    /** @param  array<int, int>  $riderIds */
    public function validateRiders(LogisticsProfile $hub, array $riderIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $riderIds))));

        if ($ids === []) {
            return;
        }

        $valid = CourierProfile::where('logistics_profile_id', $hub->id)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->count();

        if ($valid !== count($ids)) {
            throw ValidationException::withMessages([
                'riders' => 'One or more selected riders are not approved, active riders of this hub.',
            ]);
        }
    }

    public function removalBlockedByOrders(LogisticsProfile $hub, HubServiceAreaBarangay $barangayRow): array
    {
        $barangayRow->loadMissing('area');
        $area = $barangayRow->area;

        $base = Order::heldBy($hub->id)
            ->whereNotIn('status', self::FINAL_STATUSES)
            ->whereRaw('LOWER(TRIM(ship_province)) = ?', [HubServiceArea::nameKey($area->province)])
            ->whereRaw('LOWER(TRIM(ship_municipality)) = ?', [HubServiceArea::nameKey($area->municipality)])
            ->whereRaw('LOWER(TRIM(ship_barangay)) = ?', [HubServiceArea::nameKey($barangayRow->barangay)]);

        $count = (clone $base)->count();

        return [
            'blocked' => $count > 0,
            'order_count' => $count,
            'sample_order_ids' => (clone $base)->limit(3)->pluck('id')->all(),
        ];
    }

    // ── Writes ────────────────────────────────────────────────────────────

    /**
     * Replace the hub's selected barangays (and their riders) in one transaction.
     *
     * @param  array{barangays?: array<int, string>, riders?: array<string, int|string|null>}  $input
     */
    public function claimBarangays(LogisticsProfile $hub, array $input): void
    {
        if (! $this->ensureBarangayData($hub)) {
            throw ValidationException::withMessages([
                'barangays' => 'The barangay list could not be loaded. Please retry.',
            ]);
        }

        $province = trim((string) $hub->province);
        $municipality = trim((string) $hub->municipality);

        $names = collect($input['barangays'] ?? [])
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim($v))
            ->unique(fn ($v) => HubServiceArea::nameKey($v))
            ->values();

        $riderByKey = collect($input['riders'] ?? [])
            ->mapWithKeys(fn ($id, $name) => [HubServiceArea::nameKey((string) $name) => $id ? (int) $id : null]);

        // Every barangay must belong to the hub's own municipality.
        $validKeys = PsgcBarangay::forMunicipality($province, $municipality)
            ->pluck('barangay')
            ->map(fn ($b) => HubServiceArea::nameKey($b))
            ->all();

        $invalid = $names->filter(fn ($n) => ! in_array(HubServiceArea::nameKey($n), $validKeys, true))->all();

        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'barangays' => 'These barangays are not in your municipality: '.implode(', ', $invalid).'.',
            ]);
        }

        $this->validateRiders($hub, $riderByKey->filter()->values()->all());

        $wantedKeys = $names->map(fn ($n) => HubServiceArea::nameKey($n))->all();

        try {
            $summary = DB::transaction(function () use ($hub, $names, $wantedKeys, $riderByKey, $province, $municipality) {
                $area = $this->homeArea($hub);

                $taken = HubServiceAreaBarangay::where('province_key', HubServiceArea::nameKey($province))
                    ->where('municipality_key', HubServiceArea::nameKey($municipality))
                    ->whereIn('barangay_key', $wantedKeys)
                    ->with('area.logisticsProfile')
                    ->lockForUpdate()
                    ->get()
                    ->filter(fn ($row) => (int) $row->area->logistics_profile_id !== (int) $hub->id);

                if ($taken->isNotEmpty()) {
                    $details = $taken->map(fn ($r) => 'Brgy. '.$r->barangay.' ('.($r->area->logisticsProfile?->business_name ?? 'another hub').')')->implode('; ');

                    throw ValidationException::withMessages([
                        'barangays' => 'These barangays already belong to another hub: '.$details.'.',
                    ]);
                }

                $current = $area->barangays()->lockForUpdate()->get()->keyBy(fn ($b) => HubServiceArea::nameKey($b->barangay));

                // Removals first, blocked while active orders still depend on the barangay.
                $removed = [];
                foreach ($current as $key => $row) {
                    if (in_array($key, $wantedKeys, true)) {
                        continue;
                    }

                    $row->setRelation('area', $area);
                    $blocked = $this->removalBlockedByOrders($hub, $row);

                    if ($blocked['blocked']) {
                        throw ValidationException::withMessages([
                            'barangays' => "Cannot remove Brgy. {$row->barangay}: it has {$blocked['order_count']} active order(s) held by your hub. Complete or cancel them first.",
                        ]);
                    }

                    $removed[] = $row->barangay;
                    $row->delete();
                }

                // Additions and rider changes.
                $added = [];
                foreach ($names as $name) {
                    $key = HubServiceArea::nameKey($name);
                    $riderId = $riderByKey->get($key);

                    if ($row = $current->get($key)) {
                        if ((int) $row->rider_id !== (int) $riderId) {
                            $row->update(['rider_id' => $riderId]);
                        }

                        continue;
                    }

                    $area->barangays()->create(['barangay' => $name, 'rider_id' => $riderId]);
                    $added[] = $name;
                }

                return ['added' => $added, 'removed' => $removed];
            }, 3);
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23505') {
                throw ValidationException::withMessages([
                    'barangays' => 'One of these barangays was just claimed by another hub. Reload the page and try again.',
                ]);
            }

            throw $e;
        }

        if ($summary['added'] || $summary['removed']) {
            ActivityLogger::log(
                'service_barangays_updated',
                'logistics',
                "{$hub->business_name} updated its service barangays (".count($summary['added']).' added, '.count($summary['removed']).' removed).',
                $hub,
                $summary
            );
        }
    }

    /** Remove an entire legacy service area after checking every barangay in it. */
    public function deleteArea(LogisticsProfile $hub, HubServiceArea $area): void
    {
        DB::transaction(function () use ($hub, $area) {
            $locked = HubServiceArea::whereKey($area->getKey())
                ->where('logistics_profile_id', $hub->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'area' => 'This service area no longer exists.',
                ]);
            }

            foreach ($locked->barangays()->lockForUpdate()->get() as $barangayRow) {
                $barangayRow->setRelation('area', $locked);
                $blocked = $this->removalBlockedByOrders($hub, $barangayRow);

                if ($blocked['blocked']) {
                    throw ValidationException::withMessages([
                        'area' => "Cannot remove {$locked->municipality}: Brgy. {$barangayRow->barangay} has {$blocked['order_count']} non-final order(s) held by your hub.",
                    ]);
                }
            }

            $locked->delete();
        }, 3);
    }
}
