<?php

use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('seller');
        $table->string('account_status')->default('active');
    });
    foreach ([
        '2026_09_05_161506_create_products_table.php',
        '2026_09_19_162034_create_product_options_table.php',
        '2026_09_20_152710_create_product_variants_table.php',
        '2026_10_11_161355_harden_product_variants_and_stock.php',
        '2026_10_14_222218_create_product_discounts_table.php',
        '2026_10_27_002244_add_weight_to_products_and_variants_table.php',
        '2026_10_27_002245_add_image_path_to_product_variants_table.php',
        '2026_11_06_175450_add_inventory_management_fields_to_products_table.php',
        '2026_10_22_161655_create_notifications_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Schema::create('order_returns', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('seller_id');
        $table->string('status');
    });
    Storage::fake('r2_public');
    $this->seller = inventorySeller();
    $this->actingAs($this->seller);
});

function inventorySeller(): User
{
    $seller = User::factory()->create(['role' => 'seller', 'account_status' => 'active']);
    $seller->setRelation('sellerProfile', new SellerProfile(['status' => 'approved', 'line_of_business' => config('shopleap_categories.list')[0]['name']]));

    return $seller;
}

function inventoryPayload(array $attributes = []): array
{
    return array_replace([
        'name' => 'New listing',
        'description' => 'A useful product',
        'category' => config('shopleap_categories.list')[0]['name'],
        'price' => 100,
        'stock_quantity' => 10,
        'low_stock_threshold' => 5,
        'status' => Product::STATUS_DRAFT,
    ], $attributes);
}

function inventoryProduct(User $seller, array $attributes = []): Product
{
    return Product::factory()->create(['seller_id' => $seller->id, ...$attributes]);
}

function inventoryDiscount(Product $product, array $attributes = []): ProductDiscount
{
    return $product->discount()->create([
        'seller_id' => $product->seller_id, 'type' => 'fixed', 'value' => 10,
        'is_active' => true, 'ends_at' => now()->addDays(2), ...$attributes,
    ]);
}

it('renders each page with shared components and no management modal', function () {
    $product = inventoryProduct($this->seller);
    foreach (['seller.products.index', 'seller.products.create', 'seller.products.edit', 'seller.products.discounts.index'] as $route) {
        $response = $this->get(route($route, $route === 'seller.products.edit' ? $product : []));
        $response->assertOk()->assertSee('seller-products-navigation', false)->assertSee('confirmation', false)
            ->assertDontSee('modalOpen', false)->assertDontSee('openManage', false);
    }
});

it('returns only owned products and unfiltered owned summary counts', function () {
    inventoryProduct($this->seller, ['name' => 'Owned active', 'status' => 'active', 'stock_quantity' => 2]);
    inventoryProduct($this->seller, ['name' => 'Owned sold out', 'stock_quantity' => 0]);
    inventoryProduct(inventorySeller(), ['name' => 'Private foreign listing', 'status' => 'active']);
    $this->get(route('seller.products.index', ['status' => 'active']))->assertOk()
        ->assertSee('Owned active')->assertDontSee('Owned sold out')->assertDontSee('Private foreign listing')
        ->assertViewHas('summary', fn (array $summary) => $summary['Total'] === 2 && $summary['Active'] === 1 && $summary['Low stock'] === 1 && $summary['Out of stock'] === 1);
});

it('searches variant SKUs and honours per-product low stock thresholds', function () {
    $product = inventoryProduct($this->seller, ['stock_quantity' => 20, 'low_stock_threshold' => 2]);
    $product->variants()->create(['options' => ['Size' => 'S'], 'price' => 100, 'stock_quantity' => 2, 'sku' => 'MATCH-SKU', 'is_active' => true]);
    inventoryProduct($this->seller, ['name' => 'Healthy stock', 'stock_quantity' => 4, 'low_stock_threshold' => 1]);
    $this->get(route('seller.products.index', ['search' => 'match-sku', 'stock' => 'low']))->assertOk()
        ->assertViewHas('products', fn ($products) => $products->modelKeys() === [$product->id]);
});

it('filters discounts by their live schedule and sorts prices', function () {
    $cheap = inventoryProduct($this->seller, ['price' => 40]);
    $costly = inventoryProduct($this->seller, ['price' => 200]);
    $scheduled = inventoryProduct($this->seller);
    inventoryDiscount($cheap);
    inventoryDiscount($costly);
    inventoryDiscount($scheduled, ['starts_at' => now()->addDay()]);
    $this->get(route('seller.products.index', ['discount' => 'yes', 'sort' => 'price_desc']))->assertOk()
        ->assertViewHas('products', fn ($products) => $products->modelKeys() === [$costly->id, $cheap->id]);
});

it('paginates owned inventory', function () {
    Product::factory()->count(16)->create(['seller_id' => $this->seller->id]);
    $this->get(route('seller.products.index', ['sort' => 'name', 'page' => 2]))->assertOk()
        ->assertViewHas('products', fn ($products) => $products->count() === 1 && $products->total() === 16);
});

it('saves an unfinished draft and requires photos to publish', function () {
    $this->postJson(route('seller.products.store'), inventoryPayload(['price' => null, 'stock_quantity' => null]))
        ->assertOk()->assertJsonStructure(['redirect']);
    $product = Product::sole();
    expect($product->status)->toBe('draft')->and($product->stock_quantity)->toBe(0);
    $this->putJson(route('seller.products.update', $product), inventoryPayload(['status' => 'active']))
        ->assertUnprocessable()->assertJsonValidationErrors('images');
    expect($product->fresh()->status)->toBe('draft');
});

it('publishes and updates validated photos using the R2 public disk', function () {
    $this->post(route('seller.products.store'), inventoryPayload([
        'status' => 'active', 'sku' => 'BASE-001',
        'images' => [UploadedFile::fake()->image('cover.png')],
    ]))->assertRedirect();
    $product = Product::sole();
    $first = $product->images()->sole();
    Storage::disk('r2_public')->assertExists($first->path);
    $this->put(route('seller.products.update', $product), inventoryPayload([
        'status' => 'active', 'remove_images' => [$first->id],
        'images' => [UploadedFile::fake()->image('replacement.webp')],
    ]))->assertRedirect();
    Storage::disk('r2_public')->assertMissing($first->path);
    expect($product->fresh()->status)->toBe('active')->and($product->images()->count())->toBe(1);
});

it('rejects invalid image content and oversized files', function () {
    $this->postJson(route('seller.products.store'), inventoryPayload(['images' => [UploadedFile::fake()->create('fake.jpg', 100, 'text/plain')]]))
        ->assertUnprocessable()->assertJsonValidationErrors('images.0');
    $this->postJson(route('seller.products.store'), inventoryPayload(['images' => [UploadedFile::fake()->image('huge.jpg')->size(10241)]]))
        ->assertUnprocessable()->assertJsonValidationErrors('images.0');
    expect(Product::count())->toBe(0);
});

it('protects other sellers products for every row action', function (string $method, string $route, array $data) {
    $foreign = inventoryProduct(inventorySeller());
    $this->{$method}(route($route, $foreign), $data)->assertForbidden();
    expect($foreign->fresh()->name)->toBe($foreign->name);
})->with([
    ['getJson', 'seller.products.edit', []],
    ['putJson', 'seller.products.update', []],
    ['patchJson', 'seller.products.quick', []],
    ['patchJson', 'seller.products.status', []],
    ['postJson', 'seller.products.duplicate', []],
    ['deleteJson', 'seller.products.destroy', []],
]);

it('rejects cross-product image removal and foreign variant IDs', function () {
    $owned = inventoryProduct($this->seller);
    $foreign = inventoryProduct(inventorySeller());
    $image = $foreign->images()->create(['path' => 'foreign.jpg']);
    $this->putJson(route('seller.products.update', $owned), inventoryPayload(['remove_images' => [$image->id]]))
        ->assertUnprocessable()->assertJsonValidationErrors('images');
    $variant = $owned->variants()->create(['options' => ['Size' => 'S'], 'price' => 50, 'stock_quantity' => 5, 'is_active' => true]);
    $foreignVariant = $foreign->variants()->create(['options' => ['Size' => 'L'], 'price' => 50, 'stock_quantity' => 5, 'is_active' => true]);
    $this->putJson(route('seller.products.update', $owned), inventoryPayload(['variants' => [
        ['id' => $foreignVariant->id, 'price' => 5, 'stock_quantity' => 1, 'is_active' => 1],
    ]]))->assertUnprocessable()->assertJsonValidationErrors('variants');
    expect($variant->fresh()->stock_quantity)->toBe(5);
});

it('creates variants and edits them without replacing IDs or losing aggregate stock', function () {
    $this->postJson(route('seller.products.store'), inventoryPayload([
        'options' => [['name' => 'Size', 'values' => 'S, L']],
        'variants' => [
            ['options' => ['Size' => 'S'], 'price' => 70, 'stock_quantity' => 3, 'is_active' => 1],
            ['options' => ['Size' => 'L'], 'price' => 90, 'stock_quantity' => 4, 'is_active' => 1],
        ],
    ]))->assertOk();
    $product = Product::sole();
    $variants = $product->variants()->orderBy('id')->get();
    expect($product->stock_quantity)->toBe(7)->and((float) $product->price)->toBe(70.0);
    $rows = $variants->map(fn ($variant, $index) => ['id' => $variant->id, 'price' => 60 + $index, 'stock_quantity' => 8, 'is_active' => $index === 0 ? 1 : 0])->all();
    $this->putJson(route('seller.products.update', $product), inventoryPayload(['variants' => $rows]))->assertOk();
    expect($product->variants()->orderBy('id')->pluck('id')->all())->toBe($variants->modelKeys());
    expect($product->fresh()->stock_quantity)->toBe(8)->and((float) $product->fresh()->price)->toBe(60.0);
});

it('rejects supplied variant IDs when creating a product', function () {
    $this->postJson(route('seller.products.store'), inventoryPayload([
        'options' => [['name' => 'Size', 'values' => 'S']],
        'variants' => [['id' => 99, 'options' => ['Size' => 'S'], 'price' => 70, 'stock_quantity' => 3, 'is_active' => 1]],
    ]))->assertUnprocessable()->assertJsonValidationErrors('variants.0.id');
    expect(Product::count())->toBe(0);
});

it('resubmits rejected products and preserves review requirements through draft saves and duplication', function () {
    $product = inventoryProduct($this->seller, ['status' => 'rejected']);
    $product->forceFill(['rejection_reason' => 'Replace the cover'])->save();
    $product->images()->create(['path' => 'cover.jpg']);
    $this->putJson(route('seller.products.update', $product), inventoryPayload(['status' => 'draft']))->assertOk();
    expect($product->fresh()->requires_review)->toBeTrue();
    $this->patchJson(route('seller.products.status', $product), ['status' => 'active'])->assertUnprocessable();
    $this->putJson(route('seller.products.update', $product), inventoryPayload(['status' => 'active']))->assertOk();
    expect($product->fresh()->status)->toBe('pending_review')->and($product->fresh()->rejection_reason)->toBeNull();
    $this->postJson(route('seller.products.duplicate', $product))->assertOk();
    $copy = Product::latest('id')->first();
    expect($copy->status)->toBe('draft')->and($copy->requires_review)->toBeTrue();
});

it('quick edits simple inventory and prevents a price change from invalidating a discount', function () {
    $product = inventoryProduct($this->seller);
    inventoryDiscount($product, ['value' => 50]);
    $this->patchJson(route('seller.products.quick', $product), ['price' => 120, 'stock_quantity' => 3])->assertOk();
    expect((float) $product->fresh()->price)->toBe(120.0)->and($product->fresh()->stock_quantity)->toBe(3);
    $this->patchJson(route('seller.products.quick', $product), ['price' => 50, 'stock_quantity' => 3])->assertUnprocessable();
    expect((float) $product->fresh()->price)->toBe(120.0);
});

it('duplicates as a draft without copying discounts and safely retains shared images during deletion', function () {
    $product = inventoryProduct($this->seller);
    Storage::disk('r2_public')->put('shared.jpg', 'image');
    $product->images()->create(['path' => 'shared.jpg']);
    inventoryDiscount($product);
    $this->postJson(route('seller.products.duplicate', $product))->assertOk();
    $copy = Product::latest('id')->first();
    expect($copy->status)->toBe('draft')->and($copy->discount)->toBeNull()->and($copy->images)->toHaveCount(1);
    $this->deleteJson(route('seller.products.destroy', $product))->assertOk();
    Storage::disk('r2_public')->assertExists('shared.jpg');
    $this->deleteJson(route('seller.products.destroy', $copy))->assertOk();
    Storage::disk('r2_public')->assertMissing('shared.jpg');
});

it('rejects mixed-owner bulk requests atomically', function () {
    $owned = inventoryProduct($this->seller, ['status' => 'active']);
    $foreign = inventoryProduct(inventorySeller(), ['status' => 'active']);
    $this->postJson(route('seller.products.bulk'), ['action' => 'delete', 'product_ids' => [$owned->id, $foreign->id]])->assertForbidden();
    $this->assertModelExists($owned);
    $this->assertModelExists($foreign);
    $this->postJson(route('seller.products.bulk'), ['action' => 'deactivate', 'product_ids' => [$owned->id]])->assertOk();
    expect($owned->fresh()->status)->toBe('archived');
});

it('applies discounts to multiple products and updates buyer-facing price accessors', function () {
    $one = inventoryProduct($this->seller);
    $two = inventoryProduct($this->seller, ['price' => 200]);
    $this->postJson(route('seller.products.discounts.store'), [
        'product_ids' => [$one->id, $two->id], 'type' => 'percent', 'value' => 20, 'ends_at' => now()->addDay()->toDateTimeString(),
    ])->assertOk();
    expect($one->fresh()->display_price)->toBe(80.0)->and($one->fresh()->was_price)->toBe(100.0)->and($two->fresh()->display_price)->toBe(160.0);
});

it('blocks overlap, invalid final prices, excessive percentages and invalid schedules', function (array $changes, string $field) {
    $product = inventoryProduct($this->seller);
    $this->postJson(route('seller.products.discounts.store'), array_replace([
        'product_ids' => [$product->id], 'type' => 'fixed', 'value' => 10, 'ends_at' => now()->addDay()->toDateTimeString(),
    ], $changes))->assertUnprocessable()->assertJsonValidationErrors($field);
    expect(ProductDiscount::count())->toBe(0);
})->with([
    [['type' => 'percent', 'value' => 91], 'value'],
    [['value' => 100], 'value'],
    [['value' => -10], 'value'],
    [['starts_at' => '2099-01-02', 'ends_at' => '2099-01-01'], 'ends_at'],
    [['ends_at' => '2000-01-01'], 'ends_at'],
]);

it('requires explicit editing of an existing discount and can end it without losing the record', function () {
    $product = inventoryProduct($this->seller);
    $discount = inventoryDiscount($product, ['starts_at' => now()->addHour()]);
    $payload = ['product_ids' => [$product->id], 'type' => 'fixed', 'value' => 20, 'ends_at' => now()->addDay()->toDateTimeString()];
    $this->postJson(route('seller.products.discounts.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('product_ids');
    $this->putJson(route('seller.products.discounts.update', $discount), $payload)->assertOk();
    expect((float) $discount->fresh()->value)->toBe(20.0);
    $this->deleteJson(route('seller.products.discounts.destroy', $discount))->assertOk();
    expect($product->fresh()->display_price)->toBe(100.0)->and($discount->fresh()->is_active)->toBeFalse();
    $this->assertModelExists($discount);
});

it('protects discount selection, editing, updates and ending from other sellers', function () {
    $foreign = inventoryProduct(inventorySeller());
    $discount = inventoryDiscount($foreign);
    $payload = ['product_ids' => [$foreign->id], 'type' => 'percent', 'value' => 10, 'ends_at' => now()->addDay()->toDateTimeString()];
    $this->getJson(route('seller.products.discounts.index', ['product_ids' => [$foreign->id]]))->assertForbidden();
    $this->getJson(route('seller.products.discounts.index', ['edit' => $discount->id]))->assertNotFound();
    $this->postJson(route('seller.products.discounts.store'), $payload)->assertForbidden();
    $this->putJson(route('seller.products.discounts.update', $discount), $payload)->assertForbidden();
    $this->deleteJson(route('seller.products.discounts.destroy', $discount))->assertForbidden();
});

it('renders discount states and removes the obsolete modal routes', function () {
    $product = inventoryProduct($this->seller);
    inventoryDiscount($product, ['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
    $this->get(route('seller.products.discounts.index', ['state' => 'scheduled']))->assertOk()->assertViewHas('discounts', fn ($items) => $items->total() === 1);
    $this->get(route('seller.products.discounts.index', ['state' => 'live']))->assertOk()->assertViewHas('discounts', fn ($items) => $items->total() === 0);
    $this->get('/seller/products/'.$product->id.'/details')->assertNotFound();
    $this->get('/seller/products/'.$product->id.'/discount')->assertNotFound();
    $this->post('/seller/products/'.$product->id.'/images')->assertNotFound();
});
