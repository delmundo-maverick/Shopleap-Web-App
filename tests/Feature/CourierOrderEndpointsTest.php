<?php

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    foreach (['buyer_profiles', 'seller_profiles'] as $tableName) {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
        });
    }
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
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->string('order_number')->unique();
        $table->string('waybill_code')->unique();
        foreach (['buyer_id', 'seller_id', 'pickup_rider_id', 'delivery_rider_id', 'logistics_profile_id', 'origin_hub_id', 'current_hub_id'] as $column) {
            $table->foreignId($column)->nullable();
        }
        $table->string('status');
        $table->string('pickup_status');
        $table->date('pickup_date')->nullable();
        $table->string('pickup_time_slot')->nullable();
        $table->timestamp('waybill_printed_at')->nullable();
        $table->json('shipping_address');
        foreach (['total', 'total_amount', 'shipping_fee'] as $column) {
            $table->decimal($column, 12, 2);
        }
        $table->timestamps();
    });
    Schema::create('order_items', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('order_id');
        $table->string('product_name');
        $table->json('selected_options');
        $table->unsignedInteger('quantity');
        $table->decimal('weight_kg', 12, 3);
        $table->timestamps();
    });

    $hubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $hub = $hubUser->logisticsProfile()->create(['business_name' => 'Cebu Sorting Hub', 'status' => 'approved']);
    $courierUser = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);
    $courier = CourierProfile::create([
        'user_id' => $courierUser->id, 'logistics_profile_id' => $hub->id,
        'first_name' => 'Juan', 'last_name' => 'Cruz', 'status' => 'approved', 'is_active' => true,
    ]);
    $this->courier = $courier;
    $this->courierUser = $courierUser;
    $this->buyer = User::factory()->create(['name' => 'Maria Buyer', 'role' => 'buyer', 'account_status' => 'active']);
    $seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $this->order = Order::create([
        'buyer_id' => $this->buyer->id, 'seller_id' => $seller->id,
        'pickup_rider_id' => $courier->id, 'delivery_rider_id' => $courier->id,
        'logistics_profile_id' => $hub->id, 'origin_hub_id' => $hub->id, 'current_hub_id' => $hub->id,
        'status' => Order::STATUS_READY_FOR_PICKUP, 'pickup_status' => 'assigned',
        'total' => '1239.00', 'total_amount' => '1199.00', 'shipping_fee' => '40.00',
        'shipping_address' => [
            'name' => $this->buyer->name, 'contact_no' => '09123456789',
            'street' => '123 Main Street', 'barangay' => 'Lahug', 'municipality' => 'Cebu City', 'province' => 'Cebu',
        ],
    ]);
    $this->order->items()->create([
        'product_name' => 'Wireless Gaming Headphones', 'selected_options' => ['Color' => 'White'],
        'quantity' => 1, 'weight_kg' => '1.000',
    ]);
    Sanctum::actingAs($courierUser);
});

it('returns the real buyer, COD amount and item for an assigned courier order', function (string $endpoint, string $payloadPath) {
    $endpoint = str_replace('{code}', $this->order->waybill_code, $endpoint);
    $response = $this->getJson('/api/courier/'.$endpoint)->assertSuccessful();

    $response->assertJsonPath($payloadPath.'.buyer.name', $this->buyer->name)
        ->assertJsonPath($payloadPath.'.payment.method', 'cod')
        ->assertJsonPath($payloadPath.'.payment.amount_to_collect', '1239.00')
        ->assertJsonCount(1, $payloadPath.'.items')
        ->assertJsonPath($payloadPath.'.items.0.name', 'Wireless Gaming Headphones')
        ->assertJsonPath($payloadPath.'.items.0.variant', 'Color: White')
        ->assertJsonPath($payloadPath.'.items.0.quantity', 1)
        ->assertJsonPath($payloadPath.'.parcel_weight_kg', '1.00');
})->with([
    'pickups' => ['pickups', 'data.0'],
    'pickups awaiting acceptance' => ['pickups?filter=awaiting_accept', 'data.0'],
    'scanned order' => ['orders/scan/{code}', 'data'],
]);

it('resolves supported scan references while preserving the buyer and pickup destination', function (string $format) {
    $reference = match ($format) {
        'order number' => $this->order->order_number,
        'prefixed QR' => 'SHOPLEAP:ORDER:'.$this->order->waybill_code,
        'tracking URL' => 'https://shopleap.shop/track/'.$this->order->waybill_code,
        'URL query' => 'https://shopleap.shop/track/'.$this->order->waybill_code.'?print=1',
        'URL trailing slash' => 'https://shopleap.shop/track/'.$this->order->waybill_code.'/?print=1',
        'encoded reference' => 'https://shopleap.shop/track/'.str_replace('-', '%2D', $this->order->waybill_code).'?print=1',
        'lowercase' => strtolower($this->order->waybill_code),
    };
    $this->getJson('/api/courier/orders/scan/'.rawurlencode($reference))->assertSuccessful()
        ->assertJsonPath('data.order_number', $this->order->order_number)
        ->assertJsonPath('data.buyer.name', $this->buyer->name)
        ->assertJsonPath('data.buyer.phone', '09123456789')
        ->assertJsonPath('data.buyer.address', '123 Main Street, Lahug, Cebu City, Cebu')
        ->assertJsonPath('data.payment.amount_to_collect', '1239.00')
        ->assertJsonPath('data.hub_name', 'Cebu Sorting Hub')
        ->assertJsonPath('data.deliver_to_hub', 'Cebu Sorting Hub');
})->with(['order number', 'prefixed QR', 'tracking URL', 'URL query', 'URL trailing slash', 'encoded reference', 'lowercase']);

it('recognizes stored legacy label codes without treating database IDs as scan codes', function (string $storedCode, string $reference) {
    $this->order->forceFill(['waybill_code' => $storedCode])->save();
    $this->getJson('/api/courier/orders/scan/'.rawurlencode($reference))->assertSuccessful()
        ->assertJsonPath('data.order_number', $this->order->order_number);
})->with([
    'short printed code' => ['0000000123', '123'],
    'padded barcode' => ['0000000123', '0000000123'],
    'legacy QR' => ['0000000123', 'SHOPLEAP:ORDER:123'],
    'stored short code' => ['123', '123'],
    'stored short QR' => ['123', 'SHOPLEAP:ORDER:123'],
]);

it('returns assigned active deliveries with the shared payload', function (string $status) {
    $this->order->update(['status' => $status, 'pickup_rider_id' => null]);
    $this->getJson('/api/courier/orders/scan/'.$this->order->waybill_code)->assertSuccessful()
        ->assertJsonPath('data.status', $status)
        ->assertJsonPath('data.buyer.name', $this->buyer->name)
        ->assertJsonPath('data.payment.amount_to_collect', '1239.00')
        ->assertJsonPath('data.items.0.name', 'Wireless Gaming Headphones')
        ->assertJsonPath('data.hub_name', 'Cebu Sorting Hub')
        ->assertJsonPath('data.deliver_to_hub', null);
})->with([Order::STATUS_ASSIGNED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY]);

it('does not expose parcels outside the couriers active assignment and hub', function (array $attributes) {
    $this->order->update($attributes);
    $response = $this->getJson('/api/courier/orders/scan/'.$this->order->waybill_code)->assertNotFound();
    expect($response->getContent())->not->toContain($this->buyer->name, 'Wireless Gaming Headphones');
})->with([
    'unassigned' => [['pickup_rider_id' => null, 'delivery_rider_id' => null]],
    'other pickup rider' => [['pickup_rider_id' => 999]],
    'other pickup hub' => [['origin_hub_id' => 999]],
    'other delivery rider despite prior pickup' => [['status' => Order::STATUS_OUT_FOR_DELIVERY, 'delivery_rider_id' => 999]],
    'other delivery hub' => [['status' => Order::STATUS_OUT_FOR_DELIVERY, 'current_hub_id' => 999]],
    'cancelled' => [['status' => Order::STATUS_CANCELLED]],
    'completed' => [['status' => Order::STATUS_COMPLETED]],
]);

it('rejects unknown codes and arbitrary database IDs', function (string $reference) {
    $reference = $reference === 'database ID' ? (string) $this->order->id : $reference;
    $this->getJson('/api/courier/orders/scan/'.rawurlencode($reference))->assertNotFound();
})->with(['WB-UNKNOWN', 'database ID', 'https://shopleap.shop/track/?print=1', 'SHOPLEAP:BAG:UNKNOWN']);

it('requires authentication for the scan endpoint', function () {
    auth()->forgetGuards();
    $this->getJson('/api/courier/orders/scan/'.$this->order->waybill_code)->assertUnauthorized();
});

it('requires an approved active courier', function (array $attributes) {
    $this->courier->update($attributes);
    $this->getJson('/api/courier/orders/scan/'.$this->order->waybill_code)->assertForbidden();
})->with(['pending' => [['status' => 'pending']], 'inactive' => [['is_active' => false]]]);
