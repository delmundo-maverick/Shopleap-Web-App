<?php

use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\ShipmentBag;
use App\Models\User;
use App\Services\LogisticsReportService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
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
        $table->unsignedInteger('delivery_attempts')->default(0);
        $table->decimal('shipping_fee', 10, 2)->default(0);
        $table->decimal('cod_collected_amount', 10, 2)->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamp('cod_collected_at')->nullable();
        $table->timestamps();
    });
    (require database_path('migrations/2026_11_08_002023_add_earnings_snapshot_to_orders_table.php'))->up();
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

function reportHub(string $name = 'Test Hub'): LogisticsProfile
{
    $user = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);

    return $user->logisticsProfile()->create(['business_name' => $name, 'status' => 'approved']);
}

function reportOrder(LogisticsProfile $hub, array $attributes = []): Order
{
    $order = new Order;
    $order->forceFill(array_merge(['logistics_profile_id' => $hub->id, 'origin_hub_id' => $hub->id, 'destination_hub_id' => $hub->id, 'current_hub_id' => $hub->id,
        'status' => Order::STATUS_DELIVERED, 'ship_province' => 'Cebu', 'ship_municipality' => 'Cebu City', 'delivery_attempts' => 0,
        'delivered_at' => now(), 'cod_collected_at' => now(), 'cod_collected_amount' => 150, 'shipping_fee' => 40,
        'created_at' => now()->subMonths(2)], $attributes))->save();

    return $order;
}

function buildLogisticsReport(LogisticsProfile $hub, string $type, array $filters = []): array
{
    return app(LogisticsReportService::class)->build($hub->id, now()->startOfDay(), now()->endOfDay(), $type, $filters);
}

it('builds every report with a consistent empty result', function (string $type) {
    $report = buildLogisticsReport(reportHub(), $type);
    expect($report)->toHaveKeys(['columns', 'rows', 'notes', 'summary', 'chart', 'hasData']);
    expect($report['hasData'])->toBeFalse();
})->with(['shipment_summary', 'delivery_performance', 'failed_returns', 'rider_performance', 'cod_remittance', 'shipping_earnings', 'area_route', 'hub_throughput', 'damaged_lost']);

it('counts older orders by delivery date and distinguishes reattempts from lateness', function () {
    $hub = reportHub();
    $other = reportHub('Other');
    reportOrder($hub);
    reportOrder($hub, ['delivery_attempts' => 1]);
    reportOrder($other);
    reportOrder($hub, ['delivered_at' => now()->subDays(3), 'created_at' => now()]);
    $report = buildLogisticsReport($hub, 'delivery_performance');
    expect($report['summary'][0]['value'])->toBe(2)->and($report['summary'][1]['value'])->toBe('50%');
    expect($report['rows'])->toHaveCount(2);
    expect(buildLogisticsReport($hub, 'shipment_summary')['summary'][0]['value'])->toBe(1);
    expect(app(LogisticsReportService::class)->build(null, now(), now(), 'shipment_summary')['hasData'])->toBeFalse();
});

it('counts only recorded cash and earnings at the delivery hub without inventing fees', function () {
    $origin = reportHub();
    $destination = reportHub();
    reportOrder($origin, ['destination_hub_id' => $destination->id, 'cod_collected_amount' => 123.45, 'delivery_attempts' => 3]);
    reportOrder($destination, ['cod_collected_at' => null, 'cod_collected_amount' => null]);
    expect(buildLogisticsReport($origin, 'shipping_earnings')['hasData'])->toBeFalse();
    expect(buildLogisticsReport($destination, 'shipping_earnings')['summary'][0]['value'])->toBe(2);
    expect(buildLogisticsReport($destination, 'shipping_earnings')['summary'][1]['value'])->toBe('-');
    expect(buildLogisticsReport($destination, 'cod_remittance')['summary'][0]['value'])->toBe('PHP 123.45');
});

it('uses recorded failed attempts and missing events rather than cancellation status', function () {
    $hub = reportHub();
    $other = reportHub();
    $delivered = reportOrder($hub);
    $cancelled = reportOrder($hub, ['status' => Order::STATUS_CANCELLED]);
    $foreign = reportOrder($other);
    foreach ([[ParcelEvent::TYPE_DELIVERY_ATTEMPT, $delivered], [ParcelEvent::TYPE_BAG_MISSING, $delivered]] as [$type, $order]) {
        ParcelEvent::create(['order_id' => $order->id, 'hub_id' => $hub->id, 'event_type' => $type, 'note' => 'Actual incident']);
    }
    ParcelEvent::create(['order_id' => $foreign->id, 'hub_id' => $other->id, 'event_type' => ParcelEvent::TYPE_BAG_MISSING]);
    expect(buildLogisticsReport($hub, 'failed_returns')['rows'])->toHaveCount(1);
    expect(buildLogisticsReport($hub, 'damaged_lost')['rows'])->toHaveCount(1);
    DB::table('order_returns')->insert(['order_id' => $delivered->id, 'reason' => 'damaged', 'details' => 'Broken packaging', 'status' => 'requested', 'created_at' => now(), 'updated_at' => now()]);
    expect(buildLogisticsReport($hub, 'damaged_lost')['rows'])->toHaveCount(2);
});

it('applies origin destination seller and rider filters to events and orders', function () {
    $hub = reportHub();
    $seller = User::factory()->create(['role' => 'seller']);
    $seller->sellerProfile()->create(['municipality' => 'Pickup City']);
    $rider = CourierProfile::create(['logistics_profile_id' => $hub->id, 'first_name' => 'Test', 'last_name' => 'Rider']);
    $order = reportOrder($hub, ['seller_id' => $seller->id, 'delivery_rider_id' => $rider->id]);
    ParcelEvent::create(['order_id' => $order->id, 'hub_id' => $hub->id, 'rider_id' => $rider->id, 'event_type' => ParcelEvent::TYPE_DELIVERY_ATTEMPT]);
    $filters = ['origin_area' => 'Pickup City', 'destination_area' => 'Cebu City', 'seller_id' => $seller->id, 'rider_id' => $rider->id];
    expect(buildLogisticsReport($hub, 'delivery_performance', $filters)['rows'])->toHaveCount(1);
    expect(buildLogisticsReport($hub, 'failed_returns', $filters)['rows'])->toHaveCount(1);
    expect(buildLogisticsReport($hub, 'delivery_performance', ['origin_area' => 'Cebu City'])['rows'])->toBeEmpty();
});

it('counts throughput by event and bag dates even for orders created earlier', function () {
    $hub = reportHub();
    $other = reportHub();
    $order = reportOrder($hub);
    foreach ([ParcelEvent::TYPE_BAG_RECEIVED, ParcelEvent::TYPE_SORTED, ParcelEvent::TYPE_BAG_DISPATCHED] as $type) {
        ParcelEvent::create(['order_id' => $order->id, 'hub_id' => $hub->id, 'event_type' => $type]);
    }
    $bag = ShipmentBag::create(['code' => 'TEST-BAG', 'from_hub_id' => $hub->id, 'to_hub_id' => $other->id, 'status' => ShipmentBag::STATUS_IN_TRANSIT, 'dispatched_at' => now(), 'created_by' => $hub->user_id]);
    $bag->items()->create(['order_id' => $order->id]);
    $bag->forceFill(['created_at' => now()->subMonths(2)])->save();
    $report = buildLogisticsReport($hub, 'hub_throughput');
    expect($report['summary'][0]['value'])->toBe(1)->and($report['summary'][1]['value'])->toBe(1)->and($report['summary'][2]['value'])->toBe(1);
    expect($report['rows'][0]['code'])->toBe('TEST-BAG');
});

it('validates report parameters and forbids a foreign rider filter', function () {
    $hub = reportHub();
    $other = reportHub();
    $foreign = CourierProfile::create(['logistics_profile_id' => $other->id, 'first_name' => 'Other', 'last_name' => 'Rider']);
    $this->actingAs($hub->user)->getJson(route('logistics.reports.index', ['rider_id' => $foreign->id]))->assertUnprocessable()->assertJsonValidationErrors('rider_id');
    $this->getJson(route('logistics.reports.index', ['preset' => 'custom', 'from' => '2024-01-01', 'to' => '2026-11-04']))->assertUnprocessable()->assertJsonValidationErrors('to');
    $this->getJson(route('logistics.reports.index', ['type' => 'unknown']))->assertUnprocessable()->assertJsonValidationErrors('type');
    $this->getJson(route('logistics.reports.index', ['payment_type' => 'Prepaid']))->assertUnprocessable()->assertJsonValidationErrors('payment_type');
});

it('renders filters and preserves them in export links', function () {
    $hub = reportHub();
    reportOrder($hub);
    $this->actingAs($hub->user)->get(route('logistics.reports.index', ['type' => 'delivery_performance', 'destination_area' => 'Cebu City']))
        ->assertOk()->assertSee('First-attempt success')->assertSee('destination_area=Cebu%20City')->assertSee('data-report-filters', false);
});

it('exports only the filtered rows with the same report dates', function () {
    $hub = reportHub();
    $included = reportOrder($hub);
    $excluded = reportOrder($hub, ['ship_municipality' => 'Other City']);
    $this->actingAs($hub->user);
    $params = ['type' => 'delivery_performance', 'preset' => 'today', 'destination_area' => 'Cebu City'];
    $response = $this->get(route('logistics.reports.export.csv', $params))->assertOk();
    $csv = $response->streamedContent();
    expect($csv)->toContain('#'.$included->id)->not->toContain('#'.$excluded->id);
    $this->get(route('logistics.reports.export.pdf', $params))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('paginates the page while exporting every matching row', function () {
    $hub = reportHub();
    for ($i = 0; $i < 26; $i++) {
        reportOrder($hub);
    }
    $response = $this->actingAs($hub->user)->get(route('logistics.reports.index', ['type' => 'delivery_performance', 'preset' => 'today']));
    $response->assertOk()->assertViewHas('rows', fn ($rows) => $rows->count() === 25 && $rows->total() === 26);
    $this->get(route('logistics.reports.index', ['type' => 'delivery_performance', 'preset' => 'today', 'page' => 2]))->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
    $csv = $this->get(route('logistics.reports.export.csv', ['type' => 'delivery_performance', 'preset' => 'today']))->streamedContent();
    expect(substr_count($csv, '#'))->toBe(26);
});

it('protects spreadsheet exports from formula-like incident notes', function () {
    $hub = reportHub();
    $order = reportOrder($hub);
    ParcelEvent::create(['order_id' => $order->id, 'hub_id' => $hub->id, 'event_type' => ParcelEvent::TYPE_DELIVERY_ATTEMPT, 'note' => '=1+1']);
    $csv = $this->actingAs($hub->user)->get(route('logistics.reports.export.csv', ['type' => 'failed_returns', 'preset' => 'today']))->streamedContent();
    expect($csv)->toContain("'=1+1");
});

it('includes zero-activity days in daily charts', function () {
    $hub = reportHub();
    reportOrder($hub);
    $report = app(LogisticsReportService::class)->build($hub->id, now()->subDays(2)->startOfDay(), now()->endOfDay(), 'delivery_performance');
    expect($report['chart']['datasets'][0]['data'])->toBe([0, 0, 1]);
});

it('keeps same-name cities in separate provinces and groups rider activity', function () {
    $hub = reportHub();
    $rider = CourierProfile::create(['logistics_profile_id' => $hub->id, 'first_name' => 'Jane', 'last_name' => 'Rider']);
    reportOrder($hub, ['created_at' => now(), 'delivery_rider_id' => $rider->id, 'ship_province' => 'Cebu', 'ship_municipality' => 'San Jose']);
    reportOrder($hub, ['created_at' => now(), 'delivery_rider_id' => $rider->id, 'ship_province' => 'Batangas', 'ship_municipality' => 'San Jose']);
    expect(buildLogisticsReport($hub, 'area_route')['rows'])->toHaveCount(2);
    $riders = buildLogisticsReport($hub, 'rider_performance');
    expect($riders['rows'])->toHaveCount(1);
    expect($riders['rows'][0]['delivered'])->toBe(2);
});
