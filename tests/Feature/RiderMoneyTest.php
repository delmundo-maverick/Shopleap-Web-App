<?php

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\RiderRate;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\MoneyMovementQueries;
use App\Services\MoneyMovements;
use App\Services\OrderLedger;
use App\Services\PayoutQueries;
use App\Services\Payouts;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->travelTo(now()->startOfSecond());
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    Schema::create('logistics_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('business_name');
        $table->string('status')->default('approved');
        $table->timestamps();
    });
    Schema::create('courier_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('logistics_profile_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('status')->default('approved');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->string('order_number')->unique();
        $table->string('waybill_code')->unique();
        foreach (['seller_id', 'buyer_id', 'earning_hub_id', 'earning_courier_id', 'logistics_profile_id', 'destination_hub_id', 'delivery_rider_id'] as $column) {
            $table->foreignId($column)->nullable();
        }
        $table->string('status');
        foreach (['total_amount', 'commission_amount', 'delivered_shipping_fee', 'cod_collected_amount', 'rider_pay'] as $column) {
            $table->decimal($column, 12, 2)->nullable();
        }
        $table->timestamp('delivered_at')->nullable();
        $table->timestamp('earnings_recorded_at')->nullable();
        $table->timestamps();
    });
    Schema::create('order_items', function (Blueprint $table): void {
        $table->id();
    });
    Schema::create('delivery_proofs', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('order_id');
    });
    foreach (['2026_10_04_180040_create_platform_settings_table.php', '2026_10_21_201427_create_order_returns_tables.php', '2026_10_22_161655_create_notifications_table.php',
        '2026_11_08_002022_create_rider_rates_table.php', '2026_11_15_142304_create_money_ledger_tables.php', '2026_11_15_152144_create_money_movement_tables.php',
        '2026_11_15_162541_create_payout_tables.php', '2026_11_15_162542_initialize_payout_settings_and_refunds.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $this->buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $this->admin = User::factory()->create(['role' => 'super_admin', 'account_status' => 'active']);
    $this->hubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $this->hub = $this->hubUser->logisticsProfile()->create(['business_name' => 'Cebu Hub', 'status' => 'approved']);
    $this->riderUser = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);
    $this->rider = CourierProfile::create(['user_id' => $this->riderUser->id, 'logistics_profile_id' => $this->hub->id, 'first_name' => 'Juan', 'last_name' => 'Cruz', 'status' => 'approved', 'is_active' => true]);
    $this->order = riderMoneyOrder($this->hub->id, $this->rider->id, $this->seller->id, $this->buyer->id);
    RiderRate::factory()->create(['logistics_profile_id' => $this->hub->id, 'created_by' => $this->hubUser->id, 'amount' => '20.00', 'effective_from' => now()->subDay()]);
    app(Payouts::class)->addMethod($this->seller->id, ['type' => 'GCash', 'account_name' => 'Maria Cruz', 'account_number_masked' => '1234']);
    $this->method = DB::table('seller_payout_methods')->first();
});

function riderMoneyOrder(int $hubId, int $riderId, int $sellerId, int $buyerId, array $attributes = []): Order
{
    $order = new Order;
    $order->forceFill($attributes + ['seller_id' => $sellerId, 'buyer_id' => $buyerId, 'earning_hub_id' => $hubId, 'earning_courier_id' => $riderId,
        'logistics_profile_id' => $hubId, 'destination_hub_id' => $hubId, 'delivery_rider_id' => $riderId,
        'status' => Order::STATUS_COMPLETED, 'total_amount' => '900.00', 'commission_amount' => '90.00', 'delivered_shipping_fee' => '40.00',
        'cod_collected_amount' => '940.00', 'rider_pay' => '20.00', 'delivered_at' => now(), 'earnings_recorded_at' => now()])->save();
    app(OrderLedger::class)->delivered($order);
    app(OrderLedger::class)->completed($order);

    return $order;
}

function riderMoneyHandover(object $context, string $amount = '940.00'): object
{
    return app(MoneyMovements::class)->handover($context->hub->id, $context->rider->id, $amount, $amount === '940.00' ? null : 'Cash shortage recorded.', (string) Str::uuid(), $context->hubUser->id);
}

it('returns cash on hand from the same outstanding parcel query as the hub', function () {
    $handover = riderMoneyHandover($this, '900.00');
    Sanctum::actingAs($this->riderUser);
    $response = $this->getJson('/api/courier/cash/summary')->assertSuccessful();
    $response->assertJsonPath('cash_on_hand', '40.00')->assertJsonPath('shortage_amount', '40.00')->assertJsonPath('currency', 'PHP')
        ->assertJsonPath('orders.0.order_number', $this->order->order_number)->assertJsonPath('orders.0.amount', '40.00');
    $expected = app(MoneyMovementQueries::class)->cashOrders($this->hub->id, $this->rider->id)->first();
    expect($response->json('orders.0.amount'))->toBe(LedgerService::decimal(LedgerService::cents((string) $expected->amount)));
    app(MoneyMovements::class)->settleShortage($this->hub->id, $handover->reference_no, 'Remaining cash received.', $this->hubUser->id);
    $this->getJson('/api/courier/cash/summary')->assertExactJson(['cash_on_hand' => '0.00', 'shortage_amount' => '0.00', 'currency' => 'PHP', 'orders' => []]);
    $this->getJson('/api/courier/cash/history')->assertSuccessful()->assertJsonPath('data.0.amount', '940.00')->assertJsonPath('data.0.status', 'settled');
});

it('returns paginated handovers and keeps the rider read only', function () {
    $handover = riderMoneyHandover($this);
    Sanctum::actingAs($this->riderUser);
    $this->getJson('/api/courier/cash/history?page=1')->assertSuccessful()->assertJsonPath('data.0.reference_no', $handover->reference_no)
        ->assertJsonPath('data.0.status', 'confirmed')->assertJsonPath('data.0.amount', '940.00')->assertJsonPath('meta.total', 1)->assertJsonPath('meta.current_page', 1);
    $this->getJson('/api/courier/cash/history?page=2')->assertSuccessful()->assertJsonPath('data', []);
    $this->getJson('/api/courier/cash/history?page=0')->assertUnprocessable();
    $this->postJson('/api/courier/cash/history')->assertMethodNotAllowed();
    $this->postJson('/api/courier/cash/summary')->assertMethodNotAllowed();
    $this->postJson('/api/courier/cash/'.$handover->reference_no.'/confirm')->assertNotFound();
    $this->assertDatabaseCount('rider_cash_handovers', 1);
});

it('adds accrued and paid earnings without removing the existing earnings contract', function () {
    $second = riderMoneyOrder($this->hub->id, $this->rider->id, $this->seller->id, $this->buyer->id);
    app(Payouts::class)->payRider($this->hub->id, $this->rider->id, [$this->order->order_number], null, (string) Str::uuid(), $this->hubUser->id);
    Sanctum::actingAs($this->riderUser);
    $this->getJson('/api/courier/earnings/summary?period=all')->assertSuccessful()
        ->assertJsonPath('total_earned', '40.00')->assertJsonPath('deliveries', 2)->assertJsonPath('recorded_earnings', 2)
        ->assertJsonPath('accrued_unpaid', '20.00')->assertJsonPath('paid', '20.00')->assertJsonPath('currency', 'PHP')
        ->assertJsonStructure(['period', 'fee_per_delivery', 'cod_collected', 'daily' => [['date', 'deliveries', 'earned', 'cod_collected', 'currency']]]);
    $history = $this->getJson('/api/courier/earnings/history?period=all')->assertSuccessful()
        ->assertJsonStructure(['data' => [['order_id', 'order_number', 'waybill_code', 'amount', 'currency', 'recorded_at', 'delivered_at', 'proof', 'payout_status']], 'meta' => ['current_page', 'last_page', 'total']]);
    $rows = collect($history->json('data'))->keyBy('order_number');
    expect($rows[$this->order->order_number]['payout_status'])->toBe('paid')->and($rows[$second->order_number]['payout_status'])->toBe('accrued');
    $this->getJson('/api/courier/earnings?period=all')->assertSuccessful()->assertJsonPath('accrued_unpaid', '20.00')->assertJsonPath('paid', '20.00');
    expect(app(PayoutQueries::class)->riderTotals($this->rider->id, null, now()->endOfDay()))->toBe(['accrued_unpaid' => '20.00', 'paid' => '20.00']);
});

it('preserves the earnings date filters for the added totals', function () {
    $yesterday = riderMoneyOrder($this->hub->id, $this->rider->id, $this->seller->id, $this->buyer->id, ['delivered_at' => now()->subDay(), 'earnings_recorded_at' => now()->subDay()]);
    app(Payouts::class)->payRider($this->hub->id, $this->rider->id, [$yesterday->order_number], null, (string) Str::uuid(), $this->hubUser->id);
    Sanctum::actingAs($this->riderUser);
    $this->getJson('/api/courier/earnings/summary?period=today')->assertSuccessful()->assertJsonPath('accrued_unpaid', '20.00')->assertJsonPath('paid', '0.00');
    $day = now()->subDay()->toDateString();
    $this->getJson('/api/courier/earnings/summary?period=custom&from='.$day.'&to='.$day)->assertSuccessful()->assertJsonPath('accrued_unpaid', '0.00')->assertJsonPath('paid', '20.00');
});

it('scopes cash earnings and notifications to the signed in rider', function () {
    riderMoneyHandover($this, '900.00');
    app(Payouts::class)->payRider($this->hub->id, $this->rider->id, null, null, (string) Str::uuid(), $this->hubUser->id);
    $otherUser = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);
    CourierProfile::create(['user_id' => $otherUser->id, 'logistics_profile_id' => $this->hub->id, 'first_name' => 'Ana', 'last_name' => 'Reyes', 'status' => 'approved', 'is_active' => true]);
    Sanctum::actingAs($otherUser);
    $this->getJson('/api/courier/cash/summary?rider_id='.$this->rider->id)->assertSuccessful()->assertJsonPath('cash_on_hand', '0.00')->assertJsonPath('orders', []);
    $this->getJson('/api/courier/cash/history?rider_id='.$this->rider->id)->assertSuccessful()->assertJsonPath('meta.total', 0);
    $this->getJson('/api/courier/earnings/summary?period=all&rider_id='.$this->rider->id)->assertSuccessful()->assertJsonPath('paid', '0.00')->assertJsonPath('accrued_unpaid', '0.00');
    $this->getJson('/api/courier/earnings/history?period=all')->assertSuccessful()->assertJsonPath('data', []);
    $this->getJson('/api/courier/notifications')->assertSuccessful()->assertJsonPath('data', []);
});

it('keeps historical balances attached to the earning rider after a hub transfer', function () {
    $otherHubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $otherHub = $otherHubUser->logisticsProfile()->create(['business_name' => 'Mandaue Hub', 'status' => 'approved']);
    $this->rider->update(['logistics_profile_id' => $otherHub->id]);
    Sanctum::actingAs($this->riderUser);
    $this->getJson('/api/courier/cash/summary')->assertSuccessful()->assertJsonPath('cash_on_hand', '940.00');
    $this->getJson('/api/courier/earnings/summary?period=all')->assertSuccessful()->assertJsonPath('accrued_unpaid', '20.00');
});

it('notifies the hub and rider once for a handover including the hub that confirmed it', function (string $received, string $status) {
    $key = (string) Str::uuid();
    $handover = app(MoneyMovements::class)->handover($this->hub->id, $this->rider->id, $received, 'Cash received.', $key, $this->hubUser->id);
    app(MoneyMovements::class)->handover($this->hub->id, $this->rider->id, $received, 'Cash received.', $key, $this->hubUser->id);
    foreach ([$this->riderUser, $this->hubUser] as $user) {
        $notifications = $user->notifications()->get();
        expect($notifications)->toHaveCount(1)->and($notifications->first()->data['body'])->toContain($handover->reference_no);
        expect($notifications->first()->data['kind'])->toBe('cash.handover.'.$status);
    }
    expect($this->hubUser->notifications()->first()->data['url'])->toBe('/logistics/money/cash');
    Sanctum::actingAs($this->riderUser);
    $this->getJson('/api/courier/notifications')->assertSuccessful()->assertJsonPath('data.0.url', '/api/courier/cash/history')
        ->assertJsonPath('data.0.meta.screen', 'cash')->assertJsonPath('data.0.meta.reference_no', $handover->reference_no)->assertJsonPath('data.0.meta.amount', $received);
})->with([['940.00', 'confirmed'], ['900.00', 'short']]);

it('notifies the hub after a remittance decision with a reason for rejection', function (bool $verify) {
    riderMoneyHandover($this);
    $service = app(MoneyMovements::class);
    $remittance = $service->submitRemittance($this->hub->id, null, null, (string) Str::uuid(), $this->hubUser->id);
    $service->decideRemittance($remittance->reference_no, $verify, $verify ? null : 'Transfer reference could not be verified.', $this->admin->id);
    $service->decideRemittance($remittance->reference_no, $verify, $verify ? null : 'Transfer reference could not be verified.', $this->admin->id);
    $notification = $this->hubUser->notifications()->where('data->kind', 'remittance.'.($verify ? 'verified' : 'rejected'))->sole();
    expect($notification->data['body'])->toContain($remittance->reference_no, '₱900.00')->and($notification->data['url'])->toBe('/logistics/money/remittances');
    if (! $verify) {
        expect($notification->data['body'])->toContain('Transfer reference could not be verified.');
    }
})->with([true, false]);

it('notifies the rider once when earnings are paid', function () {
    $key = (string) Str::uuid();
    $service = app(Payouts::class);
    $payout = $service->payRider($this->hub->id, $this->rider->id, null, null, $key, $this->hubUser->id);
    $service->payRider($this->hub->id, $this->rider->id, null, null, $key, $this->hubUser->id);
    $notification = $this->riderUser->notifications()->sole();
    expect($notification->data['body'])->toContain($payout->reference_no, '₱20.00')->and($notification->data['url'])->toBe('/api/courier/earnings/history?period=all');
    expect($notification->data['meta'])->toMatchArray(['screen' => 'earnings', 'reference_no' => $payout->reference_no, 'amount' => '20.00', 'currency' => 'PHP', 'status' => 'paid']);
});

it('notifies the seller for each payout status change once', function (string $decision) {
    $service = app(Payouts::class);
    $payout = $service->requestSeller($this->seller->id, '100.00', $this->method->id, (string) Str::uuid());
    $service->decideSeller($payout->reference_no, 'approve', null, $this->admin->id);
    $service->decideSeller($payout->reference_no, 'approve', null, $this->admin->id);
    $service->decideSeller($payout->reference_no, $decision, 'Account ownership requires review.', $this->admin->id);
    $service->decideSeller($payout->reference_no, $decision, 'Account ownership requires review.', $this->admin->id);
    $notifications = $this->seller->notifications()->orderBy('created_at')->get();
    expect($notifications)->toHaveCount(2);
    foreach ($notifications as $notification) {
        expect($notification->data['body'])->toContain($payout->reference_no, '₱100.00')->and($notification->data['url'])->toBe('/seller/payouts');
    }
    if ($decision === 'reject') {
        expect($notifications->pluck('data.body')->implode(' '))->toContain('Account ownership requires review.');
    }
})->with(['pay', 'reject']);

it('notifies the buyer once after a refund is paid using its public reference', function () {
    $return = OrderReturn::create(['order_id' => $this->order->id, 'buyer_id' => $this->buyer->id, 'seller_id' => $this->seller->id,
        'type' => OrderReturn::TYPE_REFUND_ONLY, 'reason' => 'missing_item', 'details' => 'Missing parcel contents.', 'status' => OrderReturn::STATUS_REFUND_PENDING,
        'refund_amount' => '900.00', 'refund_method' => 'gcash', 'refund_account_name' => 'Buyer Cruz', 'refund_account_number' => '1234']);
    $service = app(Payouts::class);
    $refund = $service->prepareRefund($return);
    $service->payRefund($refund->reference_no, $this->admin->id);
    $service->payRefund($refund->reference_no, $this->admin->id);
    $notification = $this->buyer->notifications()->sole();
    expect($notification->data['body'])->toContain($refund->reference_no, '₱900.00')->and($notification->data['url'])->toBe(route('buyer.returns.show', $return, false));
    expect($notification->data['meta'])->toMatchArray(['reference_no' => $refund->reference_no, 'order_id' => $this->order->order_number, 'amount' => '900.00', 'currency' => 'PHP', 'status' => 'paid']);
});

it('discards money notifications when an outer transaction rolls back', function () {
    DB::beginTransaction();
    riderMoneyHandover($this);
    expect($this->hubUser->notifications()->count())->toBe(0)->and($this->riderUser->notifications()->count())->toBe(0);
    DB::rollBack();
    expect($this->hubUser->notifications()->count())->toBe(0)->and($this->riderUser->notifications()->count())->toBe(0);
    $this->assertDatabaseCount('rider_cash_handovers', 0);
});

it('delivers money notifications only after the outermost transaction commits', function () {
    DB::beginTransaction();
    riderMoneyHandover($this);
    expect($this->riderUser->notifications()->count())->toBe(0);
    DB::commit();
    expect($this->riderUser->notifications()->count())->toBe(1)->and($this->hubUser->notifications()->count())->toBe(1);
});

it('keeps the earnings history query count bounded when adding payout status', function () {
    Sanctum::actingAs($this->riderUser);
    DB::enableQueryLog();
    $this->getJson('/api/courier/earnings/history?period=all')->assertSuccessful();
    $oneCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    foreach (range(1, 5) as $index) {
        riderMoneyOrder($this->hub->id, $this->rider->id, $this->seller->id, $this->buyer->id);
    }
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/courier/earnings/history?period=all')->assertSuccessful()->assertJsonCount(6, 'data');
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual($oneCount);
    DB::disableQueryLog();
});

it('requires an approved active rider for the money endpoints', function () {
    $this->getJson('/api/courier/cash/summary')->assertUnauthorized();
    Sanctum::actingAs($this->seller);
    $this->getJson('/api/courier/cash/summary')->assertForbidden();
    $this->rider->update(['is_active' => false]);
    Sanctum::actingAs($this->riderUser);
    $this->getJson('/api/courier/cash/history')->assertForbidden();
});
