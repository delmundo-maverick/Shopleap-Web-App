<?php

use App\Models\Product;
use App\Services\PublicCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

it('caches scalar product IDs and returns fresh models with class deserialization disabled', function (string $method, array $arguments, int $limit) {
    $cachedValues = [];
    Cache::shouldReceive('remember')->twice()->andReturnUsing(function (string $key, int $seconds, Closure $callback) use (&$cachedValues, $method): mixed {
        expect($key)->toContain($method === 'discover' ? '.v3' : '.v2');
        expect($seconds)->toBe(60);

        if (! array_key_exists($key, $cachedValues)) {
            $value = $callback();
            expect($value)->toBe([99]);
            $cachedValues[$key] = serialize($value);
        }

        return unserialize($cachedValues[$key], ['allowed_classes' => false]);
    });

    $selection = Mockery::mock(Builder::class);
    $selection->shouldReceive('take')->once()->with($limit)->andReturnSelf();
    $selection->shouldReceive('pluck')->once()->with('id')->andReturn(collect([99]));

    if ($method === 'discover') {
        $selection->shouldReceive('when')->once()->with(true, Mockery::type(Closure::class))
            ->andReturnUsing(function (bool $condition, Closure $callback) use ($selection): Builder {
                return $callback($selection);
            });
        $selection->shouldReceive('where')->once()->with('name', 'like', '%lamp%')->andReturnSelf();
        $selection->shouldReceive('when')->once()->with(true, Mockery::type(Closure::class))
            ->andReturnUsing(function (bool $condition, Closure $callback) use ($selection): Builder {
                return $callback($selection);
            });
        $selection->shouldReceive('where')->once()->with('category', 'Home and Garden')->andReturnSelf();
    }

    $product = new Product(['name' => 'Fresh lamp', 'price' => 350]);
    $product->id = 99;
    $products = new Collection([$product]);
    $hydration = Mockery::mock(Builder::class);
    $hydration->shouldReceive('whereKey')->twice()->with([99])->andReturnSelf();
    $hydration->shouldReceive('get')->twice()->andReturn($products);

    $catalog = Mockery::mock(PublicCatalog::class)->makePartial();
    $catalog->shouldReceive('query')->times(3)->andReturn($selection, $hydration, $hydration);

    expect($catalog->{$method}(...$arguments))->toBe($products);
    expect($catalog->{$method}(...$arguments))->toBe($products);
})->with([
    'featured products' => ['featured', [], 8],
    'filtered discovery' => ['discover', ['lamp', 'Home and Garden'], 40],
]);
