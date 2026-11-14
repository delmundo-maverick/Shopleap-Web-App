<?php

use App\Support\Portal;

beforeEach(function () {
    $this->withoutVite();
});

it('renders registration details and verification controls for each account type', function (string $role, string $heading) {
    $url = $role === 'logistics'
        ? Portal::url('logistics', 'register')
        : Portal::url('main', 'register/'.$role);

    $response = $this->get($url)->assertOk()
        ->assertSee($heading)
        ->assertSee('registration-workspace', false)
        ->assertSee('registration-intro', false)
        ->assertSee('registration-form', false)
        ->assertSee('action="'.route('register.'.$role).'"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="terms_accepted"', false)
        ->assertSee('showOtpModal', false)
        ->assertSee('verifyOtp()', false);

    expect(substr_count($response->getContent(), '<h1'))->toBe(1);
})->with([
    'buyer' => ['buyer', 'Shop Smart.'],
    'seller' => ['seller', 'Good products.'],
    'logistics' => ['logistics', 'Deliver more.'],
]);

it('redirects the former account selection page to buyer registration', function () {
    $this->get(Portal::url('main', 'register'))->assertRedirect(route('register.buyer'));
});

it('links directly to buyer registration from the login page', function () {
    $this->get(Portal::url('main', 'login'))->assertOk()
        ->assertSee('href="'.route('register.buyer').'"', false)
        ->assertSee('Create an account')
        ->assertDontSee('href="'.route('register').'"', false);
});
