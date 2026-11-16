<?php

use App\Http\Controllers\Buyer\HomeController;
use App\Models\Product;
use App\Models\User;
use App\Services\PublicCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

it('limits top products and fills discovery pages without skipping products', function () {
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
    Schema::create('cart_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->timestamps();
    });

    $products = Product::factory()->count(49)->create(['seller_id' => 99, 'status' => Product::STATUS_ACTIVE]);
    $buyer = User::factory()->make(['id' => 100, 'role' => 'buyer']);
    $catalog = Mockery::mock(PublicCatalog::class);
    $catalog->shouldReceive('featured')->times(3)->andReturn($products->take(8));
    $catalog->shouldReceive('query')->times(3)->andReturnUsing(fn () => Product::query()->orderBy('id'));

    $discoveredIds = [];

    foreach ([1 => 24, 2 => 24, 3 => 1] as $page => $expectedCount) {
        $request = Request::create(route('buyer.home'), 'GET', ['page' => $page, 'source' => 'home']);
        app()->instance('request', $request);
        $request->setUserResolver(fn () => $buyer);

        $view = app(HomeController::class)->index($request, $catalog);
        $data = $view->getData();
        $recommended = $data['recommended'];

        expect($data['bestSellers']->modelKeys())->toBe($products->take(6)->modelKeys());
        expect($recommended->perPage())->toBe(24)
            ->and($recommended->count())->toBe($expectedCount)
            ->and($recommended->currentPage())->toBe($page)
            ->and($recommended->lastPage())->toBe(3)
            ->and($recommended->url(2))->toContain('source=home', 'page=2', '#daily-discover');

        array_push($discoveredIds, ...$recommended->getCollection()->modelKeys());
    }

    expect($discoveredIds)->toBe($products->modelKeys());
});

it('renders accessible discovery pagination with working page boundaries', function (int $page) {
    $paginator = new LengthAwarePaginator(range(1, 24), 240, 24, $page, ['path' => route('buyer.home')]);
    $html = (string) $paginator->fragment('daily-discover')->onEachSide(1)->links('components.Buyer.discover-pagination');

    expect($html)->toContain('aria-label="Daily Discover pagination"', 'aria-current="page"', 'aria-label="Page '.$page.'"');
    expect(str_contains($html, 'rel="prev"'))->toBe($page > 1);
    expect(str_contains($html, 'rel="next"'))->toBe($page < 10);
    expect($html)->toContain('#daily-discover', 'of <span class="font-semibold text-charcoal">240</span> products');
})->with([1, 5, 10]);

it('hides discovery pagination when all products fit on one page', function () {
    $paginator = new LengthAwarePaginator(range(1, 6), 6, 24);

    expect(trim((string) $paginator->links('components.Buyer.discover-pagination')))->toBe('');
});
