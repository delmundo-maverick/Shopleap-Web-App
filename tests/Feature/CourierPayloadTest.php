<?php

use App\Http\Controllers\Api\Courier\DeliveryController;
use App\Http\Controllers\Api\Courier\PickupController;
use App\Http\Controllers\Logistics\InboundController;
use App\Models\BuyerProfile;
use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function courierPayloadOrder(array $attributes = []): Order
{
    $order = new Order;
    $order->forceFill($attributes + [
        'order_number' => 'SL-TEST1234', 'waybill_code' => 'WB-TEST123456',
        'total' => '1239.00', 'total_amount' => '1199.00', 'shipping_fee' => '40.00',
        'shipping_address' => [
            'name' => 'Snapshot Buyer', 'contact_no' => '09123456789',
            'street' => '123 Main Street', 'barangay' => 'Lahug',
            'municipality' => 'Cebu City', 'province' => 'Cebu',
        ],
        'created_at' => now(),
    ]);
    $order->setRelation('buyer', null);
    $order->setRelation('items', new Collection([
        new OrderItem(['product_name' => 'Shirt', 'selected_options' => ['Size' => 'M', 'Color' => 'Blue'], 'quantity' => 2, 'weight_kg' => '0.750']),
        new OrderItem(['product_name' => 'Hat', 'quantity' => 1, 'weight_kg' => '0.250']),
    ]));

    return $order;
}

it('uses checkout snapshots and line weights without multiplying by quantity again', function () {
    expect(courierPayloadOrder()->courierPayload())->toBe([
        'payment' => ['method' => 'cod', 'amount_to_collect' => '1239.00', 'currency' => 'PHP'],
        'buyer' => ['name' => 'Snapshot Buyer', 'phone' => '09123456789', 'address' => '123 Main Street, Lahug, Cebu City, Cebu'],
        'items' => [
            ['name' => 'Shirt', 'variant' => 'Size: M, Color: Blue', 'quantity' => 2],
            ['name' => 'Hat', 'variant' => '', 'quantity' => 1],
        ],
        'parcel_weight_kg' => '1.00',
    ]);
});

it('normalizes COD aliases and leaves peso amounts in pesos', function (string $method) {
    expect(courierPayloadOrder(['payment_method' => $method])->courierPayload()['payment'])
        ->toBe(['method' => 'cod', 'amount_to_collect' => '1239.00', 'currency' => 'PHP']);
})->with(['cod', 'COD', 'cash_on_delivery']);

it('collects zero for prepaid orders even when their total is unknown', function () {
    expect(courierPayloadOrder(['payment_method' => 'card', 'total' => null, 'total_amount' => null])->courierPayload()['payment'])
        ->toBe(['method' => 'card', 'amount_to_collect' => '0.00', 'currency' => 'PHP']);
});

it('uses legacy merchandise plus shipping only when both amounts are known', function (array $amounts, ?string $expected) {
    expect(courierPayloadOrder($amounts)->courierPayload()['payment']['amount_to_collect'])->toBe($expected);
})->with([
    'legacy total' => [['total' => null], '1239.00'],
    'zero snapshot wins' => [['total' => '0.00'], '0.00'],
    'missing merchandise' => [['total' => null, 'total_amount' => null], null],
    'missing shipping' => [['total' => null, 'shipping_fee' => null], null],
    'all missing' => [['total' => null, 'total_amount' => null, 'shipping_fee' => null], null],
]);

it('retains the legacy buyer profile fallback', function () {
    $buyer = User::factory()->make(['name' => 'Legacy Buyer']);
    $buyer->setRelation('buyerProfile', new BuyerProfile([
        'contact_no' => '09999999999', 'province' => 'Cebu', 'municipality' => 'Cebu City',
        'barangay' => 'Lahug', 'house_number' => '12', 'street' => 'Old Street',
    ]));
    $order = courierPayloadOrder(['shipping_address' => null]);
    $order->setRelation('buyer', $buyer);

    expect($order->courierPayload()['buyer'])->toBe([
        'name' => 'Legacy Buyer', 'phone' => '09999999999', 'address' => '12 Old Street, Lahug, Cebu City, Cebu',
    ]);
});

it('shares the payload across delivery and pickup lists without per-order queries', function () {
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    foreach (['buyer_profiles', 'seller_profiles'] as $tableName) {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
        });
    }
    Schema::create('logistics_profiles', function (Blueprint $table): void {
        $table->id();
        $table->string('business_name');
    });
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        foreach (['buyer_id', 'seller_id', 'delivery_rider_id', 'pickup_rider_id', 'current_hub_id', 'origin_hub_id', 'logistics_profile_id'] as $column) {
            $table->unsignedBigInteger($column)->nullable();
        }
        foreach (['order_number', 'waybill_code', 'status', 'delivery_area', 'ship_municipality', 'ship_barangay', 'pickup_status', 'pickup_date', 'pickup_time_slot', 'buyer_note', 'waybill_printed_at'] as $column) {
            $table->string($column)->nullable();
        }
        $table->unsignedInteger('delivery_attempts')->default(0);
        $table->json('shipping_address');
        foreach (['total', 'total_amount', 'shipping_fee'] as $column) {
            $table->decimal($column, 12, 2)->nullable();
        }
        $table->timestamps();
    });
    Schema::create('order_items', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->string('product_name');
        $table->json('selected_options')->nullable();
        $table->unsignedInteger('quantity');
        $table->decimal('weight_kg', 12, 3)->nullable();
    });

    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    DB::table('logistics_profiles')->insert(['id' => 1, 'business_name' => 'Cebu Sorting Hub']);
    $courier = new CourierProfile;
    $courier->forceFill(['id' => 1, 'logistics_profile_id' => 1]);
    $request = Request::create('/api/courier/deliveries');
    $request->attributes->set('courier', $courier);
    $order = courierPayloadOrder();
    $order->forceFill(['buyer_id' => $buyer->id, 'seller_id' => $seller->id,
        'delivery_rider_id' => 1, 'pickup_rider_id' => 1, 'logistics_profile_id' => 1,
        'status' => Order::STATUS_ASSIGNED_TO_RIDER]);
    $order->save();
    DB::table('order_items')->insert(['order_id' => $order->id, 'product_name' => 'Shirt', 'quantity' => 2, 'weight_kg' => '1.000']);

    $delivery = app(DeliveryController::class)->index($request)->getData(true)['data'][0];
    expect($delivery['cod_amount'])->toBe('1239.00')
        ->and($delivery['buyer']['contact_no'])->toBe($delivery['buyer']['phone'])
        ->and($delivery)->toHaveKeys(['order_id', 'order_number', 'waybill_code', 'status', 'hub_id', 'delivery_area', 'note_from_buyer', 'attempts', 'max_attempts', 'items_count', 'weight_kg', 'seller']);

    $order->update(['status' => Order::STATUS_READY_FOR_PICKUP, 'pickup_status' => 'assigned']);
    $pickup = app(PickupController::class)->index($request)->getData(true)['data'][0];
    foreach (['payment', 'buyer', 'items', 'parcel_weight_kg'] as $key) {
        expect($pickup[$key])->toBe($key === 'buyer' ? array_intersect_key($delivery[$key], array_flip(['name', 'phone', 'address'])) : $delivery[$key]);
    }
    expect($pickup)->toHaveKeys(['pickup_status', 'hub_name', 'pickup_date', 'pickup_window', 'seller', 'deliver_to_hub', 'items_count', 'weight_kg', 'waybill_printed']);
    expect($pickup['buyer']['name'])->toBe('Snapshot Buyer')
        ->and($pickup['deliver_to_hub'])->toBe('Cebu Sorting Hub')
        ->and($pickup['hub_name'])->toBe('Cebu Sorting Hub');

    $filteredRequest = Request::create('/api/courier/pickups?filter=awaiting_accept');
    $filteredRequest->attributes->set('courier', $courier);
    expect(app(PickupController::class)->index($filteredRequest)->getData(true)['data'])->toBe([$pickup]);
    $order->update(['pickup_status' => 'accepted']);
    expect(app(PickupController::class)->index($filteredRequest)->getData(true)['data'])->toBe([])
        ->and(app(PickupController::class)->index($request)->getData(true)['data'][0]['buyer']['name'])->toBe('Snapshot Buyer');

    $scanRequest = Request::create('/logistics/inbound/scan', 'POST', server: ['HTTP_ACCEPT' => 'application/json']);
    $scanResponse = new ReflectionMethod(InboundController::class, 'scanResponse');
    $scan = $scanResponse->invoke(app(InboundController::class), $scanRequest, true, 'Parcel received.', $order->id)->getData(true);
    foreach (['payment', 'buyer', 'items', 'parcel_weight_kg'] as $key) {
        expect($scan[$key])->toBe($pickup[$key]);
    }
    expect($scan)->toHaveKeys(['success', 'message', 'order_id', 'waybill_code', 'type', 'reference'])
        ->and($scan['order_id'])->toBe($order->order_number);

    $duplicate = $order->replicate();
    $duplicate->save();
    DB::enableQueryLog();
    app(PickupController::class)->index($request);
    $listQueryCount = count(DB::getQueryLog());
    DB::flushQueryLog();
    $duplicate->delete();
    DB::flushQueryLog();
    app(PickupController::class)->index($request);
    expect(count(DB::getQueryLog()))->toBe($listQueryCount);
    DB::disableQueryLog();
});

it('prints shared values while retaining waybill presentation', function (array $attributes, string $amount, string $payment) {
    $order = courierPayloadOrder($attributes);
    $seller = User::factory()->make();
    $seller->setRelation('sellerProfile', null);
    $order->setRelation('seller', $seller);
    $html = view('seller.orders.waybill', [
        'order' => $order, 'courierPayload' => $order->courierPayload(),
        'orderCode' => 'WB-TEST', 'barcodeBase64' => '', 'qrBase64' => '',
    ])->render();

    expect($html)->toContain('Snapshot Buyer', '123 Main Street, Lahug, Cebu City, Cebu', '09123456789', 'Size: M, Color: Blue', 'Parcel weight: 1.00 kg', $amount, $payment);
})->with([
    'COD' => [[], '₱1,239.00', 'Cash on Delivery'],
    'prepaid' => [['payment_method' => 'card'], '₱0.00', 'Prepaid'],
    'unknown COD total' => [['total' => null, 'total_amount' => null], '₱—', 'Cash on Delivery'],
]);
