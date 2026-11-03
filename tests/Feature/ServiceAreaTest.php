<?php

use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\PsgcBarangay;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;

uses(DatabaseTransactions::class);

/**
 * @return array{0: User, 1: LogisticsProfile}
 */
function serviceAreaHub(?string $province = null, ?string $municipality = null): array
{
    $suffix = fake()->unique()->numerify('#####');
    $province ??= "Test Province {$suffix}";
    $municipality ??= "Test City {$suffix}";

    $user = User::factory()->create([
        'role' => 'logistics',
        'account_status' => 'active',
    ]);

    $hub = $user->logisticsProfile()->create([
        'last_name' => 'Hub',
        'first_name' => 'Owner',
        'sex' => 'male',
        'contact_no' => '09171234567',
        'birthday' => '1990-01-01',
        'age' => 36,
        'province' => $province,
        'municipality' => $municipality,
        'barangay' => 'Poblacion',
        'street_address' => '1 Test Street',
        'business_name' => 'Test Hub '.$suffix,
        'id_upload_path' => 'logistics/test-id.jpg',
        'business_permit_path' => 'logistics/test-permit.jpg',
        'status' => 'approved',
        'reviewed_at' => now(),
    ]);

    return [$user, $hub];
}

/** Seed local PSGC rows so tests never call the external API. */
function serviceAreaSeedBarangays(string $province, string $municipality, array $names): void
{
    foreach ($names as $name) {
        PsgcBarangay::create([
            'province_code' => 'TEST-P',
            'province' => $province,
            'municipality_code' => 'TEST-M',
            'municipality' => $municipality,
            'barangay_code' => 'TEST-B-'.$name,
            'barangay' => $name,
        ]);
    }
}

function serviceAreaOrder(
    User $seller,
    LogisticsProfile $hub,
    string $province,
    string $municipality,
    string $barangay,
    string $status = Order::STATUS_PLACED
): Order {
    return Order::create([
        'seller_id' => $seller->id,
        'status' => $status,
        'logistics_profile_id' => $hub->id,
        'current_hub_id' => $hub->id,
        'ship_province' => $province,
        'ship_municipality' => $municipality,
        'ship_barangay' => $barangay,
    ]);
}

function serviceAreaRider(LogisticsProfile $hub, string $status = 'approved', bool $active = true): CourierProfile
{
    $user = User::factory()->create([
        'role' => 'courier',
        'account_status' => 'active',
    ]);

    return $user->courierProfile()->create([
        'logistics_profile_id' => $hub->id,
        'last_name' => 'Rider',
        'first_name' => 'Test',
        'sex' => 'male',
        'contact_no' => '09179876543',
        'birthday' => '1995-01-01',
        'age' => 31,
        'province' => $hub->province,
        'municipality' => $hub->municipality,
        'barangay' => 'Poblacion',
        'street_address' => '2 Test Street',
        'vehicle_type' => 'Motorcycle',
        'plate_number' => 'ABC 123',
        'or_cr_upload_path' => 'couriers/test-or-cr.jpg',
        'license_upload_path' => 'couriers/test-license.jpg',
        'status' => $status,
        'is_active' => $active,
        'reviewed_at' => now(),
    ]);
}

it('renders the barangay checklist from the hub location', function () {
    [$user, $hub] = serviceAreaHub();
    serviceAreaSeedBarangays($hub->province, $hub->municipality, ['Alpha', 'Beta']);

    $this->actingAs($user)
        ->get(route('logistics.areas.index'))
        ->assertOk()
        ->assertSee('Registered location')
        ->assertSee($hub->municipality)
        ->assertSee('Alpha')
        ->assertSee('Beta')
        ->assertSee('Save barangays')
        ->assertSee('serviceAreaData');
});

it('shows a retry message when the barangay list cannot be loaded', function () {
    [$user] = serviceAreaHub();

    Http::fake(['*' => Http::response([], 500)]);

    $this->actingAs($user)
        ->get(route('logistics.areas.index'))
        ->assertOk()
        ->assertSee("We couldn't load the barangay list", false)
        ->assertSee('Retry')
        ->assertDontSee('serviceAreaData');
});

it('always takes the province and municipality from the hub profile', function () {
    [$user, $hub] = serviceAreaHub();
    serviceAreaSeedBarangays($hub->province, $hub->municipality, ['Alpha', 'Beta']);

    $this->actingAs($user)
        ->postJson(route('logistics.areas.store'), [
            'province' => 'Injected Province',
            'municipality' => 'Injected City',
            'barangays' => ['Alpha'],
            'riders' => [],
        ])
        ->assertOk()
        ->assertJsonStructure(['message']);

    $area = HubServiceArea::where('logistics_profile_id', $hub->id)->firstOrFail();

    expect($area->province)->toBe($hub->province)
        ->and($area->municipality)->toBe($hub->municipality);

    $this->assertDatabaseHas('hub_service_area_barangays', [
        'hub_service_area_id' => $area->id,
        'barangay' => 'Alpha',
    ]);
    $this->assertDatabaseMissing('hub_service_areas', [
        'logistics_profile_id' => $hub->id,
        'province' => 'Injected Province',
    ]);
});

it('replaces the selection and allows saving zero barangays', function () {
    [$user, $hub] = serviceAreaHub();
    serviceAreaSeedBarangays($hub->province, $hub->municipality, ['Alpha', 'Beta']);

    $this->actingAs($user)
        ->postJson(route('logistics.areas.store'), [
            'barangays' => ['Alpha', 'Beta'],
            'riders' => [],
        ])
        ->assertOk();

    $area = HubServiceArea::where('logistics_profile_id', $hub->id)->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('logistics.areas.store'), [
            'barangays' => [],
            'riders' => [],
        ])
        ->assertOk();

    expect($area->barangays()->count())->toBe(0);
});

it('rejects a barangay that already belongs to another hub', function () {
    [$owner, $ownerHub] = serviceAreaHub();
    [$other, $otherHub] = serviceAreaHub($ownerHub->province, $ownerHub->municipality);
    serviceAreaSeedBarangays($ownerHub->province, $ownerHub->municipality, ['Alpha']);

    $ownerArea = $ownerHub->serviceAreas()->create([
        'province' => $ownerHub->province,
        'municipality' => $ownerHub->municipality,
    ]);
    $ownerArea->barangays()->create(['barangay' => 'Alpha']);

    $this->actingAs($other)
        ->postJson(route('logistics.areas.store'), [
            'barangays' => ['Alpha'],
            'riders' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('barangays');

    $this->assertDatabaseMissing('hub_service_area_barangays', [
        'hub_service_area_id' => $otherHub->serviceAreas()->value('id'),
        'barangay' => 'Alpha',
    ]);
});

it('blocks removing a barangay while a non-final order still needs it', function () {
    [$user, $hub] = serviceAreaHub();
    serviceAreaSeedBarangays($hub->province, $hub->municipality, ['Alpha', 'Beta']);
    $seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);

    $this->actingAs($user)
        ->postJson(route('logistics.areas.store'), [
            'barangays' => ['Alpha', 'Beta'],
            'riders' => [],
        ])
        ->assertOk();

    $area = HubServiceArea::where('logistics_profile_id', $hub->id)->firstOrFail();
    $order = serviceAreaOrder($seller, $hub, $hub->province, $hub->municipality, 'Alpha');

    $this->actingAs($user)
        ->postJson(route('logistics.areas.store'), [
            'barangays' => ['Beta'],
            'riders' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('barangays');

    $this->assertDatabaseHas('hub_service_area_barangays', [
        'hub_service_area_id' => $area->id,
        'barangay' => 'Alpha',
    ]);

    $order->update(['status' => Order::STATUS_DELIVERED]);

    $this->actingAs($user)
        ->postJson(route('logistics.areas.store'), [
            'barangays' => ['Beta'],
            'riders' => [],
        ])
        ->assertOk();

    $this->assertDatabaseMissing('hub_service_area_barangays', [
        'hub_service_area_id' => $area->id,
        'barangay' => 'Alpha',
    ]);
});

it('assigns only approved active riders that belong to the hub', function () {
    [$user, $hub] = serviceAreaHub();
    serviceAreaSeedBarangays($hub->province, $hub->municipality, ['Alpha']);
    $rider = serviceAreaRider($hub);

    $this->actingAs($user)
        ->postJson(route('logistics.areas.store'), [
            'barangays' => ['Alpha'],
            'riders' => ['Alpha' => (string) $rider->id],
        ])
        ->assertOk();

    $this->assertDatabaseHas('hub_service_area_barangays', [
        'barangay' => 'Alpha',
        'rider_id' => $rider->id,
    ]);

    [, $otherHub] = serviceAreaHub();
    $otherRider = serviceAreaRider($otherHub);

    $this->actingAs($user)
        ->postJson(route('logistics.areas.store'), [
            'barangays' => ['Alpha'],
            'riders' => ['Alpha' => (string) $otherRider->id],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('riders');
});

it('blocks deleting a legacy area while a non-final order is held', function () {
    [$user, $hub] = serviceAreaHub();
    $legacy = $hub->serviceAreas()->create([
        'province' => 'Legacy Province',
        'municipality' => 'Legacy City',
    ]);
    $legacy->barangays()->create(['barangay' => 'Gamma']);
    $seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);

    serviceAreaOrder($seller, $hub, 'Legacy Province', 'Legacy City', 'Gamma');

    $this->actingAs($user)
        ->delete(route('logistics.areas.destroy', $legacy))
        ->assertRedirect()
        ->assertSessionHasErrors('area');

    $this->assertDatabaseHas('hub_service_areas', ['id' => $legacy->id]);
});

it('deletes a legacy area once no non-final order depends on it', function () {
    [$user, $hub] = serviceAreaHub();
    $legacy = $hub->serviceAreas()->create([
        'province' => 'Legacy Province',
        'municipality' => 'Legacy City',
    ]);
    $legacy->barangays()->create(['barangay' => 'Gamma']);
    $seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);

    $order = serviceAreaOrder($seller, $hub, 'Legacy Province', 'Legacy City', 'Gamma');
    $order->update(['status' => Order::STATUS_COMPLETED]);

    $this->actingAs($user)
        ->delete(route('logistics.areas.destroy', $legacy))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('hub_service_areas', ['id' => $legacy->id]);
});

it('does not let a hub delete another hub area', function () {
    [$user] = serviceAreaHub();
    [, $otherHub] = serviceAreaHub();
    $otherArea = $otherHub->serviceAreas()->create([
        'province' => $otherHub->province,
        'municipality' => $otherHub->municipality,
    ]);

    $this->actingAs($user)
        ->delete(route('logistics.areas.destroy', $otherArea))
        ->assertForbidden();

    $this->assertDatabaseHas('hub_service_areas', ['id' => $otherArea->id]);
});
