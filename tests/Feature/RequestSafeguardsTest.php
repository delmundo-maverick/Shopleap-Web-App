<?php

use App\Http\Controllers\MessagingController;
use App\Models\OtpVerification;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Notifications\OtpNotification;
use App\Services\OtpService;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Cache::flush();
    Notification::fake();
});

it('throttles registration and verification before repeated controller work', function (string $path, int $initialStatus) {
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->postJson($path)->assertStatus($initialStatus);
    }

    $this->postJson($path)->assertTooManyRequests()->assertHeader('Retry-After');
})->with([
    ['register/buyer', 422],
    ['register/seller', 422],
    ['register/buyer/verify', 422],
]);

it('limits aggregate writes while leaving reads available and isolating users', function () {
    Route::match(['GET', 'POST'], '/request-safeguards', fn () => response()->json(['ok' => true]))
        ->middleware('throttle:mutations');
    $this->actingAs(User::factory()->make(['id' => 101]));
    for ($attempt = 0; $attempt < 120; $attempt++) {
        $this->postJson('/request-safeguards')->assertOk();
    }
    $this->postJson('/request-safeguards')->assertTooManyRequests();
    $this->getJson('/request-safeguards')->assertOk();
    $this->actingAs(User::factory()->make(['id' => 102]));
    $this->postJson('/request-safeguards')->assertOk();
});

it('enforces recipient cooldowns across initial and repeat OTP sends without another query', function () {
    foreach ([
        '2026_09_19_165158_create_otp_verifications_table.php',
        '2026_11_04_185500_add_password_reset_fields_to_otp_verifications_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }

    $otp = app(OtpService::class);
    $otp->generateAndSend('Person@example.com', 'buyer');
    $originalHash = OtpVerification::first()->otp_hash;
    DB::enableQueryLog();
    DB::flushQueryLog();
    expect(fn () => $otp->generateAndSend(' person@example.com ', 'buyer'))
        ->toThrow(ValidationException::class);
    expect(DB::getQueryLog())->toBeEmpty();
    DB::disableQueryLog();
    expect(OtpVerification::first()->otp_hash)->toBe($originalHash);
    Notification::assertSentOnDemandTimes(OtpNotification::class, 1);

    $otp->generateAndSend('person@example.com', 'seller');
    $this->travel(61)->seconds();
    $otp->generateAndSend('person@example.com', 'buyer');
    Notification::assertSentOnDemandTimes(OtpNotification::class, 3);
});

it('caps OTP emails per recipient even when each resend waits for its cooldown', function () {
    foreach ([
        '2026_09_19_165158_create_otp_verifications_table.php',
        '2026_11_04_185500_add_password_reset_fields_to_otp_verifications_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $otp = app(OtpService::class);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $otp->generateAndSend('person@example.com', 'buyer');
        $this->travel(61)->seconds();
    }
    expect(fn () => $otp->generateAndSend('person@example.com', 'buyer'))
        ->toThrow(ValidationException::class);
    Notification::assertSentOnDemandTimes(OtpNotification::class, 5);
});

it('counts unread messages in one query without loading conversation histories', function () {
    Schema::create('conversation_participants', function (Blueprint $table): void {
        $table->unsignedBigInteger('conversation_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamp('last_read_at')->nullable();
        $table->timestamp('hidden_at')->nullable();
    });
    Schema::create('messages', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('conversation_id');
        $table->unsignedBigInteger('sender_id');
        $table->timestamp('created_at');
    });
    DB::table('conversation_participants')->insert([
        ['conversation_id' => 1, 'user_id' => 101, 'last_read_at' => '2026-01-01 12:00:00', 'hidden_at' => null],
        ['conversation_id' => 2, 'user_id' => 101, 'last_read_at' => null, 'hidden_at' => now()],
        ['conversation_id' => 3, 'user_id' => 102, 'last_read_at' => null, 'hidden_at' => null],
        ['conversation_id' => 4, 'user_id' => 101, 'last_read_at' => null, 'hidden_at' => null],
    ]);
    DB::table('messages')->insert([
        ['conversation_id' => 1, 'sender_id' => 102, 'created_at' => '2026-01-01 13:00:00'],
        ['conversation_id' => 1, 'sender_id' => 101, 'created_at' => '2026-01-01 13:00:00'],
        ['conversation_id' => 1, 'sender_id' => 102, 'created_at' => '2026-01-01 12:00:00'],
        ['conversation_id' => 2, 'sender_id' => 102, 'created_at' => '2026-01-01 13:00:00'],
        ['conversation_id' => 3, 'sender_id' => 102, 'created_at' => '2026-01-01 13:00:00'],
        ['conversation_id' => 4, 'sender_id' => 102, 'created_at' => '2026-01-01 13:00:00'],
    ]);
    $request = Request::create('/messages/unread-count');
    $request->setUserResolver(fn () => User::factory()->make(['id' => 101]));
    $user = $request->user();
    $request->setUserResolver(fn () => $user);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $response = app(MessagingController::class)->unreadCount($request);
    expect($response->getData(true))->toBe(['count' => 2]);
    expect(DB::getQueryLog())->toHaveCount(1);
    DB::disableQueryLog();
});

it('memoizes platform settings and invalidates the memo when updated', function () {
    (require database_path('migrations/2026_10_04_180040_create_platform_settings_table.php'))->up();
    PlatformSetting::set('commission_rate', 10);
    PlatformSetting::get('commission_rate');
    $hits = 0;
    Event::listen(CacheHit::class, function () use (&$hits): void {
        $hits++;
    });
    PlatformSetting::get('commission_rate');
    PlatformSetting::get('commission_rate');
    expect($hits)->toBe(1);
    PlatformSetting::set('commission_rate', 15);
    expect((int) PlatformSetting::get('commission_rate'))->toBe(15);
});

it('reuses the public hub listing until its short cache expires', function () {
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_09_18_103005_create_logistics_profiles_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Schema::table('users', function (Blueprint $table): void {
        $table->string('account_status')->default('active');
    });
    $this->getJson('/api/hubs')->assertOk()->assertExactJson(['data' => []]);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $this->getJson('/api/hubs')->assertOk()->assertExactJson(['data' => []]);
    expect(DB::getQueryLog())->toBeEmpty();
    $this->travel(61)->seconds();
    $this->getJson('/api/hubs')->assertOk();
    expect(DB::getQueryLog())->toHaveCount(1);
    DB::disableQueryLog();
});
