<?php

use App\Models\BuyerAddress;
use App\Models\HubServiceArea;
use App\Models\Order;
use App\Models\Product;
use App\Models\PsgcBarangay;
use App\Models\User;
use App\Services\HubCoverage;
use App\Services\ParcelLifecycle;
use App\Services\ShippingFeeCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    (require database_path('migrations/2026_09_02_152328_create_buyer_profiles_table.php'))->up();
    (require database_path('migrations/2026_11_02_000000_create_psgc_barangays_table.php'))->up();
    (require database_path('migrations/2026_11_04_191243_create_buyer_addresses_table.php'))->up();
    Schema::create('hub_service_areas', function (Blueprint $table) {
        $table->id();
        $table->string('province');
    });
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        foreach (['seller_id', 'buyer_id', 'origin_hub_id', 'destination_hub_id', 'shop_voucher_id'] as $field) {
            $table->unsignedBigInteger($field)->nullable();
        }
        foreach (['buyer_name', 'status', 'shipping_zone', 'ship_province', 'ship_municipality', 'ship_barangay', 'ship_street', 'ship_contact_no', 'buyer_note', 'voucher_code'] as $field) {
            $table->string($field)->nullable();
        }
        foreach (['total_amount', 'commission_amount', 'shipping_fee', 'voucher_discount'] as $field) {
            $table->decimal($field, 12, 2)->default(0);
        }
        $table->timestamps();
    });
    (require database_path('migrations/2026_11_04_191245_add_address_snapshot_to_orders_table.php'))->up();
    Http::preventStrayRequests();
    Http::fake([
        '*/provinces/' => Http::response([['name' => 'Cebu', 'code' => '072200000']]),
        '*/regions/' => Http::response([]),
        '*/provinces/072200000/cities-municipalities/' => Http::response([['name' => 'Cebu City', 'code' => '072217000'], ['name' => 'Mandaue City', 'code' => '072230000']]),
        '*/cities-municipalities/*/barangays/' => Http::response([['name' => 'Lahug', 'code' => '072217001']]),
    ]);
});

function addressBuyer(): User
{
    $buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $buyer->buyerProfile()->create(['last_name' => 'Buyer', 'first_name' => 'Test', 'sex' => 'female', 'contact_no' => '09123456789', 'birthday' => '1990-01-01', 'age' => 36,
        'province' => 'cebu', 'municipality' => 'Cebu City', 'barangay' => 'Lahug', 'house_number' => '10', 'street' => 'Old Street', 'additional_address' => 'Blue gate', 'id_upload_path' => 'test.jpg', 'status' => 'approved']);

    return $buyer;
}

function addressPayload(): array
{
    return ['label' => 'Home', 'recipient_name' => 'Recipient', 'phone' => '09123456789', 'province' => '  cebu ', 'city_municipality' => 'cebu city', 'barangay' => 'lahug', 'street_address' => '20 New Street', 'postal_code' => '6000', 'notes' => 'Red gate'];
}

it('creates a canonical first default and manages defaults transactionally', function () {
    $buyer = addressBuyer();
    $this->actingAs($buyer)->postJson(route('buyer.addresses.store'), addressPayload())->assertOk()->assertJsonPath('addresses.0.is_default', true)->assertJsonPath('addresses.0.province', 'Cebu');
    $first = $buyer->addresses()->first();
    $this->postJson(route('buyer.addresses.store'), array_merge(addressPayload(), ['label' => 'Work']))->assertOk();
    $second = $buyer->addresses()->latest('id')->first();
    expect($second->is_default)->toBeFalse();
    $this->patchJson(route('buyer.addresses.default', $second))->assertOk();
    expect($first->fresh()->is_default)->toBeFalse();
    expect($buyer->addresses()->where('is_default', true)->count())->toBe(1);
    $this->putJson(route('buyer.addresses.update', $first), array_merge(addressPayload(), ['is_default' => true]))->assertOk();
    expect($first->fresh()->is_default)->toBeTrue();
    expect($second->fresh()->is_default)->toBeFalse();
});

it('isolates every address operation by owner', function () {
    $buyer = addressBuyer();
    $other = addressBuyer();
    $address = BuyerAddress::factory()->for($other)->create();
    $this->actingAs($buyer)->getJson(route('buyer.addresses.index'))->assertJsonCount(0, 'addresses');
    $this->putJson(route('buyer.addresses.update', $address), addressPayload())->assertForbidden();
    $this->patchJson(route('buyer.addresses.default', $address))->assertForbidden();
    $this->deleteJson(route('buyer.addresses.destroy', $address))->assertForbidden();
    $this->postJson(route('buyer.checkout.store'), ['address_id' => $address->id])->assertUnprocessable()->assertJsonValidationErrors('address_id');
    $this->getJson(route('buyer.checkout.index', ['address_id' => $address->id]))->assertUnprocessable()->assertJsonValidationErrors('address_id');
});

it('requires an address and rejects forged location hierarchies and invalid fields', function () {
    $this->actingAs(addressBuyer())->postJson(route('buyer.checkout.store'), [])->assertUnprocessable()->assertJsonValidationErrors('address_id');
    foreach (['province', 'city_municipality', 'barangay'] as $field) {
        $this->postJson(route('buyer.addresses.store'), array_merge(addressPayload(), [$field => 'Forged location']))->assertUnprocessable()->assertJsonValidationErrors($field);
    }
    $this->postJson(route('buyer.addresses.store'), array_merge(addressPayload(), ['phone' => 'bad', 'postal_code' => '123']))->assertUnprocessable()->assertJsonValidationErrors(['phone', 'postal_code']);
});

it('soft deletes and promotes the newest address but protects the last address', function () {
    $buyer = addressBuyer();
    $first = BuyerAddress::factory()->for($buyer)->create();
    $next = BuyerAddress::factory()->for($buyer)->create(['is_default' => false]);
    $this->actingAs($buyer)->deleteJson(route('buyer.addresses.destroy', $first))->assertOk();
    expect($first->fresh()->trashed())->toBeTrue();
    expect($next->fresh()->is_default)->toBeTrue();
    $this->deleteJson(route('buyer.addresses.destroy', $next))->assertUnprocessable()->assertJsonValidationErrors('address');
});

it('backfills a default without deleting or duplicating legacy data', function () {
    $buyer = addressBuyer();
    DB::table('hub_service_areas')->insert(['province' => 'Cebu']);
    $migration = require database_path('migrations/2026_11_04_191244_backfill_buyer_addresses.php');
    $migration->up();
    $migration->up();
    expect($buyer->addresses()->count())->toBe(1);
    $address = $buyer->addresses()->first();
    expect($address->province)->toBe('Cebu')->and($address->street_address)->toBe('10 Old Street')->and($address->notes)->toBe('Blue gate')->and($address->is_default)->toBeTrue();
    expect($buyer->buyerProfile->fresh()->street)->toBe('Old Street');
});

it('keeps the order destination immutable after address edits and soft deletion', function () {
    $buyer = addressBuyer();
    $address = BuyerAddress::factory()->for($buyer)->create();
    $order = Order::create(['buyer_id' => $buyer->id, 'address_id' => $address->id, 'shipping_address' => $address->snapshot(), 'ship_province' => $address->province]);
    $snapshot = $order->ship_to;
    $address->update(['province' => 'Bohol', 'recipient_name' => 'Changed']);
    $address->delete();
    expect($order->fresh()->ship_to)->toBe($snapshot);
    expect(HubServiceArea::nameKey($order->fresh()->ship_to['province']))->toBe('cebu');
});

it('renders the shared modal on profile and checkout with safe initial data', function () {
    $buyer = addressBuyer();
    $address = BuyerAddress::factory()->for($buyer)->create(['recipient_name' => '<script>alert(1)</script>']);
    $this->actingAs($buyer);
    $html = view('components.buyer-addresses', ['addresses' => $buyer->addresses()->get(), 'checkout' => true, 'selected' => $address->id])->render();
    expect($html)->toContain('addressBook(', 'address-modal-title', 'name="address_id"', 'x-teleport="body"')->not->toContain('<script>alert(1)</script>');
});

function addressCheckoutCart(User $buyer): array
{
    (require database_path('migrations/2026_09_05_161506_create_products_table.php'))->up();
    (require database_path('migrations/2026_09_12_030731_create_cart_items_table.php'))->up();
    (require database_path('migrations/2026_09_20_152710_create_product_variants_table.php'))->up();
    Schema::table('products', function (Blueprint $table) {
        $table->decimal('weight_kg')->nullable();
    });
    Schema::table('cart_items', function (Blueprint $table) {
        $table->unsignedBigInteger('product_variant_id')->nullable();
        $table->json('selected_options')->nullable();
    });
    Schema::create('seller_profiles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('province');
        $table->string('municipality');
        $table->string('business_name')->nullable();
        $table->timestamps();
    });
    Schema::create('product_discounts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->boolean('is_active');
        $table->timestamp('starts_at')->nullable();
        $table->timestamp('ends_at')->nullable();
    });
    Schema::create('order_items', function (Blueprint $table) {
        $table->id();
        foreach (['order_id', 'product_id', 'product_variant_id', 'quantity'] as $field) {
            $table->unsignedBigInteger($field)->nullable();
        }
        foreach (['product_name', 'selected_options'] as $field) {
            $table->text($field)->nullable();
        }
        foreach (['weight_kg', 'price_at_purchase', 'original_price', 'subtotal', 'voucher_discount_allocated'] as $field) {
            $table->decimal($field, 12, 3)->nullable();
        }
        $table->timestamps();
    });
    $seller = User::factory()->create(['role' => 'seller']);
    $seller->sellerProfile()->create(['province' => 'Cebu', 'municipality' => 'Cebu City']);
    $product = Product::create(['seller_id' => $seller->id, 'name' => 'Test product', 'category' => 'General', 'price' => 100, 'stock_quantity' => 10, 'status' => 'active', 'weight_kg' => 1]);
    $item = $buyer->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);

    return [$seller, $item];
}

it('requotes shipping and coverage for the selected address without changing the cart', function () {
    $buyer = addressBuyer();
    [$seller, $item] = addressCheckoutCart($buyer);
    $home = BuyerAddress::factory()->for($buyer)->create();
    $work = BuyerAddress::factory()->for($buyer)->create(['city_municipality' => 'Mandaue City', 'is_default' => false]);
    Schema::create('shipping_rates', function (Blueprint $table) {
        $table->id();
        $table->string('zone');
        $table->decimal('fee');
        $table->boolean('is_active');
    });
    $this->mock(HubCoverage::class, function ($mock) {
        $mock->shouldReceive('isCovered')->andReturnUsing(fn ($seller, $province, $city, $barangay) => $city === 'Cebu City');
    });
    $shipping = app(ShippingFeeCalculator::class);
    $homeFee = $shipping->calculate($seller, $home->province, $home->city_municipality)['fee'];
    $workFee = $shipping->calculate($seller, $work->province, $work->city_municipality)['fee'];
    $this->actingAs($buyer)->getJson(route('buyer.checkout.index', ['items' => $item->id]))->assertOk()->assertJsonPath('shippingTotal', fn ($value) => (float) $value === $homeFee)->assertJsonPath('blocked', []);
    $this->getJson(route('buyer.checkout.index', ['items' => $item->id, 'address_id' => $work->id]))->assertOk()->assertJsonPath('shippingTotal', fn ($value) => (float) $value === $workFee)->assertJsonPath('blocked.0', $seller->id);
    expect($homeFee)->not->toBe($workFee);
    expect($buyer->cartItems()->count())->toBe(1);
});

it('places an order using only the owned address and snapshots every destination field', function () {
    $buyer = addressBuyer();
    [$seller, $item] = addressCheckoutCart($buyer);
    $address = BuyerAddress::factory()->for($buyer)->create(['recipient_name' => 'Actual Recipient', 'phone' => '09999999999', 'notes' => 'Side entrance']);
    Cache::forever('platform_setting:commission_rate', 10);
    $this->mock(ShippingFeeCalculator::class, function ($mock) {
        $mock->shouldReceive('calculate')->once()->withArgs(fn ($seller, $province, $city) => $province === 'Cebu' && $city === 'Cebu City')->andReturn(['fee' => 45.0, 'zone' => 'same_municipality']);
    });
    $this->mock(HubCoverage::class, function ($mock) {
        $mock->shouldReceive('routeFor')->once()->withArgs(fn ($seller, $province, $city, $barangay) => $province === 'Cebu' && $city === 'Cebu City' && $barangay === 'Lahug')->andReturn(['origin' => (object) ['id' => 1], 'destination' => (object) ['id' => 2]]);
    });
    $this->mock(ParcelLifecycle::class, function ($mock) {
        $mock->shouldReceive('placed')->once();
    });
    $this->actingAs($buyer)->post(route('buyer.checkout.store'), ['items' => $item->id, 'address_id' => $address->id, 'province' => 'Forged', 'recipient_name' => 'Forged', 'shipping_fee' => 0, 'notes' => [$seller->id => 'Buyer note']])->assertRedirect(route('buyer.orders.index'));
    $order = Order::firstOrFail();
    expect($order->address_id)->toBe($address->id)->and($order->ship_to)->toBe($address->snapshot())->and($order->ship_province)->toBe('Cebu')->and((float) $order->shipping_fee)->toBe(45.0)->and($order->buyer_note)->toBe('Buyer note');
    $address->update(['province' => 'Bohol']);
    $address->delete();
    expect($order->fresh()->ship_to['province'])->toBe('Cebu');
    expect($buyer->cartItems()->count())->toBe(0);
});

it('uses the existing local PSGC mirror when the location API is unavailable', function () {
    PsgcBarangay::create(['province_code' => '072200000', 'province' => 'Cebu', 'municipality_code' => '072217000', 'municipality' => 'Cebu City', 'barangay_code' => '072217001', 'barangay' => 'Lahug']);
    Http::swap(new Factory);
    Http::fake(['*' => Http::response([], 503)]);
    $this->actingAs(addressBuyer())->postJson(route('buyer.addresses.store'), addressPayload())->assertOk()->assertJsonPath('addresses.0.province', 'Cebu');
});

it('preserves the legacy NCR spelling used by hub routing', function () {
    PsgcBarangay::create(['province_code' => '130000000', 'province' => 'Metro Manila', 'municipality_code' => '137404000', 'municipality' => 'Quezon City', 'barangay_code' => '137404001', 'barangay' => 'Poblacion']);
    Http::swap(new Factory);
    Http::fake(['*' => Http::response([], 503)]);
    $data = array_merge(addressPayload(), ['province' => 'metro manila', 'city_municipality' => 'Quezon City', 'barangay' => 'Poblacion']);
    $this->actingAs(addressBuyer())->postJson(route('buyer.addresses.store'), $data)->assertOk()->assertJsonPath('addresses.0.province', 'Metro Manila');
});

it('does not accept a soft deleted address at checkout', function () {
    $buyer = addressBuyer();
    $address = BuyerAddress::factory()->for($buyer)->create();
    $address->delete();
    $this->actingAs($buyer)->postJson(route('buyer.checkout.store'), ['address_id' => $address->id])->assertUnprocessable()->assertJsonValidationErrors('address_id');
});
