<?php

use App\Models\BuyerProfile;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('status');
        $table->integer('stock_quantity');
        $table->timestamps();
    });
    Schema::create('product_variants', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->integer('stock_quantity');
        $table->boolean('is_active');
        $table->timestamps();
    });
});

it('returns fresh product and active variant stock without private fields', function () {
    $buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $buyer->setRelation('buyerProfile', new BuyerProfile(['status' => 'approved']));
    $this->actingAs($buyer);
    $product = Product::create(['status' => 'active', 'stock_quantity' => 12]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'stock_quantity' => 7, 'is_active' => true]);
    ProductVariant::create(['product_id' => $product->id, 'stock_quantity' => 99, 'is_active' => false]);
    $url = route('buyer.products.stock', $product);

    $this->getJson($url)->assertOk()->assertExactJson([
        'stock_quantity' => 12,
        'variants' => [['id' => $variant->id, 'stock_quantity' => 7]],
    ])->assertHeader('Cache-Control', 'no-store, private');

    $product->update(['stock_quantity' => 0]);
    $variant->update(['stock_quantity' => 0]);
    $this->getJson($url)->assertOk()->assertJsonPath('stock_quantity', 0)->assertJsonPath('variants.0.stock_quantity', 0);
});

it('does not expose stock for unavailable or missing products', function () {
    $buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $buyer->setRelation('buyerProfile', new BuyerProfile(['status' => 'approved']));
    $this->actingAs($buyer);
    $product = Product::create(['status' => 'draft', 'stock_quantity' => 12]);
    $this->getJson(route('buyer.products.stock', $product))->assertNotFound();
    $this->getJson(route('buyer.products.stock', 999))->assertNotFound();
});

it('requires buyer authentication for stock updates', function () {
    $product = Product::create(['status' => 'active', 'stock_quantity' => 1]);
    $this->getJson(route('buyer.products.stock', $product))->assertUnauthorized();
    $this->actingAs(User::factory()->create(['role' => 'seller']));
    $this->getJson(route('buyer.products.stock', $product))->assertForbidden();
});
