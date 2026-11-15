<?php

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Services\MoneyMovements;
use App\Services\MoneyReceipts;
use App\Services\Notifier;
use App\Services\OrderLedger;
use App\Services\Payouts;
use App\Support\MoneyReferences;
use App\Support\MoneyWords;
use App\Support\Portal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    config(['app.timezone' => 'UTC', 'money.timezone' => 'Asia/Manila']);
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
        $table->foreignId('user_id')->nullable();
        $table->foreignId('logistics_profile_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('status')->default('approved');
        $table->boolean('is_active')->default(true);
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
        foreach (['total_amount', 'commission_amount', 'delivered_shipping_fee', 'cod_collected_amount', 'rider_pay', 'items_subtotal', 'voucher_discount'] as $column) {
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
    $this->buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $this->admin = User::factory()->create(['role' => 'super_admin']);
    $this->hubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $this->hub = $this->hubUser->logisticsProfile()->create(['business_name' => 'Cebu Hub', 'status' => 'approved']);
    $this->riderUser = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);
    $this->rider = CourierProfile::create(['user_id' => $this->riderUser->id, 'logistics_profile_id' => $this->hub->id, 'first_name' => 'Juan', 'last_name' => 'Cruz']);
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

function moneyReceiptRecords(object $context): array
{
    $movements = app(MoneyMovements::class);
    $payouts = app(Payouts::class);
    $cash = $movements->handover($context->hub->id, $context->rider->id, '940.00', null, (string) Str::uuid(), $context->hubUser->id);
    $remittance = $movements->submitRemittance($context->hub->id, null, null, (string) Str::uuid(), $context->hubUser->id);
    $movements->decideRemittance($remittance->reference_no, true, null, $context->admin->id);
    $rider = $payouts->payRider($context->hub->id, $context->rider->id, null, null, (string) Str::uuid(), $context->hubUser->id);
    $seller = $payouts->requestSeller($context->seller->id, '810.00', $context->method->id, (string) Str::uuid());
    $payouts->decideSeller($seller->reference_no, 'approve', null, $context->admin->id);
    $payouts->decideSeller($seller->reference_no, 'pay', null, $context->admin->id);
    $return = OrderReturn::create(['order_id' => $context->order->id, 'buyer_id' => $context->buyer->id, 'seller_id' => $context->seller->id,
        'type' => OrderReturn::TYPE_REFUND_ONLY, 'reason' => 'missing_item', 'details' => 'Missing parcel contents.', 'status' => OrderReturn::STATUS_REFUND_PENDING,
        'refund_amount' => '900.00', 'refund_method' => 'gcash', 'refund_account_name' => 'Buyer Cruz', 'refund_account_number' => '1234']);
    $refund = $payouts->prepareRefund($return);
    $payouts->payRefund($refund->reference_no, $context->admin->id);

    return ['cash-handovers' => $cash, 'remittances' => $remittance, 'rider-payouts' => $rider, 'seller-payouts' => $seller, 'refunds' => $refund];
}

it('renders each official receipt with parties amount words status and an embedded QR', function (string $type, string $amount, string $words) {
    $record = moneyReceiptRecords($this)[$type];
    $response = $this->actingAs($this->admin)->get(route('money.receipts.show', ['type' => $type, 'reference' => $record->reference_no]));
    $response->assertSuccessful()->assertSee($record->reference_no)->assertSee($amount)->assertSee($words)->assertSee('Generated by Shopleap')->assertSee('data:image/svg+xml;base64,', false)
        ->assertSee('data:image/png;base64,', false)->assertDontSee('nav-shell')->assertDontSee('workspace-header')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    expect($response->getContent())->not->toMatch('/\b(demo|test|fake|simulated)\b/i');
})->with([
    ['cash-handovers', '₱940.00', 'Nine hundred forty pesos and zero centavos only'],
    ['remittances', '₱900.00', 'Nine hundred pesos and zero centavos only'],
    ['rider-payouts', '₱20.00', 'Twenty pesos and zero centavos only'],
    ['seller-payouts', '₱810.00', 'Eight hundred ten pesos and zero centavos only'],
    ['refunds', '₱900.00', 'Nine hundred pesos and zero centavos only'],
]);

it('limits receipts to the involved users and admins', function () {
    $records = moneyReceiptRecords($this);
    $receipts = app(MoneyReceipts::class);
    foreach ($records as $type => $record) {
        expect(fn () => $receipts->receipt(User::factory()->create(), $type, $record->reference_no))->toThrow(HttpException::class);
    }
    foreach (['cash-handovers', 'remittances', 'rider-payouts'] as $type) {
        expect($receipts->receipt($this->hubUser, $type, $records[$type]->reference_no)['reference'])->toBe($records[$type]->reference_no);
    }
    foreach (['cash-handovers', 'rider-payouts'] as $type) {
        expect($receipts->receipt($this->riderUser, $type, $records[$type]->reference_no)['reference'])->toBe($records[$type]->reference_no);
    }
    expect($receipts->receipt($this->seller, 'seller-payouts', $records['seller-payouts']->reference_no)['to'])->toBe($this->seller->name);
    expect($receipts->receipt($this->buyer, 'refunds', $records['refunds']->reference_no)['to'])->toBe($this->buyer->name);
    $this->actingAs($this->buyer)->get(route('money.receipts.show', ['type' => 'seller-payouts', 'reference' => $records['seller-payouts']->reference_no]))->assertForbidden();
    $this->get(route('money.receipts.show', ['type' => 'refunds', 'reference' => (string) $records['refunds']->id]))->assertNotFound();
});

it('lets the hub and authenticated rider open their own receipts on their portals', function () {
    $records = moneyReceiptRecords($this);
    $this->withSession(['portal_host' => Portal::host('logistics')])->actingAs($this->hubUser)
        ->get(route('logistics.receipts.show', ['type' => 'cash-handovers', 'reference' => $records['cash-handovers']->reference_no]))->assertSuccessful();
    Sanctum::actingAs($this->riderUser);
    $this->get(Portal::url('main', 'api/courier/receipts/rider-payouts/'.$records['rider-payouts']->reference_no))->assertSuccessful();
    $this->get(Portal::url('main', 'api/courier/receipts/seller-payouts/'.$records['seller-payouts']->reference_no))->assertNotFound();
});

it('shows receipt actions filters and empty states on all money histories', function () {
    $records = moneyReceiptRecords($this);
    foreach ([['admin.payouts.index', ['status' => 'all']], ['admin.refunds.index', ['status' => 'all']], ['admin.movements.index', ['section' => 'remittances', 'status' => 'all']]] as [$name, $parameters]) {
        $this->actingAs($this->admin)->get(route($name, $parameters))->assertSuccessful()->assertSee('View receipt')->assertSee('From date')->assertSee('To date')->assertSee('Export CSV')->assertSee('overflow-x-auto', false);
    }
    $this->actingAs($this->seller)->get(route('seller.payouts.index'))->assertSuccessful()->assertSee($records['seller-payouts']->reference_no)->assertSee('View receipt');
    foreach ([['logistics.payouts.index', []], ['logistics.movements.index', ['section' => 'cash']], ['logistics.movements.index', ['section' => 'remittances']]] as [$name, $parameters]) {
        $this->withSession(['portal_host' => Portal::host('logistics')])->actingAs($this->hubUser)->get(route($name, $parameters))->assertSuccessful()->assertSee('View receipt');
    }
    $this->withSession(['portal_host' => Portal::host('main')])->actingAs($this->seller)->get(Portal::route('main', 'seller.payouts.index', ['from' => '2099-01-01']))->assertSuccessful()->assertSee('No matching payouts.');
});

it('applies date boundaries and statuses to both histories and exports', function () {
    $record = moneyReceiptRecords($this)['seller-payouts'];
    DB::table('seller_payouts')->where('id', $record->id)->update(['requested_at' => '2026-11-15 18:00:00']);
    $this->actingAs($this->seller)->get(route('seller.payouts.index', ['from' => '2026-11-16', 'to' => '2026-11-16', 'status' => 'paid']))->assertSuccessful()->assertSee($record->reference_no);
    $this->get(route('seller.payouts.index', ['to' => '2026-11-15']))->assertSuccessful()->assertDontSee($record->reference_no);
    $this->get(route('seller.payouts.index', ['status' => 'pending']))->assertSuccessful()->assertDontSee($record->reference_no);
    $csv = $this->get(route('seller.payouts.index', ['export' => 'csv', 'from' => '2026-11-16', 'to' => '2026-11-16', 'status' => 'paid']))->assertSuccessful()->streamedContent();
    expect($csv)->toContain($record->reference_no, '810.00', 'Paid', '1234');
    $excluded = $this->get(route('seller.payouts.index', ['export' => 'csv', 'to' => '2026-11-15']))->assertSuccessful()->streamedContent();
    expect($excluded)->not->toContain($record->reference_no);
    $this->getJson(route('seller.payouts.index', ['from' => '2026-11-16', 'to' => '2026-11-15']))->assertUnprocessable();
});

it('exports hub histories and keeps all filtered records across pagination', function () {
    $records = moneyReceiptRecords($this);
    $this->withSession(['portal_host' => Portal::host('logistics')])->actingAs($this->hubUser);
    foreach ([['logistics.movements.index', ['section' => 'cash'], 'cash-handovers'], ['logistics.movements.index', ['section' => 'remittances'], 'remittances'], ['logistics.payouts.index', [], 'rider-payouts']] as [$name, $parameters, $type]) {
        expect($this->get(route($name, $parameters + ['export' => 'csv']))->assertSuccessful()->streamedContent())->toContain($records[$type]->reference_no);
    }
    $values = (array) DB::table('seller_payouts')->first();
    unset($values['id'], $values['reference_no'], $values['request_key']);
    foreach (range(1, 30) as $index) {
        MoneyReferences::insert('seller_payouts', 'SP', ['request_key' => (string) Str::uuid()] + $values);
    }
    $this->withSession(['portal_host' => Portal::host('main')])->actingAs($this->seller);
    $csv = $this->get(Portal::route('main', 'seller.payouts.index', ['export' => 'csv']))->assertSuccessful()->streamedContent();
    expect(substr_count($csv, 'SP-'))->toBe(31);
});

it('preserves complete ledger running balances while filtering statuses and exporting', function () {
    moneyReceiptRecords($this);
    $this->actingAs($this->admin)->get(route('admin.ledger.index'))->assertSuccessful()->assertSee('Export ledger CSV')->assertSee('All statuses');
    $csv = $this->get(route('admin.ledger.index', ['export' => 'csv', 'money_status' => 'posted']))->assertSuccessful()->streamedContent();
    expect($csv)->toContain('Running account balance (PHP)', 'Completed');
    $this->get(route('admin.ledger.index', ['money_status' => 'reversed']))->assertSuccessful()->assertSee('No transactions found');
});

it('spells exact peso and centavo amounts without optional extensions', function (string $amount, string $words) {
    expect(MoneyWords::pesos($amount))->toBe($words);
})->with([
    ['0.00', 'Zero pesos and zero centavos only'], ['1.01', 'One peso and one centavo only'],
    ['1234.56', 'One thousand two hundred thirty-four pesos and fifty-six centavos only'],
    ['9999999999.99', 'Nine billion nine hundred ninety-nine million nine hundred ninety-nine thousand nine hundred ninety-nine pesos and ninety-nine centavos only'],
]);

it('protects CSV cells and removes prohibited wording from financial documents', function () {
    $record = moneyReceiptRecords($this)['seller-payouts'];
    DB::table('seller_payouts')->where('id', $record->id)->update(['account_name' => '=SUM(1,2)', 'rejection_reason' => 'Simulated transfer']);
    $csv = $this->actingAs($this->seller)->get(route('seller.payouts.index', ['export' => 'csv']))->assertSuccessful()->streamedContent();
    expect($csv)->toContain("'=SUM(1,2)")->not->toMatch('/\b(demo|test|fake|simulated)\b/i');
    $receipt = $this->get(route('money.receipts.show', ['type' => 'seller-payouts', 'reference' => $record->reference_no]))->assertSuccessful();
    expect($receipt->getContent())->not->toMatch('/\b(demo|test|fake|simulated)\b/i');
});
