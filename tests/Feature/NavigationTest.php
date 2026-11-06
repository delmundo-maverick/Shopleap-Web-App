<?php

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductDiscount;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('buyer');
    });
    Schema::create('order_returns', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('seller_id')->nullable();
        $table->string('status');
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
});

it('renders every role through one shared shell with unique IDs and working route names', function (string $role, string $component, string $expected) {
    $this->actingAs(User::factory()->create(['role' => $role]));
    $html = Blade::render('<'.$component.' />', ['cartCount' => 0]);
    expect($html)->toContain('navigation-toggle.window', 'nav-shell', $expected, 'method="POST"', 'name="_token"');
    expect($html)->not->toContain('More account options');
    expect($html)->not->toContain('sidebarNav', 'data-sidebar-toggle', '$store.sidebar');
    expect(substr_count($html, 'action="'.route('logout').'"'))->toBe(1);
    expect($html)->toContain('aria-label="Log out"', 'open-logout-modal', 'aria-describedby=', 'logoutSubmitting');
    preg_match_all('/(?<![:\w-])id="([^"\s]+)"/', $html, $ids);
    expect(count($ids[1]))->toBe(count(array_unique($ids[1])));
})->with([
    ['seller', 'x-navbars.seller', 'Prepare &amp; Ship Orders'],
    ['logistics', 'x-navbars.logistics', 'Session History'],
    ['super_admin', 'x-navbars.admin', 'Policies'],
]);

it('keeps buyer location profile cart wishlist and notification controls reachable', function () {
    $this->actingAs(User::factory()->create(['role' => 'buyer']));
    $html = Blade::render('<x-Buyer.header :cart-count="0" :cart-preview-items="collect()" />');
    expect($html)->toContain('buyer-navigation', 'buyerSearchInput', 'buyerCartBadge', 'buyer-mobile-profile', 'buyer-desktop-profile', 'data-buyer-header-location', 'buyer-notification-feed', 'Vouchers', 'Track My Order');
    expect(substr_count($html, 'action="'.route('logout').'"'))->toBe(1);
    expect(substr_count($html, 'aria-label="Log out"'))->toBe(2);
    preg_match_all('/(?<![:\w-])id="([^"\s]+)"/', $html, $ids);
    expect(count($ids[1]))->toBe(count(array_unique($ids[1])));
});

it('renders guest and auth navigation with links to the landing sections', function () {
    $html = Blade::render('<x-nav.guest />');
    expect($html)->toContain('guest-navigation', 'guest-explore', route('home').'#categories', route('login'), route('register'));
    $this->get(route('login'))->assertOk()->assertSee('guest-navigation', false);
});

it('highlights only the matching seller order tab', function () {
    $this->actingAs(User::factory()->create(['role' => 'seller']));
    $request = Request::create('/seller/orders?status=prepare');
    $request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('seller.orders.index'));
    app()->instance('request', $request);
    $html = Blade::render('<x-navbars.seller />');
    expect(substr_count($html, 'aria-current="page"'))->toBe(1);
    expect($html)->toContain('Prepare &amp; Ship Orders');
});

it('keeps a single Alpine bootstrap and removes the disconnected legacy handlers', function () {
    $app = file_get_contents(resource_path('js/app.js'));
    expect(substr_count($app, 'Alpine.start()'))->toBe(1);
    expect($app)->not->toContain("Alpine.store('sidebar'");
    expect(is_file(resource_path('js/shared/navbar.js')))->toBeFalse();
});

it('logs each web role out and refuses the next authenticated page request', function (string $role, string $destination) {
    $this->actingAs(User::factory()->create(['role' => $role]), 'web');
    $this->withSession(['logout_probe' => 'private', '_token' => 'before-logout'])
        ->post(route('logout'))
        ->assertRedirect(route('login'))
        ->assertSessionMissing('logout_probe')
        ->assertSessionHas('_token', fn ($token) => is_string($token) && $token !== 'before-logout');
    $this->assertGuest('web');
    $this->get(route($destination))->assertRedirect(route('login'));
})->with([
    ['buyer', 'buyer.home'],
    ['seller', 'seller.dashboard'],
    ['logistics', 'logistics.dashboard'],
    ['super_admin', 'admin.dashboard'],
]);

it('shows the selected cart variant and its discounted unit price in the header', function (bool $hasVariant) {
    $this->actingAs(User::factory()->create(['role' => 'buyer']));
    $product = new Product(['name' => 'Preview shirt', 'price' => 100]);
    $product->setRelation('images', new Collection);
    $product->setRelation('variants', new Collection);
    $product->setRelation('liveDiscount', new ProductDiscount(['type' => 'percent', 'value' => 10]));
    $variant = $hasVariant ? new ProductVariant(['price' => 250, 'options' => ['Color' => 'Blue', 'Size' => 'Large']]) : null;
    $item = new CartItem(['quantity' => 2, 'selected_options' => $hasVariant ? ['Color' => 'Old selection'] : null]);
    $item->setRelation('product', $product);
    $item->setRelation('variant', $variant);

    $html = Blade::render('<x-Buyer.header :cart-count="2" :cart-preview-items="$items" />', ['items' => collect([$item])]);

    expect($html)->toContain('Preview shirt', '2 &times; &#8369;'.($hasVariant ? '225.00' : '90.00'));
    if ($hasVariant) {
        expect($html)->toContain('Color: Blue, Size: Large')->not->toContain('Old selection');
    }
})->with([true, false]);
