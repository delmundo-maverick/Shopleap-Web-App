<?php

use App\Models\Product;
use App\Services\PublicCatalog;
use App\Support\Portal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutVite();
});

it('renders the public landing page with empty states and real guest destinations', function () {
    $this->mock(PublicCatalog::class, function ($mock) {
        $mock->shouldReceive('featured')->once()->andReturn(new Collection);
        $mock->shouldReceive('discover')->with('', '')->once()->andReturn(new Collection);
    });
    config(['shopleap_categories.list' => []]);

    $response = $this->get(route('home'))->assertOk()
        ->assertSee('Categories will appear here soon.')
        ->assertSee('No products found.')->assertSee('Featured products')
        ->assertSee(asset('images/Shopleap-Logo-White.png'), false)
        ->assertSee(route('register.buyer'), false)
        ->assertSee(route('register.seller'), false)
        ->assertSee(Portal::url('logistics'), false)
        ->assertSee(route('terms'), false)
        ->assertSee(route('privacy'), false)
        ->assertDontSee('Rider')->assertDontSee('rider')
        ->assertDontSee('href="#"', false);

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
});

it('allows guests to search and filter the public catalog', function () {
    $this->mock(PublicCatalog::class, function ($mock) {
        $mock->shouldReceive('featured')->once()->andReturn(new Collection);
        $mock->shouldReceive('discover')->with('lamp', 'Home and Garden')->once()->andReturn(new Collection);
    });

    $this->get(route('home', ['search' => 'lamp', 'category' => 'Home and Garden']))
        ->assertOk()->assertSee('Results for lamp');
});

it('renders public product cards without querying private buyer data', function () {
    $product = new Product(['name' => 'Public lamp', 'price' => 350, 'status' => 'active']);
    $product->id = 99;
    $product->setRelation('images', new Collection);
    $product->setRelation('variants', new Collection);
    $product->setRelation('seller', null);
    $product->setRelation('liveDiscount', null);
    $this->mock(PublicCatalog::class, function ($mock) use ($product) {
        $mock->shouldReceive('featured')->once()->andReturn(new Collection);
        $mock->shouldReceive('discover')->andReturn(new Collection([$product]));
    });

    $this->get(route('home'))->assertOk()->assertSee('Public lamp')
        ->assertSee(route('buyer.products.show', 99), false)
        ->assertSee(route('login', ['continue' => 'landing']), false);
});

it('stores a safe return destination and a login message for guest save actions', function () {
    $this->get(route('login', ['continue' => 'landing']))->assertOk()
        ->assertSessionHas('url.intended', route('home'))
        ->assertSee('Please log in to continue');
});

it('keeps existing buyer product and shop detail routes protected', function () {
    $this->get(route('buyer.products.show', 99))->assertRedirect(route('login'));
    $this->get(route('buyer.shop.show', 99))->assertRedirect(route('login'));
});

it('rejects malformed public filters before querying the catalog', function () {
    $this->getJson(route('home', ['search' => ['bad']]))->assertUnprocessable();
});

it('preserves landing search filters in the safe intended URL', function () {
    $filters = ['search' => 'lamp', 'category' => 'Home and Garden'];
    $this->get(route('login', array_merge(['continue' => 'landing'], $filters)))
        ->assertOk()->assertSessionHas('url.intended', route('home', $filters));
});

it('starts with image categories and removes the former hero', function () {
    $this->mock(PublicCatalog::class, function ($mock) {
        $mock->shouldReceive('featured')->once()->andReturn(new Collection);
        $mock->shouldReceive('discover')->andReturn(new Collection);
    });

    $response = $this->get(route('home'))->assertOk()
        ->assertDontSee('Start shopping')->assertDontSee('landing-shopping.webp')
        ->assertSee('class="site-container', false)->assertSee('x-reveal', false);
    $html = $response->getContent();
    foreach (config('shopleap_categories.list') as $category) {
        $slug = Str::slug(str_replace(['&', "'"], ['and', ''], $category['name']));
        $response->assertSee(asset('images/categories/'.$slug.'.png'), false);
        expect(file_exists(public_path('images/categories/'.$slug.'.png')))->toBeTrue();
    }
    expect(substr_count($html, 'width="96" height="96"'))->toBe(14);
    expect(substr_count($html, 'loading="eager"'))->toBe(6);
    expect(substr_count($html, 'loading="lazy"'))->toBe(8);
    expect(strpos($html, 'id="categories"'))->toBeLessThan(strpos($html, 'id="discover"'));
});
