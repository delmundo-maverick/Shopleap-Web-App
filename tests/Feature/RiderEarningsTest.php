<?php

use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\RiderRate;
use App\Models\ShippingRate;
use App\Models\User;
use App\Services\LogisticsReportService;
use App\Services\Notifier;
use App\Services\OrderTransitions;
use App\Services\RiderEarnings;
use App\Support\Portal;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->withoutVite();
    $this->mock(Notifier::class)->shouldReceive('orderStatusChanged')->andReturnNull();
    $this->travelTo(Carbon::parse('2026-11-04 12:00:00'));
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('logistics');
        $table->string('account_status')->default('active');
    });
    Schema::create('logistics_profiles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('business_name');
        $table->string('status')->default('approved');
        $table->timestamps();
    });
    Schema::create('seller_profiles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('business_name')->nullable();
        $table->string('municipality');
        $table->timestamps();
    });
    Schema::create('courier_profiles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('logistics_profile_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('middle_initial')->nullable();
        $table->string('barangay')->nullable();
        $table->string('status')->default('approved');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        foreach (['seller_id', 'buyer_id', 'logistics_profile_id', 'origin_hub_id', 'destination_hub_id', 'current_hub_id', 'delivery_rider_id'] as $column) {
            $table->unsignedBigInteger($column)->nullable();
        }
        foreach (['status', 'ship_province', 'ship_municipality', 'delivery_area'] as $column) {
            $table->string($column)->nullable();
        }
        $table->string('ship_barangay')->nullable();
        $table->json('shipping_address')->nullable();
        $table->decimal('total_amount', 10, 2)->default(100);
        $table->unsignedInteger('delivery_attempts')->default(0);
        $table->decimal('shipping_fee', 10, 2)->default(0);
        $table->decimal('cod_collected_amount', 10, 2)->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamp('cod_collected_at')->nullable();
        $table->timestamps();
    });
    (require database_path('migrations/2026_11_08_002023_add_earnings_snapshot_to_orders_table.php'))->up();
    (require database_path('migrations/2026_11_08_002022_create_rider_rates_table.php'))->up();
    (require database_path('migrations/2026_10_16_103012_create_shipping_rates_table.php'))->up();
    (require database_path('migrations/2026_10_02_171814_create_order_events_table.php'))->up();
    (require database_path('migrations/2026_10_02_171800_create_hub_service_areas_table.php'))->up();
    (require database_path('migrations/2026_10_19_102852_create_hub_service_area_barangays_table.php'))->up();
    Schema::create('order_items', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->integer('quantity')->default(1);
    });
    (require database_path('migrations/2026_10_31_030000_create_parcel_events_table.php'))->up();
    (require database_path('migrations/2026_10_31_010000_create_shipment_bags_table.php'))->up();
    (require database_path('migrations/2026_10_31_020000_create_shipment_bag_items_table.php'))->up();
    Schema::create('order_returns', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->string('reason');
        $table->text('details');
        $table->string('status');
        $table->timestamps();
    });
    (require database_path('migrations/2026_10_04_180045_create_announcements_table.php'))->up();
    (require database_path('migrations/2026_10_22_161655_create_notifications_table.php'))->up();
});

function earningsHub(string $name = 'Test Hub'): LogisticsProfile
{
    $user = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);

    return $user->logisticsProfile()->create(['business_name' => $name, 'status' => 'approved']);
}

function earningsOrder(LogisticsProfile $hub, array $attributes = []): Order
{
    $order = new Order;
    $order->forceFill(array_merge(['logistics_profile_id' => $hub->id, 'origin_hub_id' => $hub->id, 'destination_hub_id' => $hub->id, 'current_hub_id' => $hub->id,
        'status' => Order::STATUS_DELIVERED, 'ship_province' => 'Cebu', 'ship_municipality' => 'Cebu City', 'delivery_attempts' => 0,
        'delivered_at' => now(), 'cod_collected_at' => now(), 'cod_collected_amount' => 150, 'shipping_fee' => 40,
        'created_at' => now()->subMonths(2)], $attributes))->save();

    return $order;
}

function buildEarningsReport(LogisticsProfile $hub, string $type, array $filters = []): array
{
    return app(LogisticsReportService::class)->build($hub->id, now()->startOfDay(), now()->endOfDay(), $type, $filters);
}

function earningsRider(LogisticsProfile $hub): CourierProfile
{
    $user = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);

    return CourierProfile::create(['user_id' => $user->id, 'logistics_profile_id' => $hub->id,
        'first_name' => 'Test', 'last_name' => 'Rider', 'barangay' => 'Lahug', 'status' => 'approved', 'is_active' => true]);
}

function earningsRate(LogisticsProfile $hub, string $amount = '20.00'): RiderRate
{
    return RiderRate::factory()->forHub($hub)->create(['amount' => $amount]);
}

function payableOrder(LogisticsProfile $hub, CourierProfile $rider, array $attributes = []): Order
{
    return earningsOrder($hub, array_merge(['delivery_rider_id' => $rider->id, 'status' => Order::STATUS_OUT_FOR_DELIVERY,
        'delivered_at' => null, 'cod_collected_at' => null, 'cod_collected_amount' => null, 'ship_barangay' => 'Lahug'], $attributes));
}

function coverEarningsAddress(LogisticsProfile $hub, CourierProfile $rider): void
{
    $hub->serviceAreas()->create(['province' => 'Cebu', 'municipality' => 'Cebu City', 'rider_id' => $rider->id]);
}

it('appends hub rates with their creator without changing history or other hubs', function () {
    $hub = earningsHub();
    $other = earningsHub('Other hub');
    earningsRate($other, '15.00');
    $this->actingAs($hub->user)->post(route('logistics.rider-rate.store'), ['amount' => '20.25', 'logistics_profile_id' => $other->id])->assertSessionHasNoErrors();
    $first = app(RiderEarnings::class)->currentRate($hub->id);
    $this->travel(1)->seconds();
    $this->post(route('logistics.rider-rate.store'), ['amount' => 0])->assertSessionHasNoErrors();
    expect($first->fresh()->amount)->toBe('20.25')
        ->and($first->created_by)->toBe($hub->user_id)
        ->and(app(RiderEarnings::class)->currentRate($hub->id)->amount)->toBe('0.00')
        ->and(RiderRate::where('logistics_profile_id', $hub->id)->count())->toBe(2)
        ->and(app(RiderEarnings::class)->currentRate($other->id)->amount)->toBe('15.00');
    $this->get(route('logistics.rider-rate.index'))->assertOk()->assertSee('40.00')->assertSee('20.25')->assertDontSee('15.00');
    expect(fn () => $first->update(['amount' => '1.00']))->toThrow(LogicException::class);
});

it('rejects invalid rates including equality to the current lowest shipping fee', function (mixed $amount) {
    $hub = earningsHub();
    $this->actingAs($hub->user)->postJson(route('logistics.rider-rate.store'), ['amount' => $amount])
        ->assertUnprocessable()->assertJsonValidationErrors('amount');
    expect(RiderRate::count())->toBe(0);
})->with([null, '', -1, 'abc', '1.001', '40.00', '40.01', '999999999']);

it('reads the lowest fee at save time and refuses rates when no shipping fees exist', function () {
    $hub = earningsHub();
    ShippingRate::where('zone', ShippingRate::ZONE_SAME_MUNICIPALITY)->update(['fee' => 10]);
    $this->actingAs($hub->user)->postJson(route('logistics.rider-rate.store'), ['amount' => '10.00'])->assertUnprocessable();
    ShippingRate::query()->delete();
    $this->postJson(route('logistics.rider-rate.store'), ['amount' => '0.00'])->assertUnprocessable();
});

it('snapshots delivery only once even with stale models rate changes and repeated COD amounts', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    earningsRate($hub, '20.25');
    $order = payableOrder($hub, $rider);
    $stale = $order->fresh();
    $transitions = app(OrderTransitions::class);
    expect($transitions->delivered($order, $hub->user_id, 140))->toBeTrue();
    $original = app(RiderEarnings::class)->earning($order);
    $this->travel(1)->minutes();
    earningsRate($hub, '30.00');
    expect($transitions->delivered($stale, $hub->user_id, 999))->toBeTrue();
    expect(app(RiderEarnings::class)->earning($stale))->toBe($original)
        ->and($stale->rider_pay)->toBe('20.25')->and($stale->delivered_shipping_fee)->toBe('40.00')
        ->and($stale->earning_hub_id)->toBe($hub->id)->and($stale->earning_courier_id)->toBe($rider->id)
        ->and($stale->cod_collected_amount)->toBe('140.00')
        ->and($order->parcelEvents()->where('event_type', ParcelEvent::TYPE_DELIVERED)->count())->toBe(1)
        ->and($order->events()->count())->toBe(2);
});

it('rolls back the status and snapshot together if recording a delivery event fails', function () {
    $hub = earningsHub();
    $order = payableOrder($hub, earningsRider($hub));
    earningsRate($hub);
    Schema::drop('parcel_events');
    expect(fn () => app(OrderTransitions::class)->delivered($order))->toThrow(QueryException::class);
    expect($order->fresh()->status)->toBe(Order::STATUS_OUT_FOR_DELIVERY)
        ->and($order->fresh()->rider_pay)->toBeNull()->and($order->fresh()->earnings_recorded_at)->toBeNull()
        ->and($order->events()->count())->toBe(0);
});

it('never grants pay for failed cancelled undelivered or legacy parcels', function (string $status) {
    $hub = earningsHub();
    $order = payableOrder($hub, earningsRider($hub), ['status' => $status]);
    earningsRate($hub);
    app(OrderTransitions::class)->delivered($order);
    expect($order->fresh()->rider_pay)->toBeNull()->and($order->fresh()->earnings_recorded_at)->toBeNull();
})->with([Order::STATUS_DELIVERY_FAILED, Order::STATUS_CANCELLED, Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);

it('preserves a missing rate on retries and distinguishes a zero rate from no rate', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    $order = payableOrder($hub, $rider);
    app(OrderTransitions::class)->delivered($order);
    expect(app(RiderEarnings::class)->earning($order))->toBeNull()->and($order->earnings_recorded_at)->not->toBeNull();
    earningsRate($hub, '0.00');
    app(OrderTransitions::class)->delivered($order);
    expect($order->rider_pay)->toBeNull();
    $next = payableOrder($hub, $rider);
    app(OrderTransitions::class)->delivered($next);
    expect(app(RiderEarnings::class)->earning($next)['amount'])->toBe('0.00');
});

it('uses the holding hubs rate rather than the origin hubs rate', function () {
    $origin = earningsHub();
    $destination = earningsHub('Destination');
    earningsRate($origin, '10.00');
    earningsRate($destination, '25.00');
    $order = payableOrder($origin, earningsRider($destination), ['current_hub_id' => $destination->id, 'destination_hub_id' => $destination->id]);
    app(OrderTransitions::class)->delivered($order);
    expect($order->rider_pay)->toBe('25.00')->and($order->earning_hub_id)->toBe($destination->id);
});

it('creates the same snapshot through the web and returns it from authenticated API retries', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    earningsRate($hub);
    $order = payableOrder($hub, $rider);
    $this->actingAs($hub->user)->post(route('logistics.deliveries.delivered', $order))->assertSessionHasNoErrors();
    expect($order->fresh()->rider_pay)->toBe('20.00');
    Sanctum::actingAs($rider->user);
    $first = $this->postJson(Portal::url('main', 'api/courier/deliveries/'.$order->id.'/delivered'), ['amount' => 140])->assertOk()->assertJsonPath('earning.amount', '20.00')->assertJsonPath('earning.currency', 'PHP')->json('earning');
    $this->postJson(Portal::url('main', 'api/courier/deliveries/'.$order->id.'/delivered'), ['amount' => 1])->assertOk()->assertJsonPath('earning', $first);
});

it('creates snapshots from the rider API and returns null if no rate exists', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    coverEarningsAddress($hub, $rider);
    $order = payableOrder($hub, $rider);
    Sanctum::actingAs($rider->user);
    $this->postJson(Portal::url('main', 'api/courier/deliveries/'.$order->id.'/delivered'), ['amount' => 140])->assertOk()->assertJsonPath('earning', null);
    earningsRate($hub, '12.50');
    $next = payableOrder($hub, $rider);
    $this->postJson('/api/courier/deliveries/'.$next->id.'/delivered', ['amount' => 140])->assertOk()->assertJsonPath('earning.amount', '12.50');
    expect($next->fresh()->earning_courier_id)->toBe($rider->id);
});

it('keeps hub reports and CSV scoped and computes totals from frozen snapshots', function () {
    $hub = earningsHub();
    $other = earningsHub('Foreign');
    $rider = earningsRider($hub);
    earningsRate($hub, '12.50');
    $included = payableOrder($hub, $rider);
    app(OrderTransitions::class)->delivered($included);
    earningsRate($other, '5.00');
    $foreign = payableOrder($other, earningsRider($other));
    app(OrderTransitions::class)->delivered($foreign);
    $included->forceFill(['shipping_fee' => 999, 'delivery_rider_id' => $foreign->delivery_rider_id, 'destination_hub_id' => $other->id])->save();
    $this->actingAs($hub->user);
    $params = ['type' => 'shipping_earnings', 'preset' => 'today'];
    $this->get(route('logistics.reports.index', $params))->assertOk()->assertViewHas('report', function ($report) {
        expect(array_column($report['summary'], 'value'))->toBe([1, 'PHP 40.00', 'PHP 12.50', 'PHP 27.50']);
        expect($report['secondary'][0]['barangay'])->toBe('Lahug')->and($report['chart']['datasets'][0]['data'])->toBe([1]);

        return true;
    });
    $csv = $this->get(route('logistics.reports.export.csv', $params))->assertOk()->streamedContent();
    expect($csv)->toContain('#'.$included->id)->toContain('12.50')->toContain('27.50')->not->toContain('#'.$foreign->id);
});

it('paginates earnings parcels in the database and exports every filtered delivery', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    $other = earningsRider($hub);
    earningsRate($hub);
    for ($i = 0; $i < 26; $i++) {
        app(OrderTransitions::class)->delivered(payableOrder($hub, $rider));
    }
    app(OrderTransitions::class)->delivered(payableOrder($hub, $other));
    $params = ['type' => 'shipping_earnings', 'preset' => 'today', 'rider_id' => $rider->id];
    $this->actingAs($hub->user)->get(route('logistics.reports.index', $params))->assertOk()
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 25 && $rows->total() === 26);
    $this->get(route('logistics.reports.index', $params + ['page' => 2]))->assertOk()
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
    $csv = $this->get(route('logistics.reports.export.csv', $params))->streamedContent();
    expect(substr_count($csv, '#'))->toBe(26);
});

it('returns only the authenticated riders snapshot earnings across all earnings surfaces', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    $other = earningsRider($hub);
    $foreignHub = earningsHub('Foreign');
    earningsRate($hub, '12.50');
    $own = payableOrder($hub, $rider);
    app(OrderTransitions::class)->delivered($own);
    app(OrderTransitions::class)->delivered(payableOrder($hub, $other));
    earningsRate($foreignHub);
    app(OrderTransitions::class)->delivered(payableOrder($foreignHub, earningsRider($foreignHub)));
    earningsRate($hub, '25.00');
    Sanctum::actingAs($rider->user);
    $this->getJson('/api/courier/earnings/rate')->assertOk()->assertJsonPath('amount', '25.00')->assertJsonPath('currency', 'PHP');
    foreach (['/api/courier/earnings', '/api/courier/earnings/summary'] as $path) {
        $this->getJson($path.'?preset=today&courier_id='.$other->id)->assertOk()
            ->assertJsonPath('deliveries', 1)->assertJsonPath('total_earned', '12.50')->assertJsonPath('daily.0.earned', '12.50');
    }
    $this->getJson('/api/courier/earnings/history?preset=today')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.order_id', $own->id)->assertJsonPath('data.0.amount', '12.50')->assertJsonPath('data.0.currency', 'PHP');
    $this->getJson('/api/courier/dashboard')->assertOk()->assertJsonPath('earned_today', '12.50');
    $this->getJson('/api/courier/history')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.earning', '12.50');
    $this->postJson('/api/courier/deliveries/'.Order::where('delivery_rider_id', $other->id)->first()->id.'/delivered', ['amount' => 140])->assertForbidden();
});

it('applies matching date boundaries on the hub report and rider summary', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    earningsRate($hub);
    app(OrderTransitions::class)->delivered(payableOrder($hub, $rider));
    $yesterday = payableOrder($hub, $rider);
    app(OrderTransitions::class)->delivered($yesterday);
    $yesterday->forceFill(['delivered_at' => now()->subDay()])->save();
    Sanctum::actingAs($rider->user);
    $this->getJson('/api/courier/earnings/summary?preset=today')->assertJsonPath('total_earned', '20.00');
    $this->getJson('/api/courier/earnings/summary?preset=7d')->assertJsonPath('total_earned', '40.00');
    $date = now()->toDateString();
    $this->getJson('/api/courier/earnings/summary?preset=custom&from='.$date.'&to='.$date)->assertOk()->assertJsonPath('total_earned', '20.00');
    $this->getJson('/api/courier/earnings/summary?preset=custom')->assertUnprocessable();
    expect(buildEarningsReport($hub, 'shipping_earnings')['summary'][2]['value'])->toBe('PHP 20.00');
});

it('shows missing snapshots without estimating earnings or backfilling orders', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    $legacy = earningsOrder($hub, ['delivery_rider_id' => $rider->id]);
    earningsRate($hub);
    $report = buildEarningsReport($hub, 'shipping_earnings');
    expect($report['rows']->first()['pay'])->toBe('-')->and($report['rows']->first()['fees'])->toBe('-')->and($report['rows']->first()['margin'])->toBe('-');
    expect($legacy->fresh()->earnings_recorded_at)->toBeNull();
    Sanctum::actingAs($rider->user);
    $this->getJson('/api/courier/earnings/summary?preset=today')->assertJsonPath('total_earned', '0.00')->assertJsonPath('recorded_earnings', 0);
    $this->getJson('/api/courier/earnings/history?preset=today')->assertJsonPath('data.0.amount', null);
});

it('requires an approved courier for earnings and an approved hub for rates', function () {
    $this->getJson('/api/courier/earnings/rate')->assertUnauthorized();
    $buyer = User::factory()->create(['role' => 'buyer']);
    Sanctum::actingAs($buyer);
    $this->getJson('/api/courier/earnings/summary')->assertForbidden();
    $this->actingAs($buyer, 'web')->withSession(['portal_host' => Portal::host('logistics')])->postJson(route('logistics.rider-rate.store'), ['amount' => 10])->assertForbidden();
});

it('rechecks courier and hub ownership under the delivery lock', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    $other = earningsRider($hub);
    $order = payableOrder($hub, $rider);
    earningsRate($hub);
    $stale = $order->fresh();
    $order->forceFill(['delivery_rider_id' => $other->id])->save();
    expect(app(OrderTransitions::class)->delivered($stale, expectedCourierId: $rider->id, expectedHubId: $hub->id))->toBeFalse();
    expect($order->fresh()->earnings_recorded_at)->toBeNull();
});

it('retains historical courier filtering after the courier moves to another hub', function () {
    $hub = earningsHub();
    $other = earningsHub('Other');
    $rider = earningsRider($hub);
    earningsRate($hub);
    $order = payableOrder($hub, $rider);
    app(OrderTransitions::class)->delivered($order);
    $rider->update(['logistics_profile_id' => $other->id]);
    $params = ['type' => 'shipping_earnings', 'preset' => 'today', 'rider_id' => $rider->id];
    $this->actingAs($hub->user)->get(route('logistics.reports.index', $params))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 1);
    $this->actingAs($other->user)->get(route('logistics.reports.index', $params))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->total() === 0);
});

it('reverses the new snapshot migration without touching existing order amounts', function () {
    $hub = earningsHub();
    $legacy = earningsOrder($hub);
    $migration = require database_path('migrations/2026_11_08_002023_add_earnings_snapshot_to_orders_table.php');
    $migration->down();
    expect(Schema::hasColumn('orders', 'rider_pay'))->toBeFalse()->and($legacy->fresh()->shipping_fee)->toBe('40.00');
    $migration->up();
    expect($legacy->fresh()->rider_pay)->toBeNull()->and($legacy->fresh()->earnings_recorded_at)->toBeNull();
});

it('shows an unknown net margin when a recorded delivery has no rider rate', function () {
    $hub = earningsHub();
    $rider = earningsRider($hub);
    app(OrderTransitions::class)->delivered(payableOrder($hub, $rider));
    earningsRate($hub);
    app(OrderTransitions::class)->delivered(payableOrder($hub, $rider));
    $report = buildEarningsReport($hub, 'shipping_earnings');
    expect(array_column($report['summary'], 'value'))->toBe([2, 'PHP 80.00', 'PHP 20.00', '-']);
});

it('renders the earnings empty states and preserves working PDF exports', function () {
    $hub = earningsHub();
    $this->actingAs($hub->user);
    $params = ['type' => 'shipping_earnings', 'preset' => 'today'];
    $this->get(route('logistics.reports.index', $params))->assertOk()->assertSee('No matching records')->assertSee('No rider deliveries in this period.');
    $this->get(route('logistics.reports.export.pdf', $params))->assertOk()->assertHeader('content-type', 'application/pdf');
    earningsRate($hub);
    app(OrderTransitions::class)->delivered(payableOrder($hub, earningsRider($hub)));
    $this->get(route('logistics.reports.export.pdf', $params))->assertOk()->assertHeader('content-type', 'application/pdf');
});
