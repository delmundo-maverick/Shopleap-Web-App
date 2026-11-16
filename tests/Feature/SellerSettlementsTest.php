<?php

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Services\LedgerService;
use App\Services\MoneyMovementQueries;
use App\Services\MoneyMovements;
use App\Services\MoneyReport;
use App\Services\OrderLedger;
use App\Services\Payouts;
use App\Services\SellerSettlements;
use App\Support\Portal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('r2');
    Notification::fake();
    config()->set('money.logistics_seller_settlements', true);
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
        $table->timestamps();
    });
    Schema::create('seller_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('status')->default('approved');
        $table->timestamps();
    });
    Schema::create('buyer_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->string('order_number')->unique();
        $table->string('waybill_code')->unique();
        foreach (['seller_id', 'buyer_id', 'earning_hub_id', 'earning_courier_id'] as $column) {
            $table->foreignId($column);
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
        $table->foreignId('order_id');
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
    foreach (['2026_10_04_180040_create_platform_settings_table.php', '2026_10_21_201427_create_order_returns_tables.php',
        '2026_10_02_171814_create_order_events_table.php', '2026_10_31_030000_create_parcel_events_table.php',
        '2026_10_22_161655_create_notifications_table.php', '2026_11_15_142304_create_money_ledger_tables.php',
        '2026_11_15_152144_create_money_movement_tables.php', '2026_11_15_162541_create_payout_tables.php',
        '2026_11_15_162542_initialize_payout_settings_and_refunds.php', '2026_11_16_171812_create_seller_settlements_tables.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $this->seller->sellerProfile()->create(['status' => 'approved']);
    $this->buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $this->buyer->buyerProfile()->create(['status' => 'approved']);
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->hubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $this->hub = $this->hubUser->logisticsProfile()->create(['business_name' => 'Settlement Hub', 'status' => 'approved']);
    $this->rider = CourierProfile::create(['logistics_profile_id' => $this->hub->id, 'first_name' => 'Juan', 'last_name' => 'Cruz']);
    $this->order = new Order;
    $this->order->forceFill(['seller_id' => $this->seller->id, 'buyer_id' => $this->buyer->id, 'earning_hub_id' => $this->hub->id,
        'earning_courier_id' => $this->rider->id, 'status' => Order::STATUS_DELIVERED,
        'total_amount' => '900.00', 'commission_amount' => '90.00', 'delivered_shipping_fee' => '40.00',
        'cod_collected_amount' => '940.00', 'rider_pay' => '20.00', 'delivered_at' => now(), 'earnings_recorded_at' => now()])->save();
    app(OrderLedger::class)->delivered($this->order);
    app(Payouts::class)->addMethod($this->seller->id, ['type' => 'GCash', 'account_name' => 'Seller', 'account_number_masked' => '1234']);
    $this->method = DB::table('seller_payout_methods')->first();
    $this->settlements = app(SellerSettlements::class);
});

function readySellerSettlement(object $test): void
{
    $test->order->update(['status' => Order::STATUS_COMPLETED]);
    app(OrderLedger::class)->completed($test->order);
    app(MoneyMovements::class)->handover($test->hub->id, $test->rider->id, '940.00', null, (string) Str::uuid(), $test->hubUser->id);
}

function createSellerSettlement(object $test, ?string $key = null): object
{
    return $test->settlements->create($test->hubUser, $test->seller->id, $test->method->id, [$test->order->order_number], $key ?? (string) Str::uuid());
}

function settlementRefund(object $test): OrderReturn
{
    $return = OrderReturn::create(['order_id' => $test->order->id, 'buyer_id' => $test->buyer->id, 'seller_id' => $test->seller->id,
        'type' => OrderReturn::TYPE_REFUND_ONLY, 'reason' => 'missing_item', 'details' => 'Refund required',
        'status' => OrderReturn::STATUS_REFUND_PENDING, 'items_amount' => '900.00', 'refund_amount' => '900.00', 'commission_reversal' => '90.00',
        'refund_method' => 'gcash', 'refund_account_name' => 'Buyer', 'refund_account_number' => '1234']);
    app(OrderLedger::class)->returnChanged($return);

    return $return;
}

it('routes only seller proceeds to the frozen hub and keeps commission separately remittable', function () {
    expect(app(MoneyReport::class)->balances('hub', $this->hub->id))->toMatchArray(['cod_to_remit' => '90.00', 'seller_payable' => '810.00'])
        ->and(app(MoneyReport::class)->balances('seller', $this->seller->id))->toMatchArray(['pending' => '810.00', 'hub_receivable' => '810.00'])
        ->and(app(MoneyReport::class)->balances('platform', 0)['cod_receivable'])->toBe('90.00');
    expect((string) app(MoneyMovementQueries::class)->remittable($this->hub->id)->first()->amount)->toBe('90');
    app(OrderLedger::class)->delivered($this->order);
    $this->assertDatabaseCount('seller_settlement_orders', 1);
});

it('requires both completion and full cash receipt before preparing seller payment', function () {
    expect($this->settlements->eligible()->count())->toBe(0);
    $this->order->update(['status' => Order::STATUS_COMPLETED]);
    app(OrderLedger::class)->completed($this->order);
    expect($this->settlements->eligible()->count())->toBe(0);
    $handover = app(MoneyMovements::class)->handover($this->hub->id, $this->rider->id, '900.00', 'Short', (string) Str::uuid(), $this->hubUser->id);
    expect($this->settlements->eligible()->count())->toBe(0);
    app(MoneyMovements::class)->settleShortage($this->hub->id, $handover->reference_no, 'Cash received', $this->hubUser->id);
    expect($this->settlements->eligible()->count())->toBe(1);
});

it('completes logistics to seller payment without an admin and without duplicate ledger postings', function () {
    readySellerSettlement($this);
    $key = (string) Str::uuid();
    $record = createSellerSettlement($this, $key);
    expect(createSellerSettlement($this, $key)->id)->toBe($record->id)
        ->and((string) $record->amount)->toBe('810')->and($record->status)->toBe('pending');
    expect($this->settlements->orderAmount($this->order->id, 'seller', 'hub_receivable'))->toBe(81000);
    $this->settlements->send($this->hubUser, $record->reference_no, 'BANK-001', 'proof.pdf');
    expect(DB::table('seller_settlements')->where('id', $record->id)->value('status'))->toBe('sent')
        ->and(app(MoneyReport::class)->balances('hub', $this->hub->id))->toMatchArray(['cash_held' => '130.00', 'cash_in_transit' => '810.00']);
    $this->settlements->send($this->hubUser, $record->reference_no, 'BANK-001', 'duplicate.pdf');
    $paid = $this->settlements->confirm($this->seller, $record->reference_no);
    expect($paid->status)->toBe('paid')->and(app(MoneyReport::class)->balances('seller', $this->seller->id))
        ->toMatchArray(['withdrawn' => '810.00', 'available' => '0.00', 'payout_locked' => '0.00', 'hub_receivable' => '0.00']);
    $count = DB::table('ledger_transactions')->count();
    $this->settlements->confirm($this->seller, $record->reference_no);
    expect(DB::table('ledger_transactions')->count())->toBe($count)
        ->and(app(MoneyReport::class)->balances('platform', 0)['cod_held'])->toBe('0.00');
    expect(fn () => createSellerSettlement($this))->toThrow(ValidationException::class);
});

it('prevents duplicate settlement and legacy platform payout of logistics seller funds', function () {
    readySellerSettlement($this);
    expect(fn () => app(Payouts::class)->requestSeller($this->seller->id, '810.00', $this->method->id, (string) Str::uuid()))->toThrow(ValidationException::class);
    createSellerSettlement($this);
    expect(fn () => createSellerSettlement($this))->toThrow(ValidationException::class);
});

it('prevents another hub or seller from accessing settlement actions', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    $other = User::factory()->create(['role' => 'logistics']);
    $other->logisticsProfile()->create(['business_name' => 'Other Hub', 'status' => 'approved']);
    expect(fn () => $this->settlements->send($other, $record->reference_no, 'BANK-X', 'proof.pdf'))->toThrow(HttpException::class);
    $otherSeller = User::factory()->create(['role' => 'seller']);
    expect(fn () => $this->settlements->confirm($otherSeller, $record->reference_no))->toThrow(HttpException::class)
        ->and(fn () => $this->settlements->confirm($this->hubUser, $record->reference_no))->toThrow(HttpException::class);
});

it('keeps disputed money locked until seller confirmation or an evidenced admin resolution', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    $this->settlements->send($this->hubUser, $record->reference_no, 'BANK-002', 'proof.pdf');
    $this->settlements->dispute($this->seller, $record->reference_no, 'Money not received');
    expect(fn () => $this->settlements->cancel($this->hubUser, $record->reference_no, 'Retry'))->toThrow(ValidationException::class)
        ->and(fn () => $this->settlements->resolve($this->admin, $record->reference_no, 'returned', 'Investigated', ''))->toThrow(ValidationException::class);
    $cancelled = $this->settlements->resolve($this->admin, $record->reference_no, 'returned', 'Bank verified the transfer was returned to logistics', 'bank-return.pdf');
    expect($cancelled->status)->toBe('cancelled')->and($this->settlements->eligible()->count())->toBe(1)
        ->and(app(MoneyReport::class)->balances('hub', $this->hub->id))->toMatchArray(['cash_held' => '940.00', 'cash_in_transit' => '0.00']);
});

it('remits only commission and pays merchandise refunds from logistics including recovery after seller payment', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    $this->settlements->send($this->hubUser, $record->reference_no, 'BANK-003', 'proof.pdf');
    $this->settlements->confirm($this->seller, $record->reference_no);
    $remittance = app(MoneyMovements::class)->submitRemittance($this->hub->id, null, null, (string) Str::uuid(), $this->hubUser->id);
    expect(LedgerService::cents((string) $remittance->total_amount))->toBe(9000);
    app(MoneyMovements::class)->decideRemittance($remittance->reference_no, true, null, $this->admin->id);
    $return = settlementRefund($this);
    $refund = app(Payouts::class)->prepareRefund($return);
    expect($this->settlements->orderAmount($this->order->id, 'seller', 'hub_receivable'))->toBe(-81000);
    expect(fn () => app(Payouts::class)->payRefund($refund->reference_no, $this->admin->id))->toThrow(HttpException::class);
    $this->settlements->recover($this->hubUser, $this->order->id, 'RECOVERY-1', 'recovery.pdf');
    $this->settlements->returnCommission($this->admin, $this->order->id, 'COMM-RETURN-1', 'commission-return.pdf');
    app(Payouts::class)->payRefund($refund->reference_no, $this->hubUser->id, $this->hub->id, 'REFUND-1', 'refund.pdf');
    expect(app(MoneyReport::class)->balances('hub', $this->hub->id))->toMatchArray(['cash_held' => '40.00', 'seller_payable' => '0.00', 'cod_to_remit' => '0.00'])
        ->and(app(MoneyReport::class)->balances('seller', $this->seller->id))->toMatchArray(['available' => '0.00', 'hub_receivable' => '0.00'])
        ->and(app(MoneyReport::class)->balances('platform', 0))->toMatchArray(['cod_held' => '0.00', 'cod_receivable' => '0.00']);
    $unbalanced = DB::table('ledger_entries')->select('transaction_id')->groupBy('transaction_id')->havingRaw("ROUND(SUM(CASE WHEN direction = 'debit' THEN amount ELSE -amount END), 2) <> 0")->get();
    expect($unbalanced)->toHaveCount(0);
});

it('rolls back reservations and routing with the transaction', function () {
    readySellerSettlement($this);
    DB::beginTransaction();
    createSellerSettlement($this);
    DB::rollBack();
    $this->assertDatabaseCount('seller_settlements', 0);
    expect($this->settlements->eligible()->count())->toBe(1)
        ->and(app(MoneyReport::class)->balances('seller', $this->seller->id)['available'])->toBe('810.00');
});

it('does not reroute historical deliveries', function () {
    config()->set('money.logistics_seller_settlements', false);
    $legacy = $this->order->replicate(['order_number', 'waybill_code']);
    $legacy->save();
    app(OrderLedger::class)->delivered($legacy);
    config()->set('money.logistics_seller_settlements', true);
    app(OrderLedger::class)->delivered($legacy);
    expect($this->settlements->isDirect($legacy->id))->toBeFalse();
});

it('renders the scoped settlement screen for each participant and admin', function (string $role, string $portal) {
    readySellerSettlement($this);
    createSellerSettlement($this);
    $user = match ($role) {
        'seller' => $this->seller, 'logistics' => $this->hubUser, default => $this->admin
    };
    $this->actingAs($user);
    $name = ($role === 'super_admin' ? 'admin' : $role).'.settlements.index';
    $this->get(Portal::url($portal, ltrim(route($name, [], false), '/')))->assertSuccessful()
        ->assertSee('Logistics seller payments')->assertSee('810.00')->assertSee('Settlement Hub');
})->with([['seller', 'main'], ['logistics', 'logistics'], ['super_admin', 'admin']]);

it('validates evidence and protects payment proof from unrelated users', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    $this->actingAs($this->hubUser);
    $sendUrl = Portal::url('logistics', 'logistics/money/seller-settlements/'.$record->reference_no.'/send');
    $this->post($sendUrl, ['transfer_reference' => 'WEB-1'])->assertSessionHasErrors('proof');
    $this->post($sendUrl, ['transfer_reference' => 'WEB-1', 'proof' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])->assertRedirect()->assertSessionHasNoErrors();
    $stored = DB::table('seller_settlements')->where('id', $record->id)->first();
    Storage::disk('r2')->assertExists($stored->proof_path);
    $this->withSession(['portal_host' => Portal::host('main')])->actingAs($this->seller);
    $this->get(Portal::url('main', 'seller/settlements/'.$record->reference_no.'/proof'))->assertSuccessful();
    $otherSeller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $otherSeller->sellerProfile()->create(['status' => 'approved']);
    $this->actingAs($otherSeller);
    $this->get(Portal::url('main', 'seller/settlements/'.$record->reference_no.'/proof'))->assertForbidden();
});

it('shows overdue eligible orders and payments to admin without auto-confirming them', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    $this->travel(4)->days();
    $this->actingAs($this->admin);
    $this->get(Portal::url('admin', ltrim(route('admin.settlements.index', ['status' => 'overdue'], false), '/')))
        ->assertSuccessful()->assertSee($record->reference_no);
    expect(DB::table('seller_settlements')->where('id', $record->id)->value('status'))->toBe('pending');
});

it('blocks a transfer when a refund changes entitlement and releases the reservation on cancellation', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    settlementRefund($this);
    expect(fn () => $this->settlements->send($this->hubUser, $record->reference_no, 'STALE-1', 'proof.pdf'))->toThrow(ValidationException::class);
    $this->settlements->cancel($this->hubUser, $record->reference_no, 'Refund approved');
    expect(app(MoneyReport::class)->balances('seller', $this->seller->id))->toMatchArray(['available' => '0.00', 'payout_locked' => '0.00']);
});

it('reverses logistics refund liabilities when an approved return is cancelled', function () {
    readySellerSettlement($this);
    $return = settlementRefund($this);
    $return->update(['status' => OrderReturn::STATUS_CANCELLED]);
    app(OrderLedger::class)->returnChanged($return);
    expect(app(MoneyReport::class)->balances('seller', $this->seller->id))->toMatchArray(['available' => '810.00', 'hub_receivable' => '810.00'])
        ->and(app(MoneyReport::class)->balances('hub', $this->hub->id))->toMatchArray(['seller_payable' => '810.00', 'cod_to_remit' => '90.00']);
});

it('allows evidenced admin resolution of an overdue sent payment while forbidding routine approval', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    expect(fn () => $this->settlements->resolve($this->admin, $record->reference_no, 'paid', 'Investigated', 'proof.pdf'))->toThrow(ValidationException::class);
    $this->settlements->send($this->hubUser, $record->reference_no, 'OVERDUE-TRANSFER', 'proof.pdf');
    expect(fn () => $this->settlements->resolve($this->admin, $record->reference_no, 'paid', 'Investigated', 'proof.pdf'))->toThrow(ValidationException::class);
    $this->travel(4)->days();
    expect($this->settlements->resolve($this->admin, $record->reference_no, 'paid', 'Bank confirmed seller receipt', 'bank-evidence.pdf')->status)->toBe('paid');
});

it('keeps partial refunds payable from logistics and only pays the remaining seller entitlement', function () {
    readySellerSettlement($this);
    $return = OrderReturn::create(['order_id' => $this->order->id, 'buyer_id' => $this->buyer->id, 'seller_id' => $this->seller->id,
        'type' => OrderReturn::TYPE_REFUND_ONLY, 'reason' => 'missing_item', 'details' => 'Partial refund',
        'status' => OrderReturn::STATUS_REFUND_PENDING, 'items_amount' => '100.00', 'refund_amount' => '100.00', 'commission_reversal' => '10.00',
        'refund_method' => 'gcash', 'refund_account_name' => 'Buyer', 'refund_account_number' => '1234']);
    app(OrderLedger::class)->returnChanged($return);
    $refund = app(Payouts::class)->prepareRefund($return);
    app(Payouts::class)->payRefund($refund->reference_no, $this->hubUser->id, $this->hub->id, 'PARTIAL-REFUND', 'proof.pdf');
    $record = createSellerSettlement($this);
    expect(LedgerService::cents((string) $record->amount))->toBe(72000);
    $this->settlements->send($this->hubUser, $record->reference_no, 'PARTIAL-SELLER', 'proof.pdf');
    $this->settlements->confirm($this->seller, $record->reference_no);
    expect(app(MoneyReport::class)->balances('hub', $this->hub->id))->toMatchArray(['cash_held' => '120.00', 'cod_to_remit' => '80.00', 'seller_payable' => '0.00']);
});

it('rejects reusing external transfer references across seller payments and refunds', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    $this->settlements->send($this->hubUser, $record->reference_no, 'UNIQUE-REFERENCE', 'proof.pdf');
    $this->settlements->confirm($this->seller, $record->reference_no);
    $return = settlementRefund($this);
    $refund = app(Payouts::class)->prepareRefund($return);
    expect(fn () => app(Payouts::class)->payRefund($refund->reference_no, $this->hubUser->id, $this->hub->id, 'UNIQUE-REFERENCE', 'proof.pdf'))->toThrow(ValidationException::class);
});

it('allows only genuinely restored entitlements after a refund recovery is reversed', function () {
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    $this->settlements->send($this->hubUser, $record->reference_no, 'RESTORE-SELLER-1', 'proof.pdf');
    $this->settlements->confirm($this->seller, $record->reference_no);
    $remittance = app(MoneyMovements::class)->submitRemittance($this->hub->id, null, null, (string) Str::uuid(), $this->hubUser->id);
    app(MoneyMovements::class)->decideRemittance($remittance->reference_no, true, null, $this->admin->id);
    $return = settlementRefund($this);
    $this->settlements->recover($this->hubUser, $this->order->id, 'RESTORE-RECOVERY', 'proof.pdf');
    $this->settlements->returnCommission($this->admin, $this->order->id, 'RESTORE-COMMISSION', 'proof.pdf');
    $return->update(['status' => OrderReturn::STATUS_EXPIRED]);
    app(OrderLedger::class)->returnChanged($return);
    expect($this->settlements->eligible()->count())->toBe(1)
        ->and(app(MoneyMovementQueries::class)->remittable($this->hub->id)->count())->toBe(1);
    $restored = createSellerSettlement($this);
    expect(LedgerService::cents((string) $restored->amount))->toBe(81000);
});

it('removes the general admin payout navigation for logistics managed payments', function () {
    readySellerSettlement($this);
    createSellerSettlement($this);
    expect($this->settlements->hasLegacyPlatformPayouts())->toBeFalse();
    $this->actingAs($this->admin);
    $this->get(route('admin.settlements.index'))->assertSuccessful()
        ->assertSee('Seller settlement oversight')
        ->assertDontSee(route('admin.payouts.index', [], false), false);
});

it('redirects the unused admin seller payout page to settlement oversight', function () {
    $this->actingAs($this->admin);
    $this->get(route('admin.payouts.index'))->assertRedirect(route('admin.settlements.index'));
});

it('retains a clearly marked legacy page when historical seller funds still need payment', function () {
    config()->set('money.logistics_seller_settlements', false);
    $legacy = $this->order->replicate(['order_number', 'waybill_code']);
    $legacy->status = Order::STATUS_COMPLETED;
    $legacy->save();
    app(OrderLedger::class)->delivered($legacy);
    app(OrderLedger::class)->completed($legacy);
    expect($this->settlements->hasLegacyPlatformPayouts())->toBeTrue();
    $this->actingAs($this->admin);
    $this->get(route('admin.settlements.index'))->assertSuccessful()->assertSee('Legacy platform payout records and remaining obligations');
    $this->get(route('admin.payouts.index'))->assertSuccessful()->assertSee('Legacy platform seller payouts')->assertSee('historical platform-managed seller funds only');
});

it('follows settlement receipt and evidence links on separate role hosts', function (string $role, string $portal) {
    config()->set('portals.admin', 'http://admin.localhost');
    foreach (app('router')->getRoutes() as $route) {
        if (str_starts_with($route->getName() ?? '', 'admin.')) {
            $route->domain('admin.localhost');
        }
    }
    readySellerSettlement($this);
    $record = createSellerSettlement($this);
    Storage::disk('r2')->put('proof.pdf', 'Payment evidence');
    $this->settlements->send($this->hubUser, $record->reference_no, 'HOST-TRANSFER', 'proof.pdf');
    $actor = match ($role) {
        'seller' => $this->seller, 'logistics' => $this->hubUser, default => $this->admin
    };
    $this->withSession(['portal_host' => Portal::host($portal)])->actingAs($actor);
    $page = $this->get(route($role.'.settlements.index'))->assertSuccessful();
    $html = new DOMDocument;
    @$html->loadHTML($page->getContent());
    $checked = 0;
    foreach ($html->getElementsByTagName('a') as $link) {
        if (in_array(trim($link->textContent), ['View payment record', 'Download transfer evidence'], true)) {
            $url = html_entity_decode($link->getAttribute('href'));
            expect(parse_url($url, PHP_URL_HOST))->toBe(Portal::host($portal));
            $this->get($url)->assertSuccessful();
            $checked++;
        }
    }
    expect($checked)->toBe(2);
})->with([['seller', 'main'], ['logistics', 'logistics'], ['admin', 'admin']]);

it('removes cancelled return requests from the actionable logistics refund queue', function () {
    readySellerSettlement($this);
    $return = settlementRefund($this);
    $refund = app(Payouts::class)->prepareRefund($return);
    $return->update(['status' => OrderReturn::STATUS_CANCELLED]);
    app(OrderLedger::class)->returnChanged($return);
    $this->withSession(['portal_host' => Portal::host('logistics')])->actingAs($this->hubUser);
    $this->get(route('logistics.settlements.index'))->assertSuccessful()->assertDontSee($refund->reference_no);
});

it('completes the normal cashflow through the actual portal form endpoints', function () {
    $this->withSession(['portal_host' => Portal::host('main')])->actingAs($this->buyer);
    $this->post(route('buyer.orders.confirm', $this->order))->assertRedirect(route('buyer.orders.index', ['tab' => 'completed']))->assertSessionHasNoErrors();
    expect($this->order->fresh()->status)->toBe(Order::STATUS_COMPLETED);
    $this->withSession(['portal_host' => Portal::host('logistics')])->actingAs($this->hubUser);
    $this->post(route('logistics.movements.cash.store'), ['rider_id' => $this->rider->id, 'amount' => '940.00', 'request_key' => (string) Str::uuid()])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('logistics.settlements.store'), ['seller_id' => $this->seller->id, 'method_id' => $this->method->id,
        'orders' => [$this->order->order_number], 'request_key' => (string) Str::uuid()])->assertRedirect()->assertSessionHasNoErrors();
    $record = DB::table('seller_settlements')->first();
    $this->post(route('logistics.settlements.send', $record->reference_no), ['transfer_reference' => 'FORM-TRANSFER',
        'proof' => UploadedFile::fake()->create('payment.pdf', 10, 'application/pdf')])->assertRedirect()->assertSessionHasNoErrors();
    $this->withSession(['portal_host' => Portal::host('main')])->actingAs($this->seller);
    $this->post(Portal::url('main', ltrim(route('seller.settlements.confirm', $record->reference_no, false), '/')))->assertRedirect()->assertSessionHasNoErrors();
    $this->withSession(['portal_host' => Portal::host('logistics')])->actingAs($this->hubUser);
    $this->post(route('logistics.movements.remittances.store'), ['all' => 1, 'request_key' => (string) Str::uuid()])->assertRedirect()->assertSessionHasNoErrors();
    $remittance = DB::table('remittances')->first();
    $this->withSession(['portal_host' => Portal::host('admin')])->actingAs($this->admin);
    $this->post(route('admin.movements.remittances.decide', ['reference' => $remittance->reference_no, 'decision' => 'verify']))->assertRedirect()->assertSessionHasNoErrors();
    $this->withSession(['portal_host' => Portal::host('logistics')])->actingAs($this->hubUser);
    $this->post(route('logistics.payouts.store'), ['rider_id' => $this->rider->id, 'all' => 1, 'request_key' => (string) Str::uuid()])->assertRedirect()->assertSessionHasNoErrors();
    expect(app(MoneyReport::class)->balances('seller', $this->seller->id)['withdrawn'])->toBe('810.00')
        ->and(app(MoneyReport::class)->balances('platform', 0)['cod_held'])->toBe('90.00')
        ->and(app(MoneyReport::class)->balances('rider', $this->rider->id)['earnings_paid'])->toBe('20.00')
        ->and(app(MoneyReport::class)->balances('hub', $this->hub->id)['cash_held'])->toBe('20.00');
});

it('matches rendered settlement links and form actions throughout the payment and refund states', function () {
    readySellerSettlement($this);
    $matched = [];
    $check = function (string $role) use (&$matched): void {
        $portal = $role === 'seller' ? 'main' : $role;
        $actor = match ($role) {
            'seller' => $this->seller, 'logistics' => $this->hubUser, default => $this->admin
        };
        $this->withSession(['portal_host' => Portal::host($portal)])->actingAs($actor);
        $url = Portal::url($portal, ltrim(route($role.'.settlements.index', [], false), '/'));
        $response = $this->get($url)->assertSuccessful();
        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        foreach (['a' => 'href', 'form' => 'action'] as $tag => $attribute) {
            foreach ($document->getElementsByTagName($tag) as $element) {
                $target = html_entity_decode($element->getAttribute($attribute));
                if ($target === '' && $tag === 'form') {
                    $target = $url;
                }
                if (! str_starts_with($target, 'http://') && ! str_starts_with($target, 'https://')) {
                    continue;
                }
                if (! in_array(parse_url($target, PHP_URL_HOST), [Portal::host('main'), Portal::host('logistics'), Portal::host('admin')], true)) {
                    continue;
                }
                $method = $tag === 'form' ? strtoupper($element->getAttribute('method') ?: 'GET') : 'GET';
                $route = app('router')->getRoutes()->match(Request::create($target, $method));
                $matched[] = $route->getName();
                if ($tag === 'a' && (str_contains($target, '/receipts/') || str_contains($target, '/proof') || str_contains($target, '/settlement-evidence/'))) {
                    $this->get($target)->assertSuccessful();
                }
            }
        }
    };
    $check('logistics');
    $record = createSellerSettlement($this);
    $check('logistics');
    Storage::disk('r2')->put('audit.pdf', 'Evidence');
    $this->settlements->send($this->hubUser, $record->reference_no, 'AUDIT-TRANSFER', 'audit.pdf');
    $check('seller');
    $this->settlements->dispute($this->seller, $record->reference_no, 'Please investigate');
    $check('admin');
    $this->settlements->confirm($this->seller, $record->reference_no);
    $remittance = app(MoneyMovements::class)->submitRemittance($this->hub->id, null, null, (string) Str::uuid(), $this->hubUser->id);
    app(MoneyMovements::class)->decideRemittance($remittance->reference_no, true, null, $this->admin->id);
    $return = settlementRefund($this);
    $refund = app(Payouts::class)->prepareRefund($return);
    $check('logistics');
    $check('admin');
    $this->settlements->recover($this->hubUser, $this->order->id, 'AUDIT-RECOVERY', 'audit.pdf');
    $this->settlements->returnCommission($this->admin, $this->order->id, 'AUDIT-COMMISSION', 'audit.pdf');
    app(Payouts::class)->payRefund($refund->reference_no, $this->hubUser->id, $this->hub->id, 'AUDIT-REFUND', 'audit.pdf');
    $check('seller');
    $check('logistics');
    $check('admin');
    foreach (['logistics.settlements.store', 'logistics.settlements.send', 'logistics.settlements.cancel',
        'seller.settlements.confirm', 'seller.settlements.dispute', 'admin.settlements.resolve',
        'logistics.settlements.recovery', 'admin.settlements.commission-return', 'logistics.settlements.refund',
        'seller.settlements.evidence', 'logistics.settlements.evidence', 'admin.settlements.evidence'] as $name) {
        expect($matched)->toContain($name);
    }
});
