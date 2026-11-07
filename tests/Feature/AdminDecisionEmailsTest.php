<?php

use App\Models\User;
use App\Notifications\RegistrationDecisionNotification;
use App\Support\Portal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    foreach (['buyer_profiles', 'seller_profiles'] as $tableName) {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('first_name')->default('Test');
            $table->string('last_name')->default('Applicant');
            $table->string('business_name')->nullable();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }
    (require database_path('migrations/2026_10_03_232741_create_activity_logs_table.php'))->up();
    $this->actingAs(User::factory()->create(['role' => 'super_admin']));
    Notification::fake();
});

it('emails buyer and seller approval or rejection decisions from admin', function (string $role, bool $approved) {
    $user = User::factory()->create(['role' => $role]);
    $relationship = $role.'Profile';
    $profile = $user->$relationship()->create(['business_name' => 'Test Shop', 'first_name' => 'Test', 'last_name' => 'Applicant']);
    $this->postJson(route('admin.registrations.'.$role.'s.'.($approved ? 'approve' : 'reject'), $profile), [
        'rejection_reason' => 'Please provide a readable document.',
    ])->assertOk()->assertJsonPath('registration_status', $approved ? 'approved' : 'rejected');
    expect($profile->fresh()->reviewed_at)->not->toBeNull();
    Notification::assertSentTo($user, RegistrationDecisionNotification::class, fn ($notification): bool => $notification->role === $role && $notification->approved === $approved &&
        $notification->reason === ($approved ? null : 'Please provide a readable document.')
    );
})->with([
    ['buyer', true], ['buyer', false], ['seller', true], ['seller', false],
]);

it('requires rejection feedback and prevents conflicting duplicate decisions', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $relationship = $role.'Profile';
    $profile = $user->$relationship()->create(['business_name' => 'Test Shop', 'first_name' => 'Test', 'last_name' => 'Applicant']);
    $this->postJson(route('admin.registrations.'.$role.'s.reject', $profile), [])->assertUnprocessable();
    Notification::assertNothingSent();
    $this->postJson(route('admin.registrations.'.$role.'s.approve', $profile))->assertOk();
    $this->postJson(route('admin.registrations.'.$role.'s.reject', $profile), ['rejection_reason' => 'Stale decision'])->assertUnprocessable();
    Notification::assertSentToTimes($user, RegistrationDecisionNotification::class, 1);
    expect($profile->fresh()->status)->toBe('approved');
})->with(['buyer', 'seller']);

it('renders escaped reasons and uses main portal destinations in decision mail', function () {
    $user = User::factory()->make(['name' => 'Test Applicant']);
    foreach ([true, false] as $approved) {
        $mail = (new RegistrationDecisionNotification('seller', $approved, '<script>unsafe</script>'))->toMail($user);
        $html = view($mail->view, $mail->viewData)->render();
        expect($html)->toContain(Portal::url('main', $approved ? 'login' : ''))
            ->not->toContain('<script>unsafe</script>');
        if (! $approved) {
            expect($html)->toContain('&lt;script&gt;unsafe&lt;/script&gt;');
        }
    }
});
