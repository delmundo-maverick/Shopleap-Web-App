<?php

use App\Models\Product;
use App\Models\User;
use App\Services\BuyerCatalogSearch;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
        $table->unsignedInteger('logistics_session_version')->default(0);
    });
    Schema::create('seller_profiles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('business_name');
        $table->string('status')->default('approved');
        $table->timestamps();
    });
    Schema::create('buyer_profiles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('seller_id');
        $table->string('name');
        $table->string('category');
        $table->decimal('price');
        $table->integer('stock_quantity');
        $table->string('status');
        $table->integer('low_stock_threshold');
        $table->timestamps();
    });
    foreach (['product_images', 'product_variants', 'product_discounts', 'reviews'] as $name) {
        Schema::create($name, function (Blueprint $table) use ($name) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            if ($name === 'product_images') {
                $table->integer('sort_order')->default(0);
            } elseif ($name === 'product_discounts') {
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
            } elseif ($name === 'reviews') {
                $table->integer('rating');
            }
        });
    }
    Schema::create('cart_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->integer('quantity');
        $table->timestamps();
    });
    Schema::create('wishlist_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('product_id');
    });
    Schema::create('buyer_addresses', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->boolean('is_default');
        $table->softDeletes();
    });
    (require database_path('migrations/2026_10_22_161655_create_notifications_table.php'))->up();

    $this->buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $this->buyer->buyerProfile()->create(['status' => 'approved']);
});

it('finds active products from partial words, reordered words and small typos', function (string $search) {
    $product = Product::factory()->create(['seller_id' => 99, 'status' => Product::STATUS_ACTIVE, 'name' => 'Premium Wireless Mouse']);
    Product::factory()->create(['seller_id' => 99, 'status' => Product::STATUS_ACTIVE, 'name' => 'Cotton Shirt']);
    Product::factory()->create(['seller_id' => 99, 'name' => 'Wireless Mouse Draft']);

    $matches = app(BuyerCatalogSearch::class)->products(Product::active(), $search)->get();

    expect($matches->modelKeys())->toBe([$product->id]);
})->with(['mouse', 'MOUSE', 'mous', 'mouse wireless', '  wireless   mouse  ', 'wirless mouse', 'wirreless mouse', 'wirekess mouse', 'wrieless mouse']);

it('keeps direct matches ahead of typo alternatives and respects category filters', function () {
    $exact = Product::factory()->create(['seller_id' => 99, 'status' => Product::STATUS_ACTIVE, 'name' => 'Desk Lamp', 'category' => 'Home']);
    $similar = Product::factory()->create(['seller_id' => 99, 'status' => Product::STATUS_ACTIVE, 'name' => 'Camping Gear', 'category' => 'Outdoors']);
    $search = app(BuyerCatalogSearch::class);

    expect($search->products(Product::active(), 'lamp')->get()->modelKeys())->toBe([$exact->id]);
    expect($search->products(Product::active()->where('category', 'Outdoors'), 'lamp')->get()->modelKeys())->toBe([$similar->id]);
});

it('does not treat SQL wildcard characters as a request for every product', function (string $search) {
    Product::factory()->create(['seller_id' => 99, 'status' => Product::STATUS_ACTIVE]);

    expect(app(BuyerCatalogSearch::class)->products(Product::active(), $search)->count())->toBe(0);
})->with(['%', '_', '%%%___']);

it('shows searchable shops even when no product matches and excludes unavailable sellers', function () {
    $buyer = $this->buyer;
    $seller = User::factory()->create(['role' => 'seller']);
    $seller->sellerProfile()->create(['business_name' => 'Sunrise Electronics', 'status' => 'approved']);
    $pending = User::factory()->create(['role' => 'seller']);
    $pending->sellerProfile()->create(['business_name' => 'Sunrise Pending', 'status' => 'pending']);
    $suspended = User::factory()->create(['role' => 'seller', 'account_status' => 'suspended']);
    $suspended->sellerProfile()->create(['business_name' => 'Sunrise Suspended', 'status' => 'approved']);

    $response = $this->actingAs($buyer)->get(route('buyer.products.index', ['search' => 'sunrse']))
        ->assertOk()->assertSee('Shops')->assertSee('Sunrise Electronics')->assertSee('No products found')
        ->assertSee('0 product(s) found')->assertSee('1 shop(s) found')
        ->assertDontSee('@if', false)->assertDontSee('@endif', false)
        ->assertSee(route('buyer.shop.show', $seller), false)
        ->assertDontSee('Sunrise Pending')->assertDontSee('Sunrise Suspended');

    expect($response->viewData('shops')->total())->toBe(1);
});

it('paginates shop and product matches independently while preserving filters and sorting', function () {
    $buyer = $this->buyer;
    $sellers = User::factory()->count(8)->create(['role' => 'seller']);
    foreach ($sellers as $index => $seller) {
        $seller->sellerProfile()->create(['business_name' => 'Mouse Market '.$index, 'status' => 'approved']);
    }
    $products = Product::factory()->count(25)->sequence(fn (Sequence $sequence): array => ['price' => ($sequence->index + 1) * 10])
        ->create(['seller_id' => $sellers->first()->id, 'status' => Product::STATUS_ACTIVE, 'name' => 'Wireless Mouse', 'category' => 'Electronics']);

    $response = $this->actingAs($buyer)->get(route('buyer.products.index', [
        'search' => 'mouse', 'category' => 'Electronics', 'sort' => 'price_low', 'page' => 2, 'shops_page' => 2,
    ]))->assertOk()->assertSee('Shop search pagination')->assertSee('Product search pagination');

    expect($response->viewData('products')->getCollection()->modelKeys())->toBe([$products->last()->id]);
    expect($response->viewData('shops')->count())->toBe(2);
    expect($response->viewData('products')->url(1))->toContain('search=mouse', 'category=Electronics', 'sort=price_low', 'shops_page=2', '#search-products');
    expect($response->viewData('shops')->url(1))->toContain('page=2', 'shops_page=1', '#search-shops');
});

it('rejects malformed buyer searches', function (array $filters) {
    $buyer = $this->buyer;

    $this->actingAs($buyer)->getJson(route('buyer.products.index', $filters))->assertUnprocessable();
})->with([
    [['search' => ['mouse']]],
    [['search' => str_repeat('a', 101)]],
    [['category' => ['Electronics']]],
    [['shops_page' => -1]],
]);
