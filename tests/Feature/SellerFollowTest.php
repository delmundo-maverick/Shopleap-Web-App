<?php

use App\Models\Product;
use App\Models\SellerFollow;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function makeBuyer(): User
{
    $buyer = User::factory()->create([
        'role' => 'buyer',
        'account_status' => 'active',
    ]);

    $buyer->buyerProfile()->create([
        'first_name' => 'Test',
        'last_name' => 'Buyer',
        'sex' => 'female',
        'contact_no' => '09171234567',
        'birthday' => '1995-01-01',
        'age' => 31,
        'province' => 'Metro Manila',
        'municipality' => 'Quezon City',
        'barangay' => 'Barangay 1',
        'house_number' => '1',
        'street' => 'Test Street',
        'id_upload_path' => 'buyers/test-id.jpg',
        'status' => 'approved',
        'reviewed_at' => now(),
    ]);

    return $buyer;
}

function makeSeller(string $profileStatus = 'approved', string $accountStatus = 'active'): User
{
    $seller = User::factory()->create([
        'role' => 'seller',
        'account_status' => $accountStatus,
    ]);

    $seller->sellerProfile()->create([
        'first_name' => 'Test',
        'last_name' => 'Seller',
        'sex' => 'male',
        'contact_no' => '09179876543',
        'birthday' => '1990-01-01',
        'age' => 36,
        'province' => 'Metro Manila',
        'municipality' => 'Makati City',
        'barangay' => 'Barangay 2',
        'street_address' => '2 Test Street',
        'business_name' => 'Shop '.fake()->unique()->numerify('####'),
        'line_of_business' => 'General merchandise',
        'id_upload_path' => 'sellers/test-id.jpg',
        'business_permit_path' => 'sellers/test-permit.jpg',
        'status' => $profileStatus,
        'reviewed_at' => now(),
    ]);

    return $seller;
}

function makeProduct(User $seller, string $status = 'active'): Product
{
    return Product::create([
        'seller_id' => $seller->id,
        'name' => 'Test Product '.fake()->unique()->numerify('####'),
        'description' => 'A test product.',
        'category' => 'Home & Living',
        'price' => 100,
        'stock_quantity' => 10,
        'status' => $status,
    ]);
}

it('lets a buyer follow and unfollow a seller', function () {
    $buyer = makeBuyer();
    $seller = makeSeller();

    $this->actingAs($buyer)
        ->postJson(route('buyer.sellers.follow', $seller))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'following' => true,
            'followers_count' => 1,
            'following_count' => 1,
        ]);

    $this->assertDatabaseHas('seller_followers', [
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
    ]);

    $this->actingAs($buyer)
        ->deleteJson(route('buyer.sellers.unfollow', $seller))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'following' => false,
            'followers_count' => 0,
            'following_count' => 0,
        ]);

    $this->assertDatabaseMissing('seller_followers', [
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
    ]);
});

it('does not create duplicate follow records', function () {
    $buyer = makeBuyer();
    $seller = makeSeller();

    $this->actingAs($buyer)
        ->postJson(route('buyer.sellers.follow', $seller))
        ->assertOk();

    $this->actingAs($buyer)
        ->postJson(route('buyer.sellers.follow', $seller))
        ->assertOk()
        ->assertJson([
            'following' => true,
            'followers_count' => 1,
            'following_count' => 1,
        ]);

    $this->assertDatabaseCount('seller_followers', 1);
});

it('blocks guests from following sellers', function () {
    $seller = makeSeller();

    $this->postJson(route('buyer.sellers.follow', $seller))
        ->assertUnauthorized();

    $this->assertDatabaseCount('seller_followers', 0);
});

it('prevents a buyer from following themselves', function () {
    $buyer = makeBuyer();

    $this->actingAs($buyer)
        ->postJson(route('buyer.sellers.follow', $buyer))
        ->assertStatus(422);

    $this->assertDatabaseCount('seller_followers', 0);
});

it('refuses to follow ineligible sellers', function () {
    $buyer = makeBuyer();

    $pending = makeSeller('pending');
    $suspended = makeSeller('approved', 'suspended');
    $nonSeller = User::factory()->create([
        'role' => 'logistics',
        'account_status' => 'active',
    ]);

    $this->actingAs($buyer)
        ->postJson(route('buyer.sellers.follow', $pending))
        ->assertNotFound();

    $this->actingAs($buyer)
        ->postJson(route('buyer.sellers.follow', $suspended))
        ->assertNotFound();

    $this->actingAs($buyer)
        ->postJson(route('buyer.sellers.follow', $nonSeller))
        ->assertNotFound();

    $this->assertDatabaseCount('seller_followers', 0);
});

it('shows the following tab with eligible followed shops and product counts', function () {
    $buyer = makeBuyer();
    $followed = makeSeller();
    $pending = makeSeller('pending');

    SellerFollow::create([
        'buyer_id' => $buyer->id,
        'seller_id' => $followed->id,
    ]);

    SellerFollow::create([
        'buyer_id' => $buyer->id,
        'seller_id' => $pending->id,
    ]);

    makeProduct($followed);
    makeProduct($followed);
    makeProduct($followed, 'draft');
    makeProduct($pending);

    $response = $this->actingAs($buyer)
        ->get(route('buyer.account.index'))
        ->assertOk()
        ->assertSee('Following')
        ->assertSee($followed->sellerProfile->shop_name)
        ->assertDontSee($pending->sellerProfile->shop_name)
        ->assertSee(route('buyer.shop.show', $followed), false);

    $response->assertViewHas('following', function ($following) use ($followed) {
        return $following->total() === 1
            && $following->first()->is($followed)
            && (int) $following->first()->active_products_count === 2;
    });
});

it('shows the real follower count and current follow state on a shop page', function () {
    $buyer = makeBuyer();
    $seller = makeSeller();

    SellerFollow::create([
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
    ]);

    $this->actingAs($buyer)
        ->get(route('buyer.shop.show', $seller))
        ->assertOk()
        ->assertViewHas('followersCount', 1)
        ->assertViewHas('isFollowing', true);
});

it('hides shops that are not eligible for buyers', function () {
    $buyer = makeBuyer();
    $pending = makeSeller('pending');

    $this->actingAs($buyer)
        ->get(route('buyer.shop.show', $pending))
        ->assertNotFound();
});
