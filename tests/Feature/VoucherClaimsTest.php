<?php

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopVoucher;
use App\Models\ShopVoucherRedemption;
use App\Models\User;
use App\Models\VoucherClaim;
use App\Services\ShopVoucherService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    (require database_path('migrations/2026_09_02_152328_create_buyer_profiles_table.php'))->up();
    Schema::create('seller_profiles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('business_name')->nullable();
        $table->string('status')->default('approved');
        $table->timestamps();
    });
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('buyer_id')->nullable();
        $table->unsignedBigInteger('shop_voucher_id')->nullable();
        $table->string('status')->nullable();
        $table->timestamps();
    });
    (require database_path('migrations/2026_10_29_000000_create_seller_followers_table.php'))->up();
    (require database_path('migrations/2026_10_27_152854_create_shop_vouchers_table.php'))->up();
    (require database_path('migrations/2026_10_27_152858_create_shop_voucher_redemptions_table.php'))->up();
    (require database_path('migrations/2026_11_04_194657_create_voucher_claims_table.php'))->up();
    (require database_path('migrations/2026_11_04_194658_add_claim_limit_to_shop_vouchers_table.php'))->up();
});

function voucherBuyer(): User
{
    $buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $buyer->buyerProfile()->create(['last_name' => 'Buyer', 'first_name' => 'Test', 'sex' => 'female', 'contact_no' => '09123456789', 'birthday' => '1990-01-01', 'age' => 36,
        'province' => 'Cebu', 'municipality' => 'Cebu City', 'barangay' => 'Lahug', 'house_number' => '10', 'street' => 'Street', 'id_upload_path' => 'test.jpg', 'status' => 'approved']);

    return $buyer;
}

function claimableVoucher(array $attributes = []): ShopVoucher
{
    $seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $seller->sellerProfile()->create(['business_name' => 'Test Shop', 'status' => 'approved']);

    return ShopVoucher::factory()->create(array_merge(['seller_id' => $seller->id], $attributes));
}

function voucherCart(ShopVoucher $voucher): Collection
{
    $product = new Product(['seller_id' => $voucher->seller_id, 'name' => 'Product', 'price' => 100, 'category' => 'Clothes']);
    $product->id = 10;
    $product->setRelation('liveDiscount', null);
    $item = new CartItem(['product_id' => 10, 'quantity' => 2]);
    $item->setRelation('product', $product)->setRelation('variant', null);

    return collect([$item]);
}

it('does not auto assign vouchers and requires following even for crafted requests', function () {
    $buyer = voucherBuyer();
    $voucher = claimableVoucher();
    $this->actingAs($buyer)->getJson(route('buyer.vouchers.dropdown'))->assertOk()->assertJsonCount(0, 'available');
    $this->postJson(route('buyer.vouchers.claim', $voucher))->assertUnprocessable()->assertJsonValidationErrors('voucher');
    expect(VoucherClaim::count())->toBe(0);
    $buyer->followedSellers()->attach($voucher->seller_id);
    $this->postJson(route('buyer.vouchers.claim', $voucher))->assertOk();
    $this->getJson(route('buyer.vouchers.dropdown'))->assertJsonCount(1, 'available');
    $this->postJson(route('buyer.vouchers.claim', $voucher))->assertUnprocessable();
    expect(VoucherClaim::count())->toBe(1);
});

it('serializes claim quota and rejects another buyer after the last slot', function () {
    $voucher = claimableVoucher(['claim_limit' => 1]);
    $first = voucherBuyer();
    $second = voucherBuyer();
    foreach ([$first, $second] as $buyer) {
        $buyer->followedSellers()->attach($voucher->seller_id);
    }
    $this->actingAs($first)->postJson(route('buyer.vouchers.claim', $voucher))->assertOk()->assertJsonPath('remaining', 0);
    $this->actingAs($second)->postJson(route('buyer.vouchers.claim', $voucher))->assertUnprocessable()->assertJsonValidationErrors('voucher');
    expect($voucher->claims()->count())->toBe(1);
});

it('rejects expired inactive future exhausted and ineligible shop vouchers', function (array $attributes) {
    $buyer = voucherBuyer();
    $voucher = claimableVoucher($attributes);
    $buyer->followedSellers()->attach($voucher->seller_id);
    $this->actingAs($buyer)->postJson(route('buyer.vouchers.claim', $voucher))->assertUnprocessable();
})->with([
    [['expires_at' => '2000-01-01']], [['is_active' => false]], [['starts_at' => '2099-01-01']], [['used_quantity' => 1, 'total_quantity' => 1]],
]);

it('allows claims after unfollowing but enforces claim ownership usage and cart eligibility', function () {
    $buyer = voucherBuyer();
    $other = voucherBuyer();
    $voucher = claimableVoucher();
    $buyer->followedSellers()->attach($voucher->seller_id);
    $service = app(ShopVoucherService::class);
    $claim = $service->claim($buyer, $voucher);
    $buyer->followedSellers()->detach($voucher->seller_id);
    expect($service->validateAndApplyForCart($buyer, $voucher->code, voucherCart($voucher))['discount'])->toBe(20.0);
    expect(fn () => $service->validateAndApplyForCart($other, $voucher->code, voucherCart($voucher)))->toThrow(Exception::class, 'Claim this voucher');
    $voucher->update(['min_purchase_amount' => 500]);
    expect(fn () => $service->validateAndApplyForCart($buyer, $voucher->code, voucherCart($voucher)))->toThrow(Exception::class, 'minimum purchase');
    $voucher->update(['min_purchase_amount' => 0]);
    $different = claimableVoucher();
    expect(fn () => $service->validateAndApplyForCart($buyer, $voucher->code, voucherCart($different)))->toThrow(Exception::class, 'not applicable');
    $claim->update(['used_at' => now()]);
    expect(fn () => $service->validateAndApplyForCart($buyer, $voucher->code, voucherCart($voucher)))->toThrow(Exception::class, 'unused claimed');
});

it('backfills only redeemed pairs and preserves history without following', function () {
    $buyer = voucherBuyer();
    $voucher = claimableVoucher();
    $unredeemed = claimableVoucher();
    $order = Order::create(['buyer_id' => $buyer->id]);
    ShopVoucherRedemption::create(['buyer_id' => $buyer->id, 'seller_id' => $voucher->seller_id, 'shop_voucher_id' => $voucher->id, 'order_id' => $order->id, 'status' => 'used', 'used_at' => now()]);
    $migration = require database_path('migrations/2026_11_04_194659_backfill_redeemed_voucher_claims.php');
    $migration->up();
    $migration->up();
    expect(VoucherClaim::count())->toBe(1);
    expect($voucher->claims()->first()->order_id)->toBe($order->id);
    expect($unredeemed->claims()->count())->toBe(0);
    expect(app(ShopVoucherService::class)->getRedeemedVouchersForBuyer($buyer))->toHaveCount(1);
});

it('restores the claim and redemption quota only once when an order is cancelled', function () {
    $buyer = voucherBuyer();
    $voucher = claimableVoucher(['total_quantity' => 1, 'used_quantity' => 1, 'is_active' => false, 'status' => 'exhausted']);
    $order = Order::create(['buyer_id' => $buyer->id, 'shop_voucher_id' => $voucher->id]);
    $claim = $buyer->voucherClaims()->create(['voucher_id' => $voucher->id, 'claimed_at' => now(), 'used_at' => now(), 'order_id' => $order->id]);
    $redemption = ShopVoucherRedemption::create(['buyer_id' => $buyer->id, 'seller_id' => $voucher->seller_id, 'shop_voucher_id' => $voucher->id, 'order_id' => $order->id, 'status' => 'used']);
    app(ShopVoucherService::class)->restoreForCancelledOrder($order);
    app(ShopVoucherService::class)->restoreForCancelledOrder($order);
    expect($claim->fresh()->used_at)->toBeNull()->and($claim->fresh()->order_id)->toBeNull();
    expect($voucher->fresh()->used_quantity)->toBe(0)->and($voucher->fresh()->is_active)->toBeTrue();
    expect($redemption->fresh()->status)->toBe('cancelled');
});

it('renders the reusable card safely for shop and wallet states', function () {
    $buyer = voucherBuyer();
    $voucher = claimableVoucher(['name' => '<script>bad</script>', 'claim_limit' => 1]);
    $this->actingAs($buyer);
    $voucher->loadCount('claims');
    $html = view('components.voucher-card', ['voucher' => $voucher, 'shop' => true, 'claim' => null, 'redemption' => null, 'expired' => false])->render();
    expect($html)->toContain('Follow to claim', 'voucherCard(')->not->toContain('<script>bad</script>');
});
