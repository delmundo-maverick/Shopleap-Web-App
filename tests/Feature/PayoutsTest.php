<?php

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\MoneyMovements;
use App\Services\MoneyReport;
use App\Services\Notifier;
use App\Services\OrderLedger;
use App\Services\OrderReturns;
use App\Services\Payouts;
use App\Support\MoneyReferences;
use App\Support\Portal;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->withoutVite();
    $this->mock(Notifier::class)->shouldReceive('returnChanged', 'moneyChanged')->andReturnNull();
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
        $table->foreignId('logistics_profile_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->timestamps();
    });
    Schema::create('seller_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('status')->default('approved');
        $table->timestamps();
    });
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->string('order_number')->unique();
        $table->string('waybill_code')->unique();
        $table->foreignId('seller_id');
        $table->foreignId('buyer_id');
        $table->foreignId('earning_hub_id');
        $table->foreignId('earning_courier_id');
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
    Schema::create('products', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('seller_id');
        $table->integer('stock_quantity')->default(0);
        $table->integer('low_stock_threshold')->nullable();
    });
    Schema::create('product_variants', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('product_id');
        $table->integer('stock_quantity')->default(0);
        $table->boolean('is_active')->default(true);
    });
    foreach (['2026_10_04_180040_create_platform_settings_table.php', '2026_10_21_201427_create_order_returns_tables.php', '2026_10_22_161655_create_notifications_table.php',
        '2026_11_15_142304_create_money_ledger_tables.php', '2026_11_15_152144_create_money_movement_tables.php', '2026_11_15_162541_create_payout_tables.php', '2026_11_15_162542_initialize_payout_settings_and_refunds.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $this->seller->sellerProfile()->create(['status' => 'approved']);
    $this->buyer = User::factory()->create();
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->hubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $this->hub = $this->hubUser->logisticsProfile()->create(['business_name' => 'Cebu Hub', 'status' => 'approved']);
    $this->rider = CourierProfile::create(['logistics_profile_id' => $this->hub->id, 'first_name' => 'Juan', 'last_name' => 'Cruz']);
    $this->order = new Order;
    $this->order->forceFill(['order_number' => 'ORD-A8C3M2R7', 'seller_id' => $this->seller->id, 'buyer_id' => $this->buyer->id,
        'earning_hub_id' => $this->hub->id, 'earning_courier_id' => $this->rider->id, 'status' => Order::STATUS_COMPLETED,
        'total_amount' => '900.00', 'commission_amount' => '90.00', 'delivered_shipping_fee' => '40.00', 'cod_collected_amount' => '940.00',
        'rider_pay' => '20.00', 'delivered_at' => now(), 'earnings_recorded_at' => now()])->save();
    app(OrderLedger::class)->delivered($this->order);
    app(OrderLedger::class)->completed($this->order);
    app(Payouts::class)->addMethod($this->seller->id, ['type' => 'GCash', 'account_name' => 'Maria Cruz', 'account_number_masked' => '1234']);
    $this->method = DB::table('seller_payout_methods')->first();
});

function approvedPayoutReturn(Order $order): OrderReturn
{
    return OrderReturn::create(['order_id' => $order->id, 'buyer_id' => $order->buyer_id, 'seller_id' => $order->seller_id,
        'type' => OrderReturn::TYPE_REFUND_ONLY, 'reason' => 'missing_item', 'details' => 'Missing parcel contents.',
        'status' => OrderReturn::STATUS_REFUND_PENDING, 'items_amount' => '900.00', 'refund_amount' => '900.00', 'commission_reversal' => '90.00',
        'refund_method' => 'gcash', 'refund_account_name' => 'Buyer Cruz', 'refund_account_number' => '09171234567']);
}

it('pays rider earnings exactly once and keeps parcel coverage unique', function () {
    $service = app(Payouts::class);
    app(MoneyMovements::class)->handover($this->hub->id, $this->rider->id, '940.00', null, (string) Str::uuid(), $this->hubUser->id);
    $key = (string) Str::uuid();
    $paid = $service->payRider($this->hub->id, $this->rider->id, [$this->order->order_number], 'Cash received', $key, $this->hubUser->id);
    expect($service->payRider($this->hub->id, $this->rider->id, null, null, $key, $this->hubUser->id)->id)->toBe($paid->id);
    expect($paid->reference_no)->toMatch('/^PO-[A-Z0-9]+$/');
    expect(app(MoneyReport::class)->balances('rider', $this->rider->id))->toMatchArray(['earnings_accrued' => '0.00', 'earnings_paid' => '20.00']);
    expect(app(MoneyReport::class)->balances('hub', $this->hub->id))->toMatchArray(['rider_pay_owed' => '0.00', 'cash_held' => '920.00']);
    expect(fn () => $service->payRider($this->hub->id, $this->rider->id, null, null, (string) Str::uuid(), $this->hubUser->id))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('rider_payout_items', 1);
    expect(fn () => DB::table('rider_payout_items')->insert(['rider_payout_id' => $paid->id, 'order_id' => $this->order->id, 'amount' => '20.00']))->toThrow(QueryException::class);
});

it('rejects foreign parcels and unknown earnings without partially paying', function () {
    $service = app(Payouts::class);
    expect(fn () => $service->payRider($this->hub->id, $this->rider->id, [$this->order->order_number, 'ORD-UNOWNED'], null, (string) Str::uuid(), $this->hubUser->id))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('rider_payouts', 0);
    $this->order->forceFill(['rider_pay' => null])->save();
    expect(fn () => $service->payRider($this->hub->id, $this->rider->id, null, null, (string) Str::uuid(), $this->hubUser->id))->toThrow(ValidationException::class);
});

it('locks seller funds and pays through the ledger with a complete timeline', function () {
    $service = app(Payouts::class);
    $key = (string) Str::uuid();
    $requested = $service->requestSeller($this->seller->id, '810.00', $this->method->id, $key);
    expect($service->requestSeller($this->seller->id, '810.00', $this->method->id, $key)->id)->toBe($requested->id);
    expect($requested->reference_no)->toMatch('/^SP-[A-Z0-9]+$/');
    expect(app(MoneyReport::class)->balances('seller', $this->seller->id))->toMatchArray(['available' => '0.00', 'payout_locked' => '810.00']);
    $service->removeMethod($this->seller->id, $this->method->id);
    $this->travel(1)->minutes();
    $processing = $service->decideSeller($requested->reference_no, 'approve', null, $this->admin->id);
    expect($processing->processing_at)->not->toBeNull();
    $this->travel(1)->minutes();
    $paid = $service->decideSeller($requested->reference_no, 'pay', null, $this->admin->id);
    $service->decideSeller($requested->reference_no, 'pay', null, $this->admin->id);
    expect($paid->status)->toBe('paid')->and($paid->requested_at)->toBeLessThan($paid->processing_at)->and($paid->processing_at)->toBeLessThan($paid->paid_at);
    expect($paid->account_number_masked)->toBe('1234')->and($paid->account_name)->toBe('Maria Cruz');
    expect(app(MoneyReport::class)->balances('seller', $this->seller->id))->toMatchArray(['available' => '0.00', 'payout_locked' => '0.00', 'withdrawn' => '810.00']);
    expect(app(MoneyReport::class)->balances('platform', 0)['cod_held'])->toBe('-810.00');
    expect(app(MoneyReport::class)->balances('system', 0)['EXTERNAL_BANK'])->toBe('810.00');
    $this->assertDatabaseCount('seller_payouts', 1);
});

it('requires a rejection reason and restores seller funds once', function () {
    $service = app(Payouts::class);
    $record = $service->requestSeller($this->seller->id, '200.00', $this->method->id, (string) Str::uuid());
    expect(fn () => $service->requestSeller($this->seller->id, '100.00', $this->method->id, (string) Str::uuid()))->toThrow(ValidationException::class);
    expect(fn () => $service->decideSeller($record->reference_no, 'pay', null, $this->admin->id))->toThrow(ValidationException::class);
    expect(fn () => $service->decideSeller($record->reference_no, 'reject', ' ', $this->admin->id))->toThrow(ValidationException::class);
    $service->decideSeller($record->reference_no, 'approve', null, $this->admin->id);
    $rejected = $service->decideSeller($record->reference_no, 'reject', 'Account details need review.', $this->admin->id);
    $service->decideSeller($record->reference_no, 'reject', 'Account details need review.', $this->admin->id);
    expect($rejected->processing_at)->not->toBeNull()->and($rejected->rejected_at)->not->toBeNull();
    expect(app(MoneyReport::class)->balances('seller', $this->seller->id))->toMatchArray(['available' => '810.00', 'payout_locked' => '0.00']);
    expect(fn () => $service->decideSeller($record->reference_no, 'pay', null, $this->admin->id))->toThrow(ValidationException::class);
    expect($service->requestSeller($this->seller->id, '100.00', $this->method->id, (string) Str::uuid())->status)->toBe('pending');
});

it('enforces payout minimums and the available balance', function (string $amount) {
    expect(fn () => app(Payouts::class)->requestSeller($this->seller->id, $amount, $this->method->id, (string) Str::uuid()))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('seller_payouts', 0);
})->with(['99.99', '810.01', '-1.00', '100.001', '0.00']);

it('retains only last four digits and keeps a default method after removal', function () {
    $service = app(Payouts::class);
    expect(fn () => $service->addMethod($this->seller->id, ['type' => 'bank', 'account_name' => 'Maria', 'account_number_masked' => '123456789012']))->toThrow(ValidationException::class);
    $service->addMethod($this->seller->id, ['type' => 'Maya', 'account_name' => 'Maria', 'account_number_masked' => '5678', 'is_default' => true]);
    $service->removeMethod($this->seller->id, DB::table('seller_payout_methods')->where('is_default', true)->value('id'));
    $this->assertDatabaseHas('seller_payout_methods', ['id' => $this->method->id, 'is_default' => true]);
    expect(approvedPayoutReturn($this->order)->getRawOriginal('refund_account_number'))->toBe('4567');
});

it('pays an approved refund once and updates the buyer return history', function () {
    $return = approvedPayoutReturn($this->order);
    app(OrderLedger::class)->returnChanged($return);
    $service = app(Payouts::class);
    $record = $service->prepareRefund($return);
    expect($record->reference_no)->toMatch('/^RF-[A-Z0-9]+$/');
    expect($service->prepareRefund($return)->id)->toBe($record->id);
    $paid = $service->payRefund($record->reference_no, $this->admin->id);
    expect($service->payRefund($record->reference_no, $this->admin->id)->paid_at)->toBe($paid->paid_at);
    expect($return->refresh()->status)->toBe(OrderReturn::STATUS_REFUNDED)->and($return->refund_reference)->toBe($record->reference_no);
    expect(app(MoneyReport::class)->balances('platform', 0)['cod_held'])->toBe('-900.00');
    expect(app(MoneyReport::class)->balances('system', 0)['EXTERNAL_BANK'])->toBe('900.00');
    $this->assertDatabaseCount('order_return_events', 1);
});

it('rejects payment of a refund whose approval changed', function () {
    $return = approvedPayoutReturn($this->order);
    $record = app(Payouts::class)->prepareRefund($return);
    $return->update(['status' => OrderReturn::STATUS_REJECTED]);
    expect(fn () => app(Payouts::class)->payRefund($record->reference_no, $this->admin->id))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('refund_payouts', ['id' => $record->id, 'status' => 'pending']);
});

it('routes the earlier refund action through the payout ledger', function () {
    $return = approvedPayoutReturn($this->order);
    expect(app(OrderReturns::class)->markRefunded($return, $this->admin->id))->toBeTrue();
    expect(app(MoneyReport::class)->balances('system', 0)['EXTERNAL_BANK'])->toBe('900.00');
});

it('renders role scoped payout pages and the admin refund queue', function () {
    $this->actingAs($this->seller)->get(route('seller.payouts.index'))->assertSuccessful()->assertSee('₱810.00')->assertSee('•••• 1234')->assertSee('Last 4 account digits');
    $this->withSession(['portal_host' => Portal::host('logistics')])->actingAs($this->hubUser)->get(route('logistics.payouts.index'))->assertSuccessful()->assertSee('Rider payouts')->assertSee($this->order->order_number)->assertSee('₱20.00');
    $return = approvedPayoutReturn($this->order);
    $refund = app(Payouts::class)->prepareRefund($return);
    $this->withSession(['portal_host' => Portal::host('admin')])->actingAs($this->admin)->get(route('admin.refunds.index'))->assertSuccessful()->assertSee($refund->reference_no)->assertSee('Mark refund paid')->assertDontSee('09171234567');
    $this->get(route('admin.payouts.index'))->assertSuccessful();
});

it('prevents sellers from changing other sellers methods and admin payment statuses', function () {
    $other = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $other->sellerProfile()->create(['status' => 'approved']);
    $this->actingAs($other)->delete(route('seller.payouts.methods.destroy', $this->method->id))->assertNotFound();
    $record = app(Payouts::class)->requestSeller($this->seller->id, '100.00', $this->method->id, (string) Str::uuid());
    $this->post(route('admin.payouts.decide', ['reference' => $record->reference_no, 'decision' => 'approve']))->assertForbidden();
});

it('honors admin minimum payout changes', function () {
    $this->actingAs($this->admin)->post(route('admin.movements.settings'), ['remittance_due_days' => 3, 'minimum_payout' => '500.00'])->assertRedirect();
    expect((string) PlatformSetting::get('minimum_payout'))->toBe('500.00');
    expect(fn () => app(Payouts::class)->requestSeller($this->seller->id, '499.99', $this->method->id, (string) Str::uuid()))->toThrow(ValidationException::class);
});

it('masks existing refund accounts and backfills pending refunds without duplicate records', function () {
    $return = approvedPayoutReturn($this->order);
    DB::table('order_returns')->where('id', $return->id)->update(['refund_account_number' => '09171234567']);
    $migration = require database_path('migrations/2026_11_15_162542_initialize_payout_settings_and_refunds.php');
    $migration->up();
    $migration->up();
    expect($return->refresh()->getRawOriginal('refund_account_number'))->toBe('4567');
    $this->assertDatabaseCount('refund_payouts', 1);
});

it('retries random references on a collision', function () {
    $previous = new PDOException('UNIQUE constraint failed: rider_payouts.reference_no');
    $previous->errorInfo = ['23000', 19, 'UNIQUE constraint failed: rider_payouts.reference_no'];
    $exception = new UniqueConstraintViolationException('sqlite', 'insert into rider_payouts', [], $previous);
    $database = DB::getFacadeRoot();
    DB::shouldReceive('transaction')->once()->ordered()->andThrow($exception);
    DB::shouldReceive('transaction')->once()->ordered()->andReturnUsing(fn (Closure $callback) => $callback());
    DB::shouldReceive('table')->andReturnUsing(fn (string $table) => $database->table($table));
    try {
        $record = MoneyReferences::insert('rider_payouts', 'PO', ['hub_id' => $this->hub->id, 'rider_id' => $this->rider->id,
            'amount' => '20.00', 'request_key' => (string) Str::uuid(), 'paid_by' => $this->hubUser->id, 'paid_at' => now()]);
    } finally {
        DB::swap($database);
    }
    expect($record->reference_no)->toMatch('/^PO-[A-Z0-9]+$/');
    $this->assertDatabaseCount('rider_payouts', 1);
});

it('rolls back rider payment records and coverage when the ledger posting fails', function () {
    $this->partialMock(LedgerService::class)->shouldReceive('post')->andThrow(new RuntimeException('Posting unavailable'));
    expect(fn () => app(Payouts::class)->payRider($this->hub->id, $this->rider->id, null, null, (string) Str::uuid(), $this->hubUser->id))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('rider_payouts', 0);
    $this->assertDatabaseCount('rider_payout_items', 0);
    expect(app(MoneyReport::class)->balances('rider', $this->rider->id)['earnings_accrued'])->toBe('20.00');
});

it('disables payout requests for zero and negative available balances', function (string $extraRefund) {
    app(LedgerService::class)->post('balance-adjustment', 'refund', $this->order->id, 'Refund issued', [
        ['owner_type' => 'seller', 'owner_id' => $this->seller->id, 'account_type' => 'available', 'direction' => 'debit', 'cents' => LedgerService::cents($extraRefund)],
        ['owner_type' => 'buyer', 'owner_id' => $this->buyer->id, 'account_type' => 'refunds_issued', 'direction' => 'credit', 'cents' => LedgerService::cents($extraRefund)],
    ]);
    $this->actingAs($this->seller)->get(route('seller.payouts.index'))->assertSuccessful()->assertSee('You have no available balance to withdraw.')->assertSee('<fieldset disabled', false);
    expect(fn () => app(Payouts::class)->requestSeller($this->seller->id, '100.00', $this->method->id, (string) Str::uuid()))->toThrow(ValidationException::class);
})->with(['810.00', '900.00']);

it('does not flash an account number when a method form fails validation', function () {
    $this->actingAs($this->seller)->post(route('seller.payouts.methods.store'), ['type' => 'GCash', 'account_name' => 'Maria', 'account_number_masked' => '09171234567'])
        ->assertSessionHasErrors('account_number_masked')->assertSessionMissing('_old_input.account_number_masked');
    $this->assertDatabaseCount('seller_payout_methods', 1);
});

it('keeps rider and seller payouts scoped to their owners in forms and histories', function () {
    $service = app(Payouts::class);
    $record = $service->requestSeller($this->seller->id, '100.00', $this->method->id, (string) Str::uuid());
    $other = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $other->sellerProfile()->create(['status' => 'approved']);
    $this->actingAs($other)->get(route('seller.payouts.index'))->assertSuccessful()->assertDontSee($record->reference_no);
    $this->post(route('seller.payouts.store'), ['amount' => '100.00', 'method_id' => $this->method->id, 'request_key' => (string) Str::uuid()])->assertNotFound();
    $otherHubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $otherHub = $otherHubUser->logisticsProfile()->create(['business_name' => 'Other Hub', 'status' => 'approved']);
    expect(fn () => $service->payRider($otherHub->id, $this->rider->id, [$this->order->order_number], null, (string) Str::uuid(), $otherHubUser->id))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('rider_payouts', 0);
});

it('queues refunds from the return workflow only when payment becomes due', function () {
    $return = approvedPayoutReturn($this->order);
    $return->update(['status' => OrderReturn::STATUS_REQUESTED, 'type' => OrderReturn::TYPE_RETURN_AND_REFUND]);
    $returns = app(OrderReturns::class);
    expect($returns->approve($return, $this->seller->id))->toBeTrue();
    $this->assertDatabaseCount('refund_payouts', 0);
    $return->update(['status' => OrderReturn::STATUS_SHIPPED_BACK]);
    expect($returns->confirmReceived($return, $this->seller->id))->toBeTrue();
    $this->assertDatabaseHas('refund_payouts', ['order_return_id' => $return->id, 'status' => 'pending', 'amount' => '900.00']);
});

it('accepts seller requests and records admin payment decisions through the web routes', function () {
    $this->actingAs($this->seller)->post(route('seller.payouts.store'), ['amount' => '100.00', 'method_id' => $this->method->id, 'request_key' => (string) Str::uuid()])->assertRedirect()->assertSessionHasNoErrors();
    $record = DB::table('seller_payouts')->first();
    $this->withSession(['portal_host' => Portal::host('admin')])->actingAs($this->admin);
    $this->post(route('admin.payouts.decide', ['reference' => $record->reference_no, 'decision' => 'approve']))->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('admin.payouts.decide', ['reference' => $record->reference_no, 'decision' => 'pay']))->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('seller_payouts', ['id' => $record->id, 'status' => 'paid']);
    $this->get(route('admin.payouts.index', ['status' => 'paid']))->assertSuccessful()->assertSee($record->reference_no)->assertSee('Pending ·')->assertSee('Processing ·')->assertSee('Paid ·');
    $refund = app(Payouts::class)->prepareRefund(approvedPayoutReturn($this->order));
    $this->post(route('admin.refunds.pay', $refund->reference_no))->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('refund_payouts', ['id' => $refund->id, 'status' => 'paid', 'paid_by' => $this->admin->id]);
});

it('refunds discounted items without refunding earned delivery shipping', function () {
    Schema::table('orders', function (Blueprint $table): void {
        $table->decimal('items_subtotal', 12, 2)->nullable();
        $table->decimal('shipping_fee', 12, 2)->default(0);
    });
    Schema::table('order_items', function (Blueprint $table): void {
        $table->foreignId('order_id');
        $table->unsignedInteger('quantity');
        $table->decimal('subtotal', 12, 2);
        $table->decimal('voucher_discount_allocated', 12, 2)->default(0);
        $table->timestamps();
    });
    Schema::create('order_events', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('order_id');
        $table->foreignId('actor_id')->nullable();
        $table->string('status')->nullable();
        $table->string('title');
        $table->text('note')->nullable();
        $table->boolean('is_public')->default(true);
        $table->timestamps();
    });
    $this->order->forceFill(['items_subtotal' => '1000.00', 'shipping_fee' => '30.00'])->save();
    $this->order->items()->create(['quantity' => 1, 'subtotal' => '1000.00', 'voucher_discount_allocated' => '100.00']);
    $return = app(OrderReturns::class)->open($this->order, $this->buyer->id, [
        'reason' => 'missing_item', 'details' => 'Missing contents.', 'refund_method' => 'gcash',
        'refund_account_name' => 'Buyer Cruz', 'refund_account_number' => '09171234567',
    ], $this->order->items()->get(), []);
    expect($return->items_amount)->toBe('900.00')
        ->and($return->shipping_amount)->toBe('0.00')
        ->and($return->refund_amount)->toBe('900.00')
        ->and($return->commission_reversal)->toBe('90.00');
    expect(app(OrderReturns::class)->approve($return, $this->seller->id))->toBeTrue();
    expect(DB::table('refund_payouts')->where('order_return_id', $return->id)->value('amount'))->toEqual(900);
});

it('refuses money verification in production before creating data', function () {
    $environment = app()->environment();
    $userCount = User::count();
    app()->instance('env', 'production');
    try {
        $this->artisan('money:verify')
            ->expectsOutputToContain('FAIL Production guard: expected non-production; actual production.')
            ->assertFailed();
    } finally {
        app()->instance('env', $environment);
    }
    expect(User::count())->toBe($userCount)->and(DB::transactionLevel())->toBe(0);
});

it('rolls back money verification fixtures when setup fails', function () {
    $userCount = User::count();
    $ledgerCount = DB::table('ledger_entries')->count();
    $originalDisk = Storage::disk('r2');
    $this->artisan('money:verify')->expectsOutputToContain('FAIL Setup:')->assertFailed();
    expect(User::count())->toBe($userCount)
        ->and(DB::table('ledger_entries')->count())->toBe($ledgerCount)
        ->and(DB::transactionLevel())->toBe(0)
        ->and(Storage::disk('r2'))->toBe($originalDisk);
});
