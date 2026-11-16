<?php

use App\Models\CourierProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    Schema::create('logistics_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('business_name');
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('courier_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('logistics_profile_id');
        foreach (['first_name', 'last_name', 'status', 'contact_no', 'province', 'municipality', 'barangay', 'street_address', 'house_number'] as $column) {
            $table->string($column)->nullable();
        }
        $table->boolean('is_active');
        $table->timestamps();
    });
    $hubUser = User::factory()->create(['role' => 'logistics']);
    $hub = $hubUser->logisticsProfile()->create(['business_name' => 'Profile Hub', 'status' => 'approved']);
    $this->courierUser = User::factory()->create(['role' => 'courier', 'account_status' => 'active', 'email' => 'rider@example.com']);
    $this->courier = CourierProfile::create([
        'user_id' => $this->courierUser->id, 'logistics_profile_id' => $hub->id,
        'first_name' => 'Juan', 'last_name' => 'Cruz', 'status' => 'approved', 'is_active' => true,
    ]);
    $this->profileData = [
        'contact_no' => '09123456789', 'province' => 'Cebu', 'municipality' => 'Cebu City',
        'barangay' => 'Lahug', 'street_address' => 'Main Street',
    ];
    Sanctum::actingAs($this->courierUser);
});

it('returns email at the top level and under user while keeping all courier fields', function () {
    $this->getJson('/api/courier/me')->assertSuccessful()
        ->assertJsonPath('email', 'rider@example.com')
        ->assertJsonPath('user.email', 'rider@example.com')
        ->assertJsonPath('courier.email', 'rider@example.com')
        ->assertJsonPath('courier.user.email', 'rider@example.com')
        ->assertJsonStructure(['courier' => [
            'id', 'name', 'status', 'is_active', 'rejection_reason', 'vehicle_type', 'plate_number',
            'contact_no', 'house_number', 'street_address', 'province', 'municipality', 'barangay', 'avatar_url',
            'hub' => ['id', 'name', 'company', 'municipality', 'province'],
        ]]);
});

it('saves normalized email to the user and returns the same profile shape as me', function () {
    $response = $this->postJson('/api/courier/account/profile', $this->profileData + ['email' => '  Updated@Example.com  '])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Profile updated.')
        ->assertJsonPath('email', 'updated@example.com')
        ->assertJsonPath('user.email', 'updated@example.com')
        ->assertJsonPath('courier.email', 'updated@example.com')
        ->assertJsonPath('courier.user.email', 'updated@example.com')
        ->assertJsonPath('courier.contact_no', '09123456789');

    expect($this->courierUser->fresh()->email)->toBe('updated@example.com');
    $me = $this->getJson('/api/courier/me')->assertSuccessful();
    expect($response->json('courier'))->toBe($me->json('courier'))
        ->and($response->json('user'))->toBe($me->json('user'))
        ->and($response->json('email'))->toBe($me->json('email'));
});

it('allows keeping the current email', function () {
    $this->postJson('/api/courier/account/profile', $this->profileData + ['email' => 'rider@example.com'])
        ->assertSuccessful()->assertJsonPath('email', 'rider@example.com');
});

it('keeps email unchanged when omitted by existing clients', function () {
    $this->postJson('/api/courier/account/profile', $this->profileData)
        ->assertSuccessful()->assertJsonPath('email', 'rider@example.com');
    expect($this->courierUser->fresh()->email)->toBe('rider@example.com');
});

it('rejects invalid email without changing user or courier data', function (mixed $email) {
    $this->postJson('/api/courier/account/profile', $this->profileData + ['email' => $email])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
    expect($this->courierUser->fresh()->email)->toBe('rider@example.com')
        ->and($this->courier->fresh()->contact_no)->toBeNull();
})->with(['invalid' => ['not-an-email'], 'blank' => [''], 'null' => [null], 'array' => [['rider@example.com']]]);

it('rejects another users email regardless of role', function (string $role) {
    User::factory()->create(['email' => 'taken@example.com', 'role' => $role]);
    $this->postJson('/api/courier/account/profile', $this->profileData + ['email' => 'TAKEN@example.com'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
    expect($this->courierUser->fresh()->email)->toBe('rider@example.com');
})->with(['courier', 'buyer']);

it('requires courier authentication for profile access', function () {
    $buyer = User::factory()->create(['role' => 'buyer', 'account_status' => 'active']);
    Sanctum::actingAs($buyer);
    $this->getJson('/api/courier/me')->assertForbidden();
    $this->postJson('/api/courier/account/profile', $this->profileData + ['email' => 'updated@example.com'])->assertForbidden();
});
