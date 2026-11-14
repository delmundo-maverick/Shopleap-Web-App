<?php

use App\Models\LogisticsProfile;
use App\Models\PsgcBarangay;
use App\Services\BuyerAddressLocations;
use App\Services\HubServiceAreaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    (require database_path('migrations/2026_11_02_000000_create_psgc_barangays_table.php'))->up();
    PsgcBarangay::referenceCache()->flush();
    Http::preventStrayRequests();
});

/** @param array<string, mixed> $attributes */
function referenceBarangay(array $attributes = []): PsgcBarangay
{
    return PsgcBarangay::create([
        'province' => 'Cebu', 'province_code' => '072200000',
        'municipality' => 'Cebu City', 'municipality_code' => '072217000',
        'barangay' => 'Lahug', 'barangay_code' => '072217001',
        ...$attributes,
    ]);
}

it('serves warm municipality lookups without queries and preserves every stored attribute', function () {
    $barangay = referenceBarangay();
    $snapshot = $barangay->getRawOriginal();
    DB::enableQueryLog();
    DB::flushQueryLog();
    $first = PsgcBarangay::forMunicipality(' cebu ', 'cebu CITY')->first();
    expect(DB::getQueryLog())->toHaveCount(1);
    DB::flushQueryLog();
    $cached = PsgcBarangay::forMunicipality('CEBU', 'Cebu City')->first();
    expect($cached->getRawOriginal())->toEqual($snapshot)
        ->and($cached->id)->toBe($first->id)
        ->and(PsgcBarangay::hasMunicipality('Cebu', 'Cebu City'))->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
    DB::disableQueryLog();
});

it('checks uncached municipality presence with exists rather than loading the rows', function () {
    referenceBarangay();
    DB::enableQueryLog();
    DB::flushQueryLog();
    expect(PsgcBarangay::hasMunicipality('Cebu', 'Cebu City'))->toBeTrue();
    $queries = DB::getQueryLog();
    expect($queries)->toHaveCount(1)
        ->and(strtolower($queries[0]['query']))->toContain('exists');
    DB::disableQueryLog();
});

it('invalidates both old and new location lists when a record changes', function () {
    $barangay = referenceBarangay();
    PsgcBarangay::forMunicipality('Cebu', 'Cebu City');
    PsgcBarangay::forMunicipality('Cebu', 'Mandaue City');
    PsgcBarangay::locationOptions('barangay', '072217000');
    PsgcBarangay::locationOptions('barangay', '072230000');
    PsgcBarangay::locationOptions('municipality', '072200000');

    $barangay->update(['municipality' => 'Mandaue City', 'municipality_code' => '072230000']);

    expect(PsgcBarangay::forMunicipality('Cebu', 'Cebu City'))->toBeEmpty()
        ->and(PsgcBarangay::forMunicipality('Cebu', 'Mandaue City')->pluck('id')->all())->toBe([$barangay->id])
        ->and(PsgcBarangay::locationOptions('barangay', '072217000'))->toBeEmpty()
        ->and(PsgcBarangay::locationOptions('barangay', '072230000'))->toHaveCount(1)
        ->and(PsgcBarangay::locationOptions('municipality', '072200000'))->toBe([['name' => 'Mandaue City', 'code' => '072230000']]);

    $barangay->delete();
    expect(PsgcBarangay::forMunicipality('Cebu', 'Mandaue City'))->toBeEmpty()
        ->and(PsgcBarangay::locationOptions('province'))->toBeEmpty();
});

it('does not publish uncommitted data to the reference cache', function () {
    $barangay = referenceBarangay();
    PsgcBarangay::forMunicipality('Cebu', 'Cebu City');
    DB::beginTransaction();
    $barangay->update(['barangay' => 'Uncommitted name']);
    expect(PsgcBarangay::forMunicipality('Cebu', 'Cebu City')->first()->barangay)->toBe('Uncommitted name');
    DB::rollBack();
    expect(PsgcBarangay::forMunicipality('Cebu', 'Cebu City')->first()->barangay)->toBe('Lahug');

    DB::transaction(fn () => $barangay->fresh()->update(['barangay' => 'Committed name']));
    expect(PsgcBarangay::forMunicipality('Cebu', 'Cebu City')->first()->barangay)->toBe('Committed name');
});

it('caches local dropdown options without changing legacy NCR names or leading zero codes', function () {
    referenceBarangay(['province' => 'Metro Manila', 'province_code' => '130000000', 'municipality' => 'Quezon City', 'municipality_code' => '137404000']);
    $rows = PsgcBarangay::locationOptions('province');
    expect($rows)->toBe([['name' => 'Metro Manila', 'code' => '130000000']]);
    PsgcBarangay::locationOptions('barangay', '137404000');
    DB::enableQueryLog();
    DB::flushQueryLog();
    expect(PsgcBarangay::locationOptions('province'))->toBe($rows)
        ->and(PsgcBarangay::locationOptions('barangay', '137404000'))->toBe([['name' => 'Lahug', 'code' => '072217001']])
        ->and(DB::getQueryLog())->toBeEmpty();
    DB::disableQueryLog();
});

it('keeps buyer API cache traffic out of the database and preserves the local outage fallback', function () {
    referenceBarangay();
    Http::fake(['*' => Http::response([], 503)]);
    $locations = app(BuyerAddressLocations::class);
    $expected = [['name' => 'Lahug', 'code' => '072217001']];
    expect($locations->places('barangay', '072217000'))->toBe($expected);
    DB::enableQueryLog();
    DB::flushQueryLog();
    expect($locations->places('barangay', '072217000'))->toBe($expected)
        ->and(DB::getQueryLog())->toBeEmpty();
    DB::disableQueryLog();
});

it('does not refetch or rewrite an existing municipality including duplicate legacy records', function () {
    referenceBarangay();
    referenceBarangay(['barangay_code' => null]);
    $before = DB::table('psgc_barangays')->orderBy('id')->get()->toArray();
    $hub = new LogisticsProfile(['province' => 'Cebu', 'municipality' => 'Cebu City']);

    expect(app(HubServiceAreaService::class)->ensureBarangayData($hub))->toBeTrue();
    Http::assertNothingSent();
    expect(DB::table('psgc_barangays')->orderBy('id')->get()->toArray())->toEqual($before);
});

it('imports a new municipality in bounded batches while leaving existing rows untouched', function () {
    $existing = referenceBarangay();
    $before = $existing->getRawOriginal();
    PsgcBarangay::locationOptions('municipality', '072200000');
    PsgcBarangay::forMunicipality('Cebu', 'Mandaue City');
    Http::fake([
        '*/provinces/' => Http::response([['name' => 'Cebu', 'code' => '072200000']]),
        '*/provinces/072200000/cities-municipalities/' => Http::response([['name' => 'Mandaue City', 'code' => '072230000']]),
        '*/cities-municipalities/072230000/barangays/' => Http::response(collect(range(1, 205))->map(fn (int $number): array => [
            'name' => 'Barangay '.$number, 'code' => str_pad((string) $number, 9, '0', STR_PAD_LEFT),
        ])->all()),
    ]);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $hub = new LogisticsProfile(['province' => 'Cebu', 'municipality' => 'Mandaue City']);
    expect(app(HubServiceAreaService::class)->ensureBarangayData($hub))->toBeTrue();
    $inserts = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_starts_with(strtolower($query['query']), 'insert into "psgc_barangays"'));
    expect($inserts)->toHaveCount(3);
    DB::disableQueryLog();
    expect($existing->fresh()->getRawOriginal())->toEqual($before)
        ->and(PsgcBarangay::forMunicipality('Cebu', 'Mandaue City'))->toHaveCount(205)
        ->and(PsgcBarangay::where('barangay', 'Barangay 1')->value('barangay_code'))->toBe('000000001')
        ->and(PsgcBarangay::locationOptions('municipality', '072200000'))->toHaveCount(2);
});

it('adds and removes the lookup index without modifying existing rows or rejecting duplicates', function () {
    referenceBarangay();
    referenceBarangay();
    $before = DB::table('psgc_barangays')->orderBy('id')->get()->toArray();
    $migration = require database_path('migrations/2026_11_14_211801_add_psgc_barangay_lookup_index.php');
    $migration->up();
    $plan = DB::select('EXPLAIN QUERY PLAN SELECT * FROM psgc_barangays WHERE LOWER(province) = LOWER(?) AND LOWER(municipality) = LOWER(?) ORDER BY barangay', ['Cebu', 'Cebu City']);
    expect(collect($plan)->pluck('detail')->implode(' '))->toContain('psgc_barangays_case_insensitive_place_idx');
    expect(DB::table('psgc_barangays')->orderBy('id')->get()->toArray())->toEqual($before);
    $migration->down();
    expect(DB::table('psgc_barangays')->orderBy('id')->get()->toArray())->toEqual($before);
});
