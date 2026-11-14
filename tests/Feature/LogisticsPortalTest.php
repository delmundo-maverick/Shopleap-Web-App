<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Models\LogisticsProfile;
use App\Models\User;
use App\Notifications\LogisticsDecisionNotification;
use App\Notifications\OtpNotification;
use App\Services\OtpService;
use App\Support\Portal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
        $table->unsignedInteger('terms_version')->nullable();
        $table->timestamp('terms_accepted_at')->nullable();
        $table->unsignedInteger('privacy_version')->nullable();
        $table->timestamp('privacy_accepted_at')->nullable();
    });
    foreach ([
        '2026_09_18_103005_create_logistics_profiles_table.php',
        '2026_10_18_115326_add_company_name_to_logistics_profiles_table.php',
        '2026_11_01_160608_add_joining_existing_company_to_logistics_profiles_table.php',
        '2026_11_07_184554_add_application_documents_to_logistics_profiles_table.php',
        '2026_09_19_165158_create_otp_verifications_table.php',
        '2026_11_04_185500_add_password_reset_fields_to_otp_verifications_table.php',
        '2026_10_04_180050_create_platform_policies_table.php',
        '2026_10_03_232741_create_activity_logs_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Storage::fake('r2');
    Notification::fake();
});

function portalPartner(string $status = 'approved'): User
{
    $user = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $user->logisticsProfile()->create([
        'last_name' => 'Partner', 'first_name' => 'Delivery', 'sex' => 'female',
        'contact_no' => '09123456789', 'birthday' => '1990-01-01', 'age' => 36,
        'province' => 'Cebu', 'municipality' => 'Cebu City', 'barangay' => 'Lahug',
        'street_address' => '1 Main Street', 'business_name' => 'Test Delivery Hub',
        'company_name' => 'Test Delivery', 'id_upload_path' => 'id.pdf',
        'business_permit_path' => 'permit.pdf', 'status' => $status,
    ]);

    return $user;
}

it('serves the lightweight logistics landing and separate login and registration', function () {
    $this->get(Portal::url('logistics'))->assertOk()->assertSee('Keep the Philippines moving.')
        ->assertSee('SEC Certificate')->assertSee('BIR Form 2303')->assertDontSee('chart.js');
    $this->get(Portal::url('logistics', 'login'))->assertOk()->assertSee('Log in to Logistics')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get(Portal::url('logistics', 'register'))->assertOk()->assertSee('sec_certificate')
        ->assertSee('service_regions[]', false)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('redirects legacy logistics paths before authentication', function (string $path, string $destination) {
    $this->get(Portal::url('main', $path))->assertRedirect(Portal::url('logistics', $destination));
})->with([
    ['logistics/dashboard', 'logistics/dashboard'],
    ['logistics/riders', 'logistics/riders'],
    ['logistics/deliveries?status=pending', 'logistics/deliveries?status=pending'],
    ['register/logistics', 'register'],
    ['register/logistics/verify', 'register/logistics/verify'],
]);

it('blocks storefront and other role pages on the logistics host', function (string $path) {
    $this->get(Portal::url('logistics', $path))->assertNotFound();
})->with(['buyer', 'seller/dashboard', 'register/buyer', 'register/seller', 'register/buyer/verify', 'api/courier/login']);

it('redirects admin pages to the configured admin host', function () {
    $this->get(Portal::url('logistics', 'admin/dashboard'))->assertRedirect(Portal::url('admin', 'admin/dashboard'));
});

it('does not authenticate logistics credentials on main', function () {
    $user = portalPartner();
    $this->post(Portal::url('main', 'login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs admins in through the shared login and keeps their dashboard session', function () {
    expect(Portal::host('admin'))->toBe(Portal::host('main'));

    $admin = User::factory()->create(['role' => 'super_admin']);
    $this->partialMock(DashboardController::class)
        ->shouldReceive('index')->once()->andReturn(response('Admin dashboard'));

    $this->post(Portal::url('main', 'login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Admin dashboard');
    $this->assertAuthenticatedAs($admin);
});

it('rejects invalid admin credentials on the shared login', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->post(Portal::url('main', 'login'), ['email' => $admin->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('keeps admin pages restricted to admins on the shared host', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('admin.dashboard'))->assertForbidden();
})->with(['buyer', 'seller']);

it('preserves role isolation when a separate admin host is configured', function () {
    config(['portals.admin' => 'http://admin.localhost:8000']);

    expect(Portal::loginRoles(Request::create(Portal::url('main', 'login'))))->toBe(['buyer', 'seller'])
        ->and(Portal::loginRoles(Request::create(Portal::url('admin', 'login'))))->toBe(['super_admin']);

    $this->get(Portal::url('main', 'admin/dashboard'))
        ->assertRedirect(Portal::url('admin', 'admin/dashboard'));
});

it('does not authenticate other roles on logistics', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $this->post(Portal::url('logistics', 'login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
})->with(['buyer', 'seller', 'super_admin', 'courier']);

it('logs in approved logistics partners only on their host', function () {
    $user = portalPartner();
    $this->post(Portal::url('logistics', 'login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('logistics.dashboard'));
    $this->assertAuthenticatedAs($user);
    $this->get(Portal::url('logistics'))->assertRedirect(route('logistics.dashboard'));
});

it('limits pending and rejected partners to their application', function (string $status) {
    $user = portalPartner($status);
    $this->post(Portal::url('logistics', 'login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('logistics.application'));
    $this->get(route('logistics.application'))->assertOk();
    $this->get(route('logistics.dashboard'))->assertRedirect(route('logistics.application'));
    $this->getJson(route('logistics.riders.index'))->assertForbidden();
})->with(['pending', 'rejected']);

it('rejects wrong-role authenticated requests to logistics', function () {
    $this->actingAs(User::factory()->create(['role' => 'buyer']));
    $this->getJson(route('logistics.dashboard'))->assertForbidden();
});

it('invalidates a session when its host changes', function () {
    $user = portalPartner();
    $this->actingAs($user)->withSession(['portal_host' => Portal::host('main')]);
    $this->get(route('logistics.application'))->assertRedirect(Portal::url('logistics', 'login'));
    $this->assertGuest();
});

it('issues host-only secure http-only lax cookies with distinct host names', function () {
    config(['session.secure' => true]);
    $main = $this->get(Portal::url('main', 'login'));
    $mainCookie = collect($main->headers->getCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), config('portals.session_cookie')));
    $logistics = $this->get(Portal::url('logistics', 'login'));
    $logisticsCookie = collect($logistics->headers->getCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), config('portals.session_cookie')));
    foreach ([$mainCookie, $logisticsCookie] as $cookie) {
        expect($cookie)->not->toBeNull()
            ->and($cookie->getDomain())->toBeNull()
            ->and($cookie->isSecure())->toBeTrue()
            ->and($cookie->isHttpOnly())->toBeTrue()
            ->and($cookie->getSameSite())->toBe('lax');
    }
    expect($mainCookie->getName())->not->toBe($logisticsCookie->getName());
});

it('uses distinct host-scoped remember cookies and auth session keys', function () {
    $mainRequest = Request::create(Portal::url('main', 'login'));
    $this->app->instance('request', $mainRequest);
    Auth::forgetGuards();
    $mainGuard = Auth::guard('web');
    $logisticsRequest = Request::create(Portal::url('logistics', 'login'));
    $this->app->instance('request', $logisticsRequest);
    Auth::forgetGuards();
    $logisticsGuard = Auth::guard('web');

    expect($mainGuard->getName())->not->toBe($logisticsGuard->getName())
        ->and($mainGuard->getRecallerName())->not->toBe($logisticsGuard->getRecallerName());
});

it('rejects cross-origin form posts even between the two portals', function () {
    $this->withHeader('Origin', config('portals.main'))
        ->post(Portal::url('logistics', 'login'), [])->assertStatus(419);
});

it('trusts forwarded host and protocol for portal routing', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->withHeaders([
        'X-Forwarded-Host' => Portal::host('logistics'),
        'X-Forwarded-Proto' => 'https',
    ])->get(Portal::url('main', 'login'))->assertOk()->assertSee('Log in to Logistics');
});

it('forces https when configured', function () {
    config(['portals.force_https' => true]);
    $response = $this->get('http://'.Portal::host('logistics').'/login');
    $response->assertStatus(308)->assertRedirect('https://'.Portal::host('logistics').'/login');
});

it('builds logistics email links independently of the current host', function () {
    $user = portalPartner();
    $approved = (new LogisticsDecisionNotification(true))->toMail($user);
    $rejected = (new LogisticsDecisionNotification(false, 'Update permit'))->toMail($user);
    $otp = (new OtpNotification('123456', 'registration', 'logistics'))->toMail($user);
    expect($approved->viewData['loginUrl'])->toBe(Portal::url('logistics', 'login'))
        ->and($rejected->viewData['applicationUrl'])->toBe(Portal::route('logistics', 'logistics.application'))
        ->and($otp->viewData['loginUrl'])->toBe(Portal::url('logistics', 'login'));
    expect(view($rejected->view, $rejected->viewData)->render())->toContain('Update permit');
});

it('allows rejected partners to resubmit required documents', function () {
    $user = portalPartner('rejected');
    $this->actingAs($user);
    $files = [];
    foreach (['id_upload', 'business_permit', 'sec_certificate', 'bir_registration'] as $field) {
        $files[$field] = UploadedFile::fake()->create($field.'.pdf', 100, 'application/pdf');
    }
    $this->post(route('logistics.application.resubmit'), [
        ...$files, 'company_name' => 'Updated Delivery', 'business_name' => 'Updated Hub', 'service_regions' => ['Region VII'],
    ])->assertRedirect(route('logistics.application'));
    $profile = $user->logisticsProfile->fresh();
    expect($profile->status)->toBe('pending')->and($profile->service_regions)->toBe(['Region VII']);
    Storage::disk('r2')->assertExists($profile->sec_certificate_path);
    $this->post(route('logistics.application.resubmit'), [])->assertForbidden();
});

it('requires all four documents and valid service regions', function () {
    $this->post(Portal::url('logistics', 'register'), ['service_regions' => ['unknown']])
        ->assertSessionHasErrors(['id_upload', 'business_permit', 'sec_certificate', 'bir_registration', 'service_regions.0']);
});

it('verifies logistics otp only on the logistics host and creates a pending application', function () {
    $attributes = portalPartner()->logisticsProfile->getAttributes();
    LogisticsProfile::query()->delete();
    $email = 'new-partner@example.test';
    app(OtpService::class)->generateAndSend($email, 'logistics');
    $code = null;
    Notification::assertSentOnDemand(OtpNotification::class, function ($notification) use (&$code): bool {
        $code = $notification->toMail(new stdClass)->viewData['otp'];

        return true;
    });
    unset($attributes['id'], $attributes['user_id'], $attributes['created_at'], $attributes['updated_at']);
    $this->withSession(['pending_registration' => [
        'role' => 'logistics', 'email' => $email,
        'user' => ['name' => 'New Partner', 'email' => $email, 'password' => Hash::make('password'), 'role' => 'logistics'],
        'profile' => [...$attributes, 'status' => 'pending'],
    ]])->post(Portal::url('logistics', 'register/logistics/verify'), ['otp' => $code])
        ->assertRedirect(Portal::url('logistics', 'login'));
    expect(User::where('email', $email)->first()->logisticsProfile->status)->toBe('pending');
});

it('registers a company and completes email verification with all documents', function () {
    $documents = [];
    foreach (['id_upload', 'business_permit', 'sec_certificate', 'bir_registration'] as $field) {
        $documents[$field] = UploadedFile::fake()->create($field.'.pdf', 100, 'application/pdf');
    }
    $this->post(Portal::url('logistics', 'register'), [
        ...$documents,
        'last_name' => 'Partner', 'first_name' => 'New', 'sex' => 'female',
        'email' => 'registration@example.test', 'contact_no' => '09123456789',
        'birthday' => '1990-01-01', 'province' => 'Cebu', 'municipality' => 'Cebu City',
        'barangay' => 'Lahug', 'street_address' => '1 Main Street',
        'business_name' => 'New Hub', 'company_name' => 'New Company',
        'service_regions' => ['Region VII'], 'password' => 'test-password',
        'password_confirmation' => 'test-password', 'terms_accepted' => '1',
    ])->assertRedirect(Portal::url('logistics', 'register/logistics/verify'));
    $code = null;
    Notification::assertSentOnDemand(OtpNotification::class, function ($notification) use (&$code): bool {
        $code = $notification->toMail(new stdClass)->viewData['otp'];

        return true;
    });
    $this->get(Portal::url('logistics', 'register/logistics/verify'))->assertOk();
    $this->post(Portal::url('logistics', 'register/logistics/verify'), ['otp' => $code])
        ->assertRedirect(Portal::url('logistics', 'login'));
    $user = User::where('email', 'registration@example.test')->firstOrFail();
    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->logisticsProfile->status)->toBe('pending')
        ->and($user->logisticsProfile->service_regions)->toBe(['Region VII']);
    Storage::disk('r2')->assertExists($user->logisticsProfile->sec_certificate_path);
    Storage::disk('r2')->assertExists($user->logisticsProfile->bir_registration_path);
});

it('sends logistics-host decision emails from admin actions', function (bool $approved) {
    $partner = portalPartner('pending');
    $this->actingAs(User::factory()->create(['role' => 'super_admin']));
    $this->postJson(route('admin.registrations.logistics.'.($approved ? 'approve' : 'reject'), $partner->logisticsProfile), [
        'rejection_reason' => 'Please update your permit.',
    ])->assertOk();
    expect($partner->logisticsProfile->fresh()->status)->toBe($approved ? 'approved' : 'rejected');
    Notification::assertSentTo($partner, LogisticsDecisionNotification::class, fn ($notification): bool => $notification->approved === $approved);
    $this->postJson(route('admin.registrations.logistics.'.($approved ? 'reject' : 'approve'), $partner->logisticsProfile), [
        'rejection_reason' => 'A stale decision from another tab.',
    ])->assertUnprocessable();
    Notification::assertSentToTimes($partner, LogisticsDecisionNotification::class, 1);
    expect($partner->logisticsProfile->fresh()->status)->toBe($approved ? 'approved' : 'rejected');
})->with([true, false]);

it('blocks inactive partners and unverified logistics accounts', function (string $condition) {
    $user = portalPartner();
    $user->update($condition === 'unverified' ? ['email_verified_at' => null] : ['account_status' => $condition]);
    $this->post(Portal::url('logistics', 'login'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
})->with(['suspended', 'deactivated', 'unverified']);

it('enforces document size and content types on resubmission', function () {
    $this->actingAs(portalPartner('rejected'));
    $this->post(route('logistics.application.resubmit'), [
        'sec_certificate' => UploadedFile::fake()->create('oversize.pdf', 5121, 'application/pdf'),
        'bir_registration' => UploadedFile::fake()->create('script.js', 20, 'text/javascript'),
    ])->assertSessionHasErrors(['sec_certificate', 'bir_registration']);
});
