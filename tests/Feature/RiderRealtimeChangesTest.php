<?php

use App\Http\Controllers\Logistics\PickupController;
use App\Models\CourierProfile;
use App\Models\HubServiceArea;
use App\Models\Order;
use App\Models\User;
use App\Services\Notifier;
use App\Services\ParcelLifecycle;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    Schema::create('logistics_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('business_name');
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('courier_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('logistics_profile_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('status');
        $table->boolean('is_active');
        $table->timestamps();
    });
    Schema::create('seller_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('province');
        $table->string('municipality');
        $table->string('barangay');
        $table->timestamps();
    });
    Schema::create('hub_service_areas', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('logistics_profile_id');
        $table->foreignId('rider_id');
        $table->string('province');
        $table->string('municipality');
        $table->timestamps();
    });
    Schema::create('hub_service_area_barangays', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('hub_service_area_id');
        $table->foreignId('rider_id')->nullable();
        $table->string('barangay');
    });
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->string('order_number')->unique();
        $table->string('waybill_code')->unique();
        foreach (['seller_id', 'buyer_id', 'pickup_rider_id', 'delivery_rider_id', 'origin_hub_id', 'current_hub_id', 'logistics_profile_id'] as $column) {
            $table->foreignId($column)->nullable();
        }
        $table->string('status');
        $table->string('pickup_status')->nullable();
        $table->date('pickup_date')->nullable();
        $table->string('pickup_time_slot')->nullable();
        $table->timestamp('waybill_printed_at')->nullable();
        $table->json('shipping_address')->nullable();
        $table->timestamps();
    });
    Schema::create('order_items', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('order_id');
    });
    Schema::create('order_events', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('order_id');
        $table->foreignId('actor_id')->nullable();
        $table->string('status')->nullable();
        $table->string('title');
        $table->text('note')->nullable();
        $table->timestamps();
    });
    Schema::create('parcel_events', function (Blueprint $table): void {
        $table->id();
        foreach (['order_id', 'hub_id', 'rider_id', 'actor_user_id'] as $column) {
            $table->foreignId($column)->nullable();
        }
        foreach (['event_type', 'from_status', 'to_status', 'note'] as $column) {
            $table->string($column)->nullable();
        }
        $table->timestamps();
    });
    $this->hubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $this->hub = $this->hubUser->logisticsProfile()->create(['business_name' => 'Realtime Hub', 'status' => 'approved']);
    $this->courierUser = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);
    $this->courier = new CourierProfile;
    $this->courier->forceFill(['id' => 41, 'user_id' => $this->courierUser->id, 'logistics_profile_id' => $this->hub->id,
        'first_name' => 'Juan', 'last_name' => 'Cruz', 'status' => 'approved', 'is_active' => true])->save();
    $seller = User::factory()->create(['role' => 'seller']);
    $seller->sellerProfile()->create(['province' => 'Cebu', 'municipality' => 'Cebu City', 'barangay' => 'Lahug']);
    HubServiceArea::create(['logistics_profile_id' => $this->hub->id, 'rider_id' => $this->courier->id,
        'province' => 'Cebu', 'municipality' => 'Cebu City']);
    $this->order = Order::create(['seller_id' => $seller->id, 'logistics_profile_id' => $this->hub->id,
        'origin_hub_id' => $this->hub->id, 'current_hub_id' => $this->hub->id,
        'status' => Order::STATUS_READY_FOR_PICKUP, 'pickup_status' => 'assigned', 'pickup_rider_id' => $this->courier->id,
        'pickup_date' => today(), 'pickup_time_slot' => 'morning',
        'shipping_address' => ['name' => 'Buyer', 'contact_no' => '09123456789', 'province' => 'Cebu', 'municipality' => 'Cebu City', 'barangay' => 'Lahug', 'street' => 'Main Street']]);
    $this->mock(Notifier::class, function ($mock): void {
        $mock->shouldReceive('orderStatusChanged')->andReturnNull();
        $mock->shouldReceive('pickupAssigned')->andReturnNull();
    });
    config()->set('services.supabase', ['url' => 'https://realtime.example.test', 'service_key' => 'test-service-key']);
    Http::preventStrayRequests();
    Http::fake(function () {
        expect(DB::transactionLevel())->toBe(0);

        return Http::response([], 202);
    });
});

it('notifies each distinct rider once after the outermost status transaction commits', function () {
    $this->order->update(['delivery_rider_id' => $this->courier->id]);
    DB::transaction(function () {
        expect(app(ParcelLifecycle::class)->transition($this->order, Order::STATUS_READY_FOR_PICKUP, Order::STATUS_PICKED_UP))->toBeTrue();
        Http::assertNothingSent();
    });
    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => $request['messages'][0]['topic'] === 'rider:41');
    expect(app(ParcelLifecycle::class)->transition($this->order, Order::STATUS_READY_FOR_PICKUP, Order::STATUS_PICKED_UP))->toBeFalse();
    Http::assertSentCount(1);
});

it('discards status notifications on rollback', function () {
    DB::beginTransaction();
    app(ParcelLifecycle::class)->transition($this->order, Order::STATUS_READY_FOR_PICKUP, Order::STATUS_PICKED_UP);
    DB::rollBack();

    Http::assertNothingSent();
    expect($this->order->fresh()->status)->toBe(Order::STATUS_READY_FOR_PICKUP);
});

it('notifies old and new delivery riders when a transition reassigns the parcel', function () {
    $this->order->update(['pickup_rider_id' => null, 'delivery_rider_id' => 52, 'status' => Order::STATUS_DELIVERY_FAILED]);
    app(ParcelLifecycle::class)->transition($this->order, Order::STATUS_DELIVERY_FAILED, Order::STATUS_ASSIGNED_TO_RIDER, ['delivery_rider_id' => 41]);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request): bool => $request['messages'][0]['topic'] === 'rider:52');
    Http::assertSent(fn ($request): bool => $request['messages'][0]['topic'] === 'rider:41');
});

it('notifies pickup assignments and reassignment without duplicating unchanged assignments', function (?int $oldRiderId, int $expectedCount) {
    $this->order->update(['pickup_rider_id' => $oldRiderId]);
    $this->actingAs($this->hubUser);
    $request = Request::create('/logistics/pickups/assign', 'POST', ['rider_id' => 41, 'pickup_date' => today()->toDateString(), 'pickup_time_slot' => 'morning']);
    $request->setUserResolver(fn () => $this->hubUser);
    $request->attributes->set('hub', $this->hub);

    DB::transaction(function () use ($request) {
        app(PickupController::class)->assign($request, $this->order);
        Http::assertNothingSent();
    });
    Http::assertSentCount($expectedCount);
    if ($expectedCount > 0) {
        Http::assertSent(fn ($request): bool => $request['messages'][0]['topic'] === 'rider:41');
    }
    if ($oldRiderId === 52) {
        Http::assertSent(fn ($request): bool => $request['messages'][0]['topic'] === 'rider:52');
    }
})->with(['new assignment' => [null, 1], 'reassignment' => [52, 2], 'unchanged assignment' => [41, 0]]);

it('notifies the courier once for successful query-based pickup acceptance or decline', function (string $action, array $data) {
    Sanctum::actingAs($this->courierUser);
    DB::transaction(function () use ($action, $data) {
        $this->postJson('/api/courier/pickups/'.$this->order->order_number.'/'.$action, $data)->assertSuccessful();
        Http::assertNothingSent();
    });
    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => $request['messages'][0]['topic'] === 'rider:41');

    $this->postJson('/api/courier/pickups/'.$this->order->order_number.'/'.$action, $data);
    Http::assertSentCount(1);
})->with(['accept' => ['accept', []], 'decline' => ['decline', ['reason' => 'Unavailable']]]);

it('does not notify riders for failed or unassigned status changes', function () {
    $this->order->update(['pickup_rider_id' => null]);
    app(ParcelLifecycle::class)->transition($this->order, Order::STATUS_READY_FOR_PICKUP, Order::STATUS_PICKED_UP);

    Http::assertNothingSent();
});

it('does not duplicate a status refresh when its courier notification is created', function () {
    (require database_path('migrations/2026_10_22_161655_create_notifications_table.php'))->up();
    $this->app->forgetInstance(Notifier::class);
    $this->order->update(['pickup_rider_id' => null, 'status' => Order::STATUS_SORTED]);

    app(ParcelLifecycle::class)->transition($this->order, Order::STATUS_SORTED, Order::STATUS_ASSIGNED_TO_RIDER, ['delivery_rider_id' => 41]);

    expect($this->courierUser->notifications()->count())->toBe(1);
    Http::assertSentCount(1);
});

it('confirms pickup using full URL QR text through the shared scan parser', function (string $format) {
    $this->order->update(['pickup_status' => 'accepted', 'waybill_printed_at' => now()]);
    Sanctum::actingAs($this->courierUser);
    $reference = $format === 'encoded' ? str_replace('-', '%2D', $this->order->waybill_code) : $this->order->waybill_code;
    $scan = 'https://shopleap.shop/track/'.$reference.($format === 'trailing slash' ? '/' : '').'?print=1';

    $this->postJson('/api/courier/pickups/'.$this->order->order_number.'/confirm', ['scan' => $scan])
        ->assertSuccessful()->assertJsonPath('data.status', Order::STATUS_PICKED_UP);
    expect($this->order->fresh()->status)->toBe(Order::STATUS_PICKED_UP);
    Http::assertSentCount(1);
})->with(['URL', 'trailing slash', 'encoded']);
