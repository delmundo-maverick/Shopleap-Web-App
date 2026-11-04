<?php

use App\Mail\PasswordChanged;
use App\Mail\PasswordResetOtp;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function ($table) {
        $table->string('role')->default('buyer');
    });
    (require database_path('migrations/2026_09_19_165158_create_otp_verifications_table.php'))->up();
    (require database_path('migrations/2026_11_04_185500_add_password_reset_fields_to_otp_verifications_table.php'))->up();
    Schema::create('personal_access_tokens', function ($table) {
        $table->id();
        $table->morphs('tokenable');
        $table->string('name');
        $table->string('token', 64)->unique();
        $table->text('abilities')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });
    Mail::fake();
});

function recoveryUser(string $role = 'buyer'): User
{
    return User::factory()->create(['role' => $role]);
}

function requestRecoveryCode($test, User $user): string
{
    $test->post(route('password.email'), ['email' => $user->email])->assertRedirect(route('password.verify'));

    return Mail::sent(PasswordResetOtp::class)->last()->otp;
}

it('resets each web role and revokes credentials and authorization', function (string $role) {
    $user = recoveryUser($role);
    $user->createToken('test');
    config(['session.driver' => 'database']);
    DB::table('sessions')->insert(['id' => 'old-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    $code = requestRecoveryCode($this, $user);
    expect(Hash::check($code, OtpVerification::first()->otp_hash))->toBeTrue();
    $this->get(route('password.verify'))->assertOk()->assertDontSee($user->email);
    $this->post(route('password.verify.store'), ['otp' => $code])->assertRedirect(route('password.reset'));
    expect(OtpVerification::first()->used_at)->not->toBeNull();
    $this->get(route('password.reset'))->assertOk();
    $this->post(route('password.update'), ['password' => 'new-password', 'password_confirmation' => 'new-password'])->assertRedirect(route('login'))->assertSessionHas('status');
    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
    expect(DB::table('sessions')->where('id', 'old-device')->exists())->toBeFalse();
    expect(OtpVerification::first()->reset_token_hash)->toBeNull();
    Mail::assertSent(PasswordChanged::class);
    $this->post(route('password.update'), ['password' => 'another-password', 'password_confirmation' => 'another-password'])->assertSessionHasErrors('password');
})->with(['buyer', 'seller', 'logistics', 'super_admin']);

it('does not disclose unknown emails and guards the reset URL', function () {
    $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertSessionHas('status', 'If an account exists, an OTP has been sent');
    Mail::assertNothingSent();
    $this->get(route('password.reset'))->assertRedirect(route('password.request'));
    $this->post(route('password.verify.store'), ['otp' => '123456'])->assertSessionHasErrors('otp');
});

it('enforces cooldown, invalidates old codes, and limits requests by email', function () {
    $user = recoveryUser();
    $first = requestRecoveryCode($this, $user);
    $this->post(route('password.resend'))->assertSessionHasErrors('email');
    $this->travel(61)->seconds();
    $second = requestRecoveryCode($this, $user);
    expect(Hash::check($first, OtpVerification::first()->otp_hash))->toBeFalse();
    expect(Hash::check($second, OtpVerification::first()->otp_hash))->toBeTrue();
    for ($i = 0; $i < 3; $i++) {
        $this->travel(61)->seconds();
        requestRecoveryCode($this, $user);
    }
    $this->travel(61)->seconds();
    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');
});

it('invalidates a code after five wrong attempts', function () {
    $user = recoveryUser();
    $code = requestRecoveryCode($this, $user);
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('password.verify.store'), ['otp' => '000000'])->assertSessionHasErrors('otp');
    }
    $this->post(route('password.verify.store'), ['otp' => $code])->assertSessionHasErrors('otp');
    expect(OtpVerification::first()->attempts)->toBe(5);
});

it('expires codes and reset grants and requires matching passwords', function () {
    $user = recoveryUser();
    $code = requestRecoveryCode($this, $user);
    $this->travel(11)->minutes();
    $this->post(route('password.verify.store'), ['otp' => $code])->assertSessionHasErrors('otp');
    $code = requestRecoveryCode($this, $user);
    $this->post(route('password.verify.store'), ['otp' => $code])->assertRedirect(route('password.reset'));
    $this->post(route('password.verify.store'), ['otp' => $code])->assertSessionHasErrors('otp');
    $this->post(route('password.update'), ['password' => 'new-password', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
    $this->travel(16)->minutes();
    $this->get(route('password.reset'))->assertRedirect(route('password.request'));
    $this->post(route('password.update'), ['password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasErrors('password');
});

it('keeps registration purposes separate and makes verification single use', function () {
    Notification::fake();
    $service = app(OtpService::class);
    $service->generateAndSend('test@example.com', 'buyer');
    $registration = OtpVerification::first();
    $registration->update(['otp_hash' => Hash::make('123456')]);
    $service->generateAndSend('test@example.com', 'buyer', 'password_reset');
    expect($service->verify('test@example.com', 'buyer', '123456')[0])->toBeTrue();
    expect($service->verify('test@example.com', 'buyer', '123456')[0])->toBeFalse();
    expect(OtpVerification::count())->toBe(2);
});

it('limits requests per IP even when emails differ', function () {
    for ($i = 0; $i < 20; $i++) {
        $this->post(route('password.email'), ['email' => "unknown{$i}@example.com"])->assertRedirect();
    }
    $this->post(route('password.email'), ['email' => 'last@example.com'])->assertTooManyRequests();
});

it('renders recovery mail with the code, expiry, and correct instructions', function () {
    $mail = new PasswordResetOtp('123456');
    $mail->assertSeeInHtml('123456');
    $mail->assertSeeInHtml('10 minutes');
    $mail->assertSeeInHtml('to reset your Shopleap password');
    $mail->assertSeeInHtml('ignore this email');
    (new PasswordChanged)->assertSeeInHtml('Your password was changed');
});

it('revokes an earlier verified reset grant when another code is requested', function () {
    $user = recoveryUser();
    $code = requestRecoveryCode($this, $user);
    $this->post(route('password.verify.store'), ['otp' => $code])->assertRedirect(route('password.reset'));
    $grant = session('password_reset');
    $this->travel(61)->seconds();
    requestRecoveryCode($this, $user);
    $this->withSession(['password_reset' => $grant])->post(route('password.update'), [
        'password' => 'new-password', 'password_confirmation' => 'new-password',
    ])->assertSessionHasErrors('password');
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
