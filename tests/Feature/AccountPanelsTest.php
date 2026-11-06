<?php

use App\Models\BuyerProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('buyer_id');
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('order_returns', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->string('status');
    });
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('status');
    });
    Schema::create('wishlist_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('product_id');
        $table->timestamps();
    });
    Schema::create('reviews', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->unsignedTinyInteger('rating');
    });
    Schema::create('conversations', function (Blueprint $table) {
        $table->id();
        $table->timestamp('last_message_at')->nullable();
    });
    Schema::create('conversation_participants', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('conversation_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamp('hidden_at')->nullable();
        $table->timestamp('last_read_at')->nullable();
        $table->timestamps();
    });
});

it('returns account content without the surrounding page or cart queries', function (string $route, string $panel, string $expected) {
    $buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $buyer->setRelation('buyerProfile', new BuyerProfile(['status' => 'approved']));
    $this->actingAs($buyer)
        ->get(route($route), ['X-Account-Panel' => 'true'])
        ->assertOk()
        ->assertSee('data-account-content="'.$panel.'"', false)
        ->assertSee($expected)
        ->assertDontSee('<!DOCTYPE html>', false)
        ->assertDontSee('buyer-navigation', false);
})->with([
    ['buyer.orders.index', 'purchases', 'Start Shopping'],
    ['buyer.wishlist.index', 'wishlist', 'My Wishlist'],
    ['messages.index', 'messages', 'Chats with sellers'],
]);

it('requires authentication for account panel requests', function (string $route) {
    $this->get(route($route), ['X-Account-Panel' => 'true'])->assertRedirect(route('login'));
})->with(['buyer.orders.index', 'buyer.wishlist.index', 'messages.index']);

function createAccountPageTables(): void
{
    Schema::create('cart_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('product_id');
        $table->unsignedBigInteger('variant_id')->nullable();
        $table->unsignedInteger('quantity');
        $table->timestamps();
    });
    Schema::create('buyer_addresses', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->boolean('is_default')->default(false);
        $table->softDeletes();
    });
    (require database_path('migrations/2026_10_22_161655_create_notifications_table.php'))->up();
}

it('still serves the standalone buyer pages using the same content', function (string $route, string $panel) {
    createAccountPageTables();
    $buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $buyer->setRelation('buyerProfile', new BuyerProfile(['status' => 'approved']));
    $this->actingAs($buyer)->get(route($route))
        ->assertOk()
        ->assertSee('<!DOCTYPE html>', false)
        ->assertSee('buyer-navigation', false)
        ->assertSee('data-account-content="'.$panel.'"', false);
})->with([
    ['buyer.orders.index', 'purchases'],
    ['buyer.wishlist.index', 'wishlist'],
    ['messages.index', 'messages'],
]);

it('renders the three destinations as account buttons with persistent content panels', function () {
    createAccountPageTables();
    $buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    $profile = new BuyerProfile(['status' => 'approved', 'first_name' => 'Account', 'last_name' => 'Buyer']);
    $buyer->setRelation('buyerProfile', $profile);
    $this->actingAs($buyer);
    $view = $this->view('Buyer.account.index', [
        'user' => $buyer,
        'profile' => $profile,
        'addresses' => collect(),
        'following' => new LengthAwarePaginator([], 0, 8),
        'followingIds' => [],
        'followingCounts' => [],
        'errors' => new ViewErrorBag,
    ]);
    foreach (['purchases', 'wishlist', 'messages'] as $name) {
        $view->assertSee('@click="go(\''.$name.'\')"', false)
            ->assertSee('data-account-panel="'.$name.'"', false);
    }
    $view->assertSee('accountTabs(', false)->assertSee('Account navigation');
});
