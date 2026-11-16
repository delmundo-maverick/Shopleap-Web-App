<?php

use App\Models\Conversation;
use App\Models\CourierProfile;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
    });
    Schema::create('courier_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->timestamps();
    });
    Schema::create('conversations', function (Blueprint $table): void {
        $table->id();
        $table->string('subject');
        $table->timestamps();
    });
    Schema::create('conversation_participants', function (Blueprint $table): void {
        $table->foreignId('conversation_id');
        $table->foreignId('user_id');
        $table->timestamps();
    });
    Schema::create('messages', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('conversation_id');
        $table->foreignId('sender_id');
        $table->text('body');
        $table->foreignId('forwarded_from_message_id')->nullable();
        $table->timestamps();
    });
    (require database_path('migrations/2026_10_22_161655_create_notifications_table.php'))->up();
    $this->riderUser = User::factory()->create(['role' => 'courier']);
    $courier = new CourierProfile;
    $courier->forceFill(['id' => 41, 'user_id' => $this->riderUser->id])->save();
    $this->sender = User::factory()->create(['role' => 'seller']);
    $this->conversation = Conversation::create(['subject' => 'Pickup']);
    $this->conversation->participants()->attach([$this->riderUser->id, $this->sender->id]);
    config()->set('services.supabase', ['url' => 'https://realtime.example.test', 'service_key' => 'test-service-key']);
    Http::preventStrayRequests();
    Http::fake(function () {
        expect(DB::transactionLevel())->toBe(0);

        return Http::response([], 202);
    });
});

it('refreshes courier chat participants after commit for incoming outgoing and forwarded messages', function (bool $fromCourier, bool $forwarded) {
    DB::transaction(function () use ($fromCourier, $forwarded) {
        $this->conversation->messages()->create([
            'sender_id' => $fromCourier ? $this->riderUser->id : $this->sender->id,
            'body' => 'The parcel is ready.', 'forwarded_from_message_id' => $forwarded ? 123 : null,
        ]);
        Http::assertNothingSent();
    });

    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => count($request['messages']) === 1 && $request['messages'][0]['topic'] === 'rider:41');
})->with(['incoming' => [false, false], 'outgoing' => [true, false], 'forwarded' => [false, true]]);

it('discards message refreshes when the transaction rolls back', function () {
    DB::beginTransaction();
    $this->conversation->messages()->create(['sender_id' => $this->sender->id, 'body' => 'Rolled back']);
    DB::rollBack();

    Http::assertNothingSent();
    $this->assertDatabaseCount('messages', 0);
});

it('only notifies profiles belonging to participants of the conversation', function () {
    $conversation = Conversation::create(['subject' => 'Other conversation']);
    $conversation->participants()->attach($this->sender->id);
    $conversation->messages()->create(['sender_id' => $this->sender->id, 'body' => 'No courier']);

    Http::assertNothingSent();
});

it('refreshes a courier after a database notification is committed', function () {
    DB::transaction(function () {
        $this->riderUser->notify(new ActivityNotification('test.created', 'New notification', 'Details'));
        Http::assertNothingSent();
    });

    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => $request['messages'][0]['topic'] === 'rider:41');
});

it('does not broadcast rolled back notifications or notifications for other roles', function () {
    DB::beginTransaction();
    $this->riderUser->notify(new ActivityNotification('test.created', 'Rolled back', 'Details'));
    DB::rollBack();
    $this->sender->notify(new ActivityNotification('test.created', 'Seller notification', 'Details'));

    Http::assertNothingSent();
});

it('does not duplicate refreshes already sent by the operation creating a notification', function () {
    $this->riderUser->notify(new ActivityNotification('test.created', 'Already refreshed', 'Details', refreshRider: false));

    Http::assertNothingSent();
});
