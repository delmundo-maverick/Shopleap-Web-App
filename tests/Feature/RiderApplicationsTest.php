<?php

use App\Models\CourierProfile;
use App\Models\LogisticsProfile;
use App\Models\User;
use App\Notifications\RiderDecisionNotification;
use App\Support\Portal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    foreach ([
        '2026_09_18_103005_create_logistics_profiles_table.php',
        '2026_09_24_161929_create_courier_profiles_table.php',
        '2026_10_02_171800_create_hub_service_areas_table.php',
        '2026_10_03_232741_create_activity_logs_table.php',
        '2026_10_04_180045_create_announcements_table.php',
        '2026_10_22_161655_create_notifications_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->hub = riderReviewHub();
    $this->actingAs($this->hub->user);
    Storage::fake('r2');
    Notification::fake();
});

function riderReviewHub(): LogisticsProfile
{
    $user = User::factory()->create(['role' => 'logistics']);

    return $user->logisticsProfile()->create([
        'last_name' => 'Partner', 'first_name' => 'Hub', 'sex' => 'female',
        'contact_no' => '09123456789', 'birthday' => '1990-01-01', 'age' => 36,
        'province' => 'Cebu', 'municipality' => 'Cebu City', 'barangay' => 'Lahug',
        'street_address' => '1 Hub Street', 'business_name' => 'Delivery Hub',
        'id_upload_path' => 'id.pdf', 'business_permit_path' => 'permit.pdf', 'status' => 'approved',
    ]);
}

function riderReviewApplicant(LogisticsProfile $hub, array $attributes = []): CourierProfile
{
    $user = User::factory()->create(['role' => 'courier', 'email' => fake()->unique()->safeEmail()]);

    return $user->courierProfile()->create([
        'logistics_profile_id' => $hub->id,
        'last_name' => 'Applicant', 'first_name' => 'Jamie', 'middle_initial' => 'R',
        'sex' => 'female', 'contact_no' => '09912345678', 'birthday' => '1994-06-10', 'age' => 32,
        'province' => 'Cebu', 'municipality' => 'Cebu City', 'barangay' => 'Lahug',
        'street_address' => '25 Rider Street', 'vehicle_type' => 'Motorcycle', 'plate_number' => 'ABC 1234',
        'or_cr_upload_path' => 'riders/or-cr.pdf', 'license_upload_path' => 'riders/license.png',
        'delivery_area' => 'Lahug', 'status' => 'pending', 'is_active' => false, ...$attributes,
    ]);
}

it('shows hub scoped applications with review links instead of immediate decisions', function () {
    $rider = riderReviewApplicant($this->hub);
    $other = riderReviewApplicant(riderReviewHub(), ['first_name' => 'Hidden Applicant']);
    $response = $this->get(route('logistics.riders.index'));
    $response->assertOk()->assertSee('Jamie Applicant')->assertSee('View application')
        ->assertSee(route('logistics.riders.show', $rider), false)->assertDontSee($other->first_name)
        ->assertDontSee(route('logistics.riders.approve', $rider), false)
        ->assertDontSee(route('logistics.riders.reject', $rider), false);
    expect($response->viewData('counts')['pending'])->toBe(1);
});

it('searches rider emails case insensitively and filters by status', function () {
    $rider = riderReviewApplicant($this->hub);
    $rider->user->update(['email' => 'jamie@example.test']);
    riderReviewApplicant($this->hub, ['first_name' => 'Other', 'status' => 'approved']);
    $this->get(route('logistics.riders.index', ['search' => 'JAMIE@EXAMPLE', 'status' => 'all']))
        ->assertOk()->assertSee('Jamie Applicant')->assertDontSee('Other Applicant');
    $this->get(route('logistics.riders.index', ['status' => 'approved']))
        ->assertOk()->assertSee('Other Applicant')->assertDontSee('Jamie Applicant');
});

it('validates rider list filters', function () {
    $this->getJson(route('logistics.riders.index', ['status' => 'unknown', 'search' => str_repeat('x', 101)]))
        ->assertUnprocessable()->assertJsonValidationErrors(['status', 'search']);
});

it('renders the submitted form and private document links before decisions', function () {
    $rider = riderReviewApplicant($this->hub);
    $response = $this->get(route('logistics.riders.show', $rider));
    $response->assertOk()->assertSee('Personal information')->assertSee('25 Rider Street')
        ->assertSee('Motorcycle')->assertSee('ABC 1234')->assertSee('Vehicle OR / CR')
        ->assertSee(route('logistics.riders.document', [$rider, 'license']), false)
        ->assertSee(route('logistics.riders.document', [$rider, 'or-cr']), false)
        ->assertSee('Approve rider')->assertSee('Reason for rejection')
        ->assertDontSee('riders/license.png')->assertDontSee('riders/or-cr.pdf');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('streams a riders own documents with private cache headers', function (string $document, string $field, string $mime) {
    $rider = riderReviewApplicant($this->hub);
    if ($mime === 'image/png') {
        Storage::disk('r2')->put($rider->$field, UploadedFile::fake()->image('license.png')->getContent());
    } else {
        Storage::disk('r2')->put($rider->$field, "%PDF-1.4\nTest application document");
    }
    $response = $this->get(route('logistics.riders.document', [$rider, $document]));
    $response->assertOk()->assertHeader('Content-Type', $mime)->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    expect($response->streamedContent())->not->toBeEmpty();
})->with([
    ['license', 'license_upload_path', 'image/png'],
    ['or-cr', 'or_cr_upload_path', 'application/pdf'],
]);

it('blocks another hubs application documents and decision endpoints', function () {
    $rider = riderReviewApplicant(riderReviewHub());
    $this->get(route('logistics.riders.show', $rider))->assertForbidden();
    $this->get(route('logistics.riders.document', [$rider, 'license']))->assertForbidden();
    $this->post(route('logistics.riders.approve', $rider))->assertForbidden();
    $this->post(route('logistics.riders.reject', $rider), ['rejection_reason' => 'Invalid'])->assertForbidden();
    $this->post(route('logistics.riders.toggle-active', $rider))->assertForbidden();
    Notification::assertNothingSent();
});

it('does not serve missing unknown or unsafe documents', function () {
    $rider = riderReviewApplicant($this->hub);
    $this->get(route('logistics.riders.document', [$rider, 'license']))->assertNotFound();
    $this->get(Portal::url('logistics', 'logistics/riders/'.$rider->id.'/documents/password'))->assertNotFound();
    Storage::disk('r2')->put($rider->license_upload_path, '<script>alert(1)</script>');
    $this->get(route('logistics.riders.document', [$rider, 'license']))->assertNotFound();
});

it('approves reviewed riders and emails the decision only once', function () {
    $rider = riderReviewApplicant($this->hub);
    $this->post(route('logistics.riders.approve', $rider))->assertRedirect(route('logistics.riders.show', $rider));
    expect($rider->fresh()->status)->toBe('approved')->and($rider->fresh()->is_active)->toBeTrue();
    Notification::assertSentOnDemand(RiderDecisionNotification::class, fn ($notification): bool => $notification->toMail(new stdClass)->viewData['approved'] === true);
    $this->post(route('logistics.riders.reject', $rider), ['rejection_reason' => 'Already reviewed'])->assertRedirect();
    Notification::assertSentOnDemandTimes(RiderDecisionNotification::class, 1);
    expect($rider->fresh()->status)->toBe('approved');
});

it('requires a rejection reason and emails it to the rider', function () {
    $rider = riderReviewApplicant($this->hub);
    $this->from(route('logistics.riders.show', $rider))->post(route('logistics.riders.reject', $rider), [])->assertSessionHasErrors('rejection_reason');
    Notification::assertNothingSent();
    $this->post(route('logistics.riders.reject', $rider), ['rejection_reason' => 'Please upload a clear license.'])
        ->assertRedirect(route('logistics.riders.show', $rider));
    expect($rider->fresh()->status)->toBe('rejected')->and($rider->fresh()->is_active)->toBeFalse();
    Notification::assertSentOnDemand(RiderDecisionNotification::class, fn ($notification): bool => $notification->toMail(new stdClass)->viewData['reason'] === 'Please upload a clear license.');
    $this->get(route('logistics.riders.show', $rider))->assertOk()->assertSee('Please upload a clear license.')->assertDontSee('Approve rider');
});

it('keeps activation controls on approved application detail pages', function () {
    $rider = riderReviewApplicant($this->hub, ['status' => 'approved', 'is_active' => true]);
    $this->get(route('logistics.riders.show', $rider))->assertOk()->assertSee('Deactivate rider')->assertDontSee('Approve rider');
    $this->post(route('logistics.riders.toggle-active', $rider))->assertRedirect(route('logistics.riders.show', $rider));
    expect($rider->fresh()->is_active)->toBeFalse();
});

it('redirects main host reviews and denies non logistics users', function () {
    $rider = riderReviewApplicant($this->hub);
    $this->get(Portal::url('main', 'logistics/riders/'.$rider->id))->assertRedirect(route('logistics.riders.show', $rider));
    $this->actingAs(User::factory()->create(['role' => 'buyer']));
    $this->getJson(route('logistics.riders.show', $rider))->assertForbidden();
});
