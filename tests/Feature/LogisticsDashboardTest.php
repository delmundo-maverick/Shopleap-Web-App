<?php

use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\Order;
use App\Models\ParcelEvent;
use App\Models\ShipmentBag;
use App\Models\SortingSession;
use App\Models\User;
use App\Services\LogisticsDashboardService;
use App\Support\NavigationIcons;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-11-06 12:00:00'));
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('logistics');
        $table->string('account_status')->default('active');
    });
    Schema::create('logistics_profiles', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('business_name');
        $table->string('status')->default('approved');
        $table->string('municipality')->default('Cebu City');
        $table->string('province')->default('Cebu');
        $table->timestamps();
    });
    Schema::create('courier_profiles', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('logistics_profile_id');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('barangay')->nullable();
        $table->string('status')->default('pending');
        $table->boolean('is_active')->default(false);
        $table->timestamps();
    });
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        foreach (['logistics_profile_id', 'current_hub_id', 'pickup_rider_id', 'delivery_rider_id'] as $column) {
            $table->unsignedBigInteger($column)->nullable();
        }
        $table->string('status');
        $table->string('ship_barangay')->nullable();
        $table->string('ship_municipality')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamps();
    });
    Schema::create('hub_service_areas', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('logistics_profile_id');
    });
    foreach ([
        '2026_10_31_030000_create_parcel_events_table.php',
        '2026_10_31_010000_create_shipment_bags_table.php',
        '2026_10_31_020000_create_shipment_bag_items_table.php',
        '2026_10_28_163144_create_sorting_sessions_table.php',
        '2026_10_28_163615_create_sorting_scans_table.php',
        '2026_10_04_180045_create_announcements_table.php',
        '2026_10_22_161655_create_notifications_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->hub = dashboardHub('Cebu Operations Hub');
    $this->actingAs($this->hub->user);
});

function dashboardHub(string $name = 'Other Hub'): LogisticsProfile
{
    $user = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);

    return $user->logisticsProfile()->create(['business_name' => $name, 'status' => 'approved']);
}

function dashboardOrder(LogisticsProfile $hub, string $status = Order::STATUS_AT_SORTING_CENTER, array $attributes = []): Order
{
    $order = new Order;
    $order->forceFill([
        'logistics_profile_id' => $hub->id, 'current_hub_id' => $hub->id, 'status' => $status,
        'ship_barangay' => 'Lahug', 'ship_municipality' => 'Cebu City', ...$attributes,
    ])->save();

    return $order;
}

function dashboardEvent(Order $order, LogisticsProfile $hub, string $date, string $type = ParcelEvent::TYPE_STATUS_CHANGED, ?string $status = null): ParcelEvent
{
    $event = new ParcelEvent([
        'order_id' => $order->id, 'hub_id' => $hub->id, 'event_type' => $type, 'to_status' => $status ?? $order->status,
    ]);
    $event->created_at = Carbon::parse($date);
    $event->updated_at = Carbon::parse($date);
    $event->save();

    return $event;
}

function dashboardBag(LogisticsProfile $from, LogisticsProfile $to, string $status = ShipmentBag::STATUS_OPEN): ShipmentBag
{
    return ShipmentBag::create([
        'code' => 'BAG-'.fake()->unique()->numerify('######'), 'from_hub_id' => $from->id,
        'to_hub_id' => $to->id, 'status' => $status, 'created_by' => $from->user_id,
    ]);
}

function dashboardData(LogisticsProfile $hub, string $range = '7d', string $queue = 'all'): array
{
    return app(LogisticsDashboardService::class)->build($hub, $range, $queue);
}

it('renders the redesigned empty dashboard with accessible controls and real empty states', function () {
    $this->get(route('logistics.dashboard'))->assertOk()->assertSee('Cebu Operations Hub')
        ->assertSee('Needs attention')->assertSee('No sorting session running')
        ->assertSee('No active riders')->assertSee('Nothing in this queue')->assertSee('No parcel activity')
        ->assertSee('Dashboard date range')->assertSee('aria-pressed="true"', false)
        ->assertSee('2026')->assertSee('data-chart-data=', false);
});

it('scopes every workload metric and bag quantity to the signed-in hub', function () {
    $other = dashboardHub();
    dashboardOrder($this->hub);
    dashboardOrder($this->hub, Order::STATUS_OUT_FOR_DELIVERY);
    dashboardOrder($this->hub, Order::STATUS_ASSIGNED_TO_RIDER);
    dashboardOrder($this->hub, Order::STATUS_DELIVERED, ['delivered_at' => now()]);
    dashboardOrder($other);
    dashboardOrder($other, Order::STATUS_OUT_FOR_DELIVERY);
    $incoming = dashboardBag($other, $this->hub, ShipmentBag::STATUS_IN_TRANSIT);
    $incoming->items()->create(['order_id' => dashboardOrder($other)->id]);
    $incoming->items()->create(['order_id' => dashboardOrder($other)->id, 'received_at' => now()]);
    dashboardBag($this->hub, $other);
    dashboardBag($other, $other);
    CourierProfile::create(['logistics_profile_id' => $this->hub->id, 'first_name' => 'Pending', 'last_name' => 'Rider']);
    CourierProfile::create(['logistics_profile_id' => $other->id, 'first_name' => 'Foreign', 'last_name' => 'Rider']);
    $data = dashboardData($this->hub, 'today');
    expect(array_column($data['cards'], 'value'))->toBe([1, 1, 1, 1, 1, 1]);
    foreach ($data['cards'] as $card) {
        expect($card['href'])->toContain('range=today', 'queue='.$card['queue'], '#work-queue');
    }
});

it('deduplicates handled parcels, excludes automatic stale flags and retains zero days', function () {
    $order = dashboardOrder($this->hub);
    dashboardEvent($order, $this->hub, '2026-11-06 08:00:00');
    dashboardEvent($order, $this->hub, '2026-11-06 09:00:00', ParcelEvent::TYPE_BAG_RECEIVED);
    dashboardEvent($order, $this->hub, '2026-11-05 09:00:00');
    $flagged = dashboardOrder($this->hub);
    dashboardEvent($flagged, $this->hub, '2026-11-06 09:00:00', ParcelEvent::TYPE_STALE);
    dashboardEvent($flagged, $this->hub, '2026-11-06 13:00:00');
    $foreign = dashboardHub();
    dashboardEvent(dashboardOrder($foreign), $foreign, '2026-11-06 09:00:00');
    $data = dashboardData($this->hub);
    expect($data['chartData']['daily']['values'])->toBe([0, 0, 0, 0, 0, 1, 1]);
    expect($data['handledTotal'])->toBe(1)->and($data['chartData']['statuses']['values'])->toBe([1]);
    expect($data['cards'][0]['trend'])->toContain('Arrivals today: 1 (0 vs yesterday)');
    expect($data['cards'][1]['trend'])->toContain('Received today: 1 (+1 vs yesterday)');
});

it('applies each date range to recorded activity without hiding current backlogs', function (string $range, int $days, int $deliveries) {
    dashboardOrder($this->hub);
    dashboardOrder($this->hub, Order::STATUS_DELIVERED, ['delivered_at' => now()]);
    dashboardOrder($this->hub, Order::STATUS_COMPLETED, ['delivered_at' => now()->subDays(3)]);
    dashboardOrder($this->hub, Order::STATUS_DELIVERED, ['delivered_at' => now()->subDays(20)]);
    $data = dashboardData($this->hub, $range);
    expect($data['chartData']['daily']['labels'])->toHaveCount($days);
    expect($data['cards'][0]['value'])->toBe(1)->and($data['cards'][4]['value'])->toBe($deliveries);
})->with([['today', 1, 1], ['7d', 7, 2], ['30d', 30, 3]]);

it('uses the configured stale threshold and ignores synthetic flags when detecting inactivity', function () {
    config(['logistics.stale_parcel_hours' => 36]);
    $stale = dashboardOrder($this->hub, Order::STATUS_AT_SORTING_CENTER, ['updated_at' => now()->subHours(40)]);
    dashboardEvent($stale, $this->hub, '2026-11-06 09:00:00', ParcelEvent::TYPE_STALE);
    $recent = dashboardOrder($this->hub, Order::STATUS_AT_SORTING_CENTER, ['updated_at' => now()->subHours(40)]);
    dashboardEvent($recent, $this->hub, '2026-11-06 09:00:00');
    dashboardOrder($this->hub, Order::STATUS_AT_SORTING_CENTER, ['updated_at' => now()->subHours(30)]);
    dashboardOrder($this->hub, Order::STATUS_DELIVERY_FAILED);
    dashboardOrder($this->hub, Order::STATUS_RETURNED);
    dashboardOrder(dashboardHub(), Order::STATUS_RETURNED);
    $data = dashboardData($this->hub, 'today', 'stale');
    expect(array_column($data['attention'], 'value'))->toBe([1, 0, 1, 1]);
    expect($data['staleHours'])->toBe(36)->and($data['workQueue']->pluck('reference')->all())->toBe(['Parcel #'.$stale->id]);
});

it('shows only the hubs active sorting session with aggregate scan counts', function () {
    $session = SortingSession::create(['logistics_profile_id' => $this->hub->id, 'user_id' => $this->hub->user_id, 'started_at' => now()->subHour()]);
    $session->scans()->create(['scanned_code' => 'ABC', 'result' => 'sorted', 'message' => 'Sorted']);
    $session->scans()->create(['scanned_code' => 'DEF', 'result' => 'needs_review', 'message' => 'Review']);
    $other = dashboardHub();
    SortingSession::create(['logistics_profile_id' => $other->id, 'user_id' => $this->hub->user_id, 'started_at' => now()]);
    $data = dashboardData($this->hub);
    expect($data['activeSession']->id)->toBe($session->id)->and($data['activeSession']->scans_count)->toBe(2)
        ->and($data['activeSession']->sorted_count)->toBe(1)->and($data['activeSession']->review_count)->toBe(1);
    $this->get(route('logistics.dashboard'))->assertOk()->assertSee('Resume sorting');
});

it('ranks active riders by real hub-scoped pickup and delivery workloads', function () {
    $light = CourierProfile::create(['logistics_profile_id' => $this->hub->id, 'first_name' => 'Light', 'last_name' => 'Load', 'barangay' => 'Lahug', 'status' => 'approved', 'is_active' => true]);
    $busy = CourierProfile::create(['logistics_profile_id' => $this->hub->id, 'first_name' => 'Busy', 'last_name' => 'Load', 'status' => 'approved', 'is_active' => true]);
    CourierProfile::create(['logistics_profile_id' => $this->hub->id, 'first_name' => 'Inactive', 'last_name' => 'Rider', 'status' => 'approved', 'is_active' => false]);
    dashboardOrder($this->hub, Order::STATUS_OUT_FOR_DELIVERY, ['delivery_rider_id' => $busy->id, 'pickup_rider_id' => $busy->id]);
    dashboardOrder($this->hub, Order::STATUS_PICKED_UP, ['pickup_rider_id' => $busy->id]);
    dashboardOrder($this->hub, Order::STATUS_DELIVERED, ['delivery_rider_id' => $light->id]);
    dashboardOrder(dashboardHub(), Order::STATUS_OUT_FOR_DELIVERY, ['delivery_rider_id' => $light->id]);
    $riders = dashboardData($this->hub)['riders'];
    expect($riders->modelKeys())->toBe([$light->id, $busy->id])->and($riders->pluck('carrying_count')->all())->toBe([0, 2]);
});

it('filters the work queue to exact card populations and links bags to their existing pages', function () {
    $other = dashboardHub();
    $incoming = dashboardBag($other, $this->hub, ShipmentBag::STATUS_IN_TRANSIT);
    $outgoing = dashboardBag($this->hub, $other);
    dashboardBag($this->hub, $other, ShipmentBag::STATUS_SEALED);
    dashboardOrder($this->hub);
    $inbound = dashboardData($this->hub, '7d', 'inbound')['workQueue'];
    $outbound = dashboardData($this->hub, '7d', 'outbound')['workQueue'];
    expect($inbound->pluck('reference')->all())->toBe([$incoming->code]);
    expect($inbound->first()['href'])->toBe(route('logistics.inbound.show', $incoming));
    expect($outbound->pluck('reference')->all())->toBe([$outgoing->code]);
    expect($outbound->first()['href'])->toBe(route('logistics.outbound.show', $outgoing));
    $this->get(route('logistics.dashboard', ['queue' => 'inbound']))->assertOk()->assertSee($incoming->code)->assertDontSee($outgoing->code);
});

it('limits the mixed queue and keeps query counts stable as workloads grow', function () {
    dashboardOrder($this->hub);
    DB::enableQueryLog();
    DB::flushQueryLog();
    dashboardData($this->hub);
    $small = count(DB::getQueryLog());
    for ($index = 0; $index < 20; $index++) {
        dashboardOrder($this->hub);
        CourierProfile::create(['logistics_profile_id' => $this->hub->id, 'first_name' => 'Rider', 'last_name' => (string) $index, 'status' => 'approved', 'is_active' => true]);
    }
    DB::flushQueryLog();
    $data = dashboardData($this->hub);
    $large = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($large)->toBe($small)->and($data['workQueue'])->toHaveCount(10)->and($data['riders'])->toHaveCount(6);
});

it('rejects unsupported dashboard filters', function (array $query) {
    $this->getJson(route('logistics.dashboard', $query))->assertUnprocessable();
})->with([[['range' => 'year']], [['queue' => 'foreign']], [['range' => ['7d']]]]);

it('requires an active approved logistics account', function () {
    auth()->logout();
    $this->getJson(route('logistics.dashboard'))->assertUnauthorized();
    $this->actingAs(User::factory()->create(['role' => 'seller', 'account_status' => 'active']));
    $this->getJson(route('logistics.dashboard'))->assertForbidden();
    $this->actingAs($this->hub->user);
    $this->hub->update(['status' => 'pending']);
    $this->hub->user->unsetRelation('logisticsProfile');
    $this->getJson(route('logistics.dashboard'))->assertForbidden();
});

it('uses the same registered navigation icon for shared concepts across roles', function () {
    expect(NavigationIcons::forRoute('seller.dashboard'))->toBe(NavigationIcons::forRoute('admin.dashboard'))
        ->and(NavigationIcons::forRoute('admin.dashboard'))->toBe(NavigationIcons::forRoute('logistics.dashboard'));
    expect(NavigationIcons::forRoute('seller.reports.index'))->toBe(NavigationIcons::forRoute('logistics.reports.index'));
    expect(NavigationIcons::forRoute('buyer.orders.index'))->toBe(NavigationIcons::forRoute('seller.orders.index'));
    foreach (['dashboard', 'riders.index', 'areas.index', 'pickups.index', 'incoming.index', 'outbound.index', 'inbound.index', 'sorting.index', 'sorting.sessions.index', 'deliveries.index', 'reports.index'] as $route) {
        expect(NavigationIcons::forRoute('logistics.'.$route))->not->toBeNull();
    }
});
