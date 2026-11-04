<?php

use App\Models\PlatformPolicy;
use App\Models\User;
use App\Support\PolicyAcceptance;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The existing migration set contains PostgreSQL-only statements, so the
 * SQLite test connection cannot migrate from scratch. These tests only need
 * the two tables they touch.
 */
beforeEach(function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
        $table->timestamp('email_verified_at')->nullable();
        $table->string('avatar_path')->nullable();
        $table->unsignedInteger('terms_version')->nullable();
        $table->timestamp('terms_accepted_at')->nullable();
        $table->unsignedInteger('privacy_version')->nullable();
        $table->timestamp('privacy_accepted_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('platform_policies', function (Blueprint $table) {
        $table->id();
        $table->string('slug')->unique();
        $table->string('title');
        $table->longText('body');
        $table->unsignedInteger('version')->default(1);
        $table->foreignId('updated_by')->nullable();
        $table->timestamps();
    });

    PlatformPolicy::create([
        'slug' => PlatformPolicy::TERMS_SLUG,
        'title' => 'Terms of Service',
        'body' => "First line of the terms.\nSecond line of the terms.",
        'version' => 3,
    ]);

    PlatformPolicy::create([
        'slug' => PlatformPolicy::PRIVACY_SLUG,
        'title' => 'Privacy Policy',
        'body' => 'We respect your privacy.',
        'version' => 2,
    ]);
});

it('shows policy pages without authentication', function (string $routeName, string $title) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee($title)
        ->assertSee('Last updated');
})->with([
    ['terms', 'Terms of Service'],
    ['privacy', 'Privacy Policy'],
]);

it('returns sanitized html and version metadata from the content endpoints', function () {
    PlatformPolicy::where('slug', PlatformPolicy::TERMS_SLUG)->update([
        'body' => "Hello <script>alert('xss')</script>\nNext line",
    ]);

    $response = $this->getJson(route('terms.content'));

    $response->assertOk()
        ->assertJson([
            'title' => 'Terms of Service',
            'version' => 3,
        ]);

    expect($response->json('content'))
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>')
        ->toContain('<br>');

    $this->getJson(route('privacy.content'))
        ->assertOk()
        ->assertJson([
            'title' => 'Privacy Policy',
            'version' => 2,
        ]);
});

it('returns a friendly message when a policy has not been published', function () {
    PlatformPolicy::where('slug', PlatformPolicy::PRIVACY_SLUG)->delete();

    $this->getJson(route('privacy.content'))
        ->assertOk()
        ->assertJson([
            'content' => null,
            'message' => 'This policy has not been published yet. Please check back later.',
        ]);

    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('This policy has not been published yet.');
});

it('stores the accepted policy versions and timestamps on the new user', function () {
    $acceptance = PolicyAcceptance::attributes();

    $user = User::create([
        'name' => 'New Buyer',
        'email' => 'new-buyer@example.com',
        'password' => bcrypt('password'),
        'role' => 'buyer',
        ...$acceptance,
    ])->refresh();

    expect($user->terms_version)->toBe(3)
        ->and($user->privacy_version)->toBe(2)
        ->and($user->terms_accepted_at)->not->toBeNull()
        ->and($user->privacy_accepted_at)->not->toBeNull();

    $this->assertDatabaseHas('users', [
        'email' => 'new-buyer@example.com',
        'terms_version' => 3,
        'privacy_version' => 2,
    ]);
});

it('requires terms acceptance on every web registration request', function (string $routeName) {
    $this->post(route($routeName), [])
        ->assertSessionHasErrors([
            'terms_accepted' => 'Please read and agree to the Terms and Conditions and Privacy Policy.',
        ]);
})->with([
    'buyer' => 'register.buyer',
    'seller' => 'register.seller',
    'logistics' => 'register.logistics',
]);
