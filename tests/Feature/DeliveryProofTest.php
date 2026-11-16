<?php

use App\Models\CourierProfile;
use App\Models\Order;
use App\Models\User;
use App\Services\DeliveryProofImages;
use App\Services\Notifier;
use App\Services\OrderLedger;
use App\Services\OrderTransitions;
use App\Support\Portal;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('r2');
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('buyer');
        $table->string('account_status')->default('active');
    });
    Schema::create('logistics_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('business_name');
        $table->string('status')->default('approved');
        $table->timestamps();
    });
    Schema::create('courier_profiles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('logistics_profile_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->string('status')->default('approved');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->string('order_number')->unique();
        $table->string('waybill_code')->unique();
        foreach (['earning_hub_id', 'earning_courier_id', 'logistics_profile_id', 'current_hub_id', 'delivery_rider_id'] as $column) {
            $table->foreignId($column)->nullable();
        }
        $table->string('status');
        $table->decimal('rider_pay', 12, 2)->nullable();
        $table->decimal('delivered_shipping_fee', 12, 2)->nullable();
        $table->decimal('cod_collected_amount', 12, 2)->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamp('earnings_recorded_at')->nullable();
        $table->timestamps();
    });
    (require database_path('migrations/2026_11_15_094113_create_delivery_proofs_table.php'))->up();
    $this->hubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $this->hub = $this->hubUser->logisticsProfile()->create(['business_name' => 'Proof Hub', 'status' => 'approved']);
    $this->riderUser = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);
    $this->rider = CourierProfile::create(['user_id' => $this->riderUser->id, 'logistics_profile_id' => $this->hub->id, 'first_name' => 'Test', 'last_name' => 'Rider']);
    $this->order = new Order;
    $this->order->forceFill(['status' => Order::STATUS_DELIVERED, 'logistics_profile_id' => $this->hub->id, 'current_hub_id' => $this->hub->id,
        'delivery_rider_id' => $this->rider->id, 'earning_hub_id' => $this->hub->id, 'earning_courier_id' => $this->rider->id,
        'rider_pay' => '20.00', 'delivered_shipping_fee' => '40.00', 'cod_collected_amount' => '140.00',
        'delivered_at' => now()->subDay(), 'earnings_recorded_at' => now()->subDay()])->save();
    $this->endpoint = '/api/courier/deliveries/'.$this->order->order_number.'/delivered';
    Sanctum::actingAs($this->riderUser);
});

it('saves a missing proof on delivered and completed orders without changing delivery money or timestamps', function (string $status) {
    $this->order->forceFill(['status' => $status])->save();
    $original = $this->order->fresh()->getAttributes();
    $response = $this->postJson($this->endpoint, ['amount' => 999, 'proof_image' => UploadedFile::fake()->image('proof.jpg', 800, 800),
        'captured_at' => '2026-11-15T08:30:00+08:00', 'latitude' => 10.3157, 'longitude' => 123.8854, 'notes' => 'Received by buyer.']);
    $response->assertSuccessful()->assertJsonPath('proof.uploaded', true)->assertJsonPath('earning.amount', '20.00');
    $proof = $this->order->fresh()->deliveryProof;
    expect($proof)->not->toBeNull()
        ->and($proof->courier_id)->toBe($this->rider->id)
        ->and($proof->logistics_profile_id)->toBe($this->hub->id)
        ->and($proof->captured_at->utc()->toIso8601String())->toBe('2026-11-15T00:30:00+00:00')
        ->and($this->order->fresh()->getAttributes())->toBe($original);
    Storage::disk('r2')->assertExists([$proof->image_path, $proof->thumbnail_path]);
    expect(getimagesizefromstring(Storage::disk('r2')->get($proof->image_path))['mime'])->toBe('image/jpeg');
    $this->actingAs($this->hubUser);
    $image = $this->get(Portal::url('logistics', 'logistics/delivery-proofs/'.$this->order->order_number.'/image'));
    $image->assertSuccessful()->assertHeader('Content-Type', 'image/jpeg');
    expect($image->streamedContent())->toBe(Storage::disk('r2')->get($proof->image_path));
})->with([Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);

it('requires the actual proof file when a delivered order has no proof', function () {
    $this->postJson($this->endpoint, ['amount' => 140, 'proof_image' => 'file:///camera/local.jpg'])
        ->assertUnprocessable()->assertJsonValidationErrors('proof_image');
    $this->postJson($this->endpoint, [])->assertUnprocessable()->assertJsonValidationErrors('proof_image');
    expect($this->order->deliveryProof()->exists())->toBeFalse();
});

it('validates the dimensions and size of a missing proof on retry', function () {
    $this->postJson($this->endpoint, ['proof_image' => UploadedFile::fake()->image('tiny.jpg', 100, 100)])
        ->assertUnprocessable()->assertJsonValidationErrors('proof_image');
    $this->postJson($this->endpoint, ['proof_image' => UploadedFile::fake()->image('large.jpg', 800, 800)->size(5121)])
        ->assertUnprocessable()->assertJsonValidationErrors('proof_image');
});

it('keeps an existing proof unchanged on repeated delivery submissions', function () {
    $this->postJson($this->endpoint, ['proof_image' => UploadedFile::fake()->image('first.jpg', 800, 800)])->assertSuccessful();
    $original = $this->order->fresh()->deliveryProof->getAttributes();
    $this->postJson($this->endpoint, ['proof_image' => UploadedFile::fake()->image('second.jpg', 900, 900)])
        ->assertSuccessful()->assertJsonPath('proof.uploaded', true);
    $this->postJson($this->endpoint, [])->assertSuccessful()->assertJsonPath('proof.uploaded', true);
    expect($this->order->fresh()->deliveryProof->getAttributes())->toBe($original)
        ->and($this->order->deliveryProof()->count())->toBe(1)
        ->and(Storage::disk('r2')->allFiles())->toHaveCount(2);
});

it('only accepts a missing proof from the original delivery rider', function () {
    $other = User::factory()->create(['role' => 'courier', 'account_status' => 'active']);
    CourierProfile::create(['user_id' => $other->id, 'logistics_profile_id' => $this->hub->id, 'first_name' => 'Other', 'last_name' => 'Rider']);
    Sanctum::actingAs($other);
    $this->postJson($this->endpoint, ['proof_image' => UploadedFile::fake()->image('proof.jpg', 800, 800)])->assertForbidden();
    expect($this->order->deliveryProof()->exists())->toBeFalse();
});

it('rolls back partial image uploads and allows a later retry', function () {
    $disk = Storage::disk('r2');
    Storage::shouldReceive('disk')->with('r2')->andReturn($disk);
    $this->partialMock(DeliveryProofImages::class)->shouldReceive('store')->once()
        ->andReturnUsing(function (UploadedFile $file, array &$stored) use ($disk): array {
            $stored[] = 'delivery-proofs/partial.jpg';
            $disk->put($stored[0], 'partial');
            throw new RuntimeException('Storage unavailable');
        });
    $original = $this->order->fresh()->getAttributes();
    $this->postJson($this->endpoint, ['proof_image' => UploadedFile::fake()->image('proof.jpg', 800, 800)])->assertServiceUnavailable();
    expect($this->order->fresh()->getAttributes())->toBe($original)
        ->and($this->order->deliveryProof()->exists())->toBeFalse();
    $disk->assertMissing('delivery-proofs/partial.jpg');
    $this->app->forgetInstance(DeliveryProofImages::class);
    $this->postJson($this->endpoint, ['proof_image' => UploadedFile::fake()->image('proof.jpg', 800, 800)])->assertSuccessful();
});

it('records a new delivery and its photo together before treating repeats as successful', function () {
    foreach (['2026_11_08_002022_create_rider_rates_table.php', '2026_10_02_171814_create_order_events_table.php', '2026_10_31_030000_create_parcel_events_table.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    Schema::table('orders', function (Blueprint $table): void {
        $table->decimal('total_amount', 12, 2)->default(100);
        $table->decimal('shipping_fee', 12, 2)->default(40);
        $table->timestamp('cod_collected_at')->nullable();
    });
    $this->mock(Notifier::class)->shouldReceive('orderStatusChanged')->once();
    $this->mock(OrderLedger::class)->shouldReceive('delivered')->once();
    $this->order->forceFill(['status' => Order::STATUS_OUT_FOR_DELIVERY, 'delivered_at' => null, 'earnings_recorded_at' => null])->save();
    $this->order->load('deliveryProof');
    $transitions = app(OrderTransitions::class);
    expect($transitions->delivered($this->order, $this->riderUser->id, 140, $this->rider->id, $this->hub->id,
        UploadedFile::fake()->image('proof.jpg', 800, 800)))->toBeTrue();
    expect($this->order->status)->toBe(Order::STATUS_DELIVERED)
        ->and($this->order->deliveryProof->riderSummary()['uploaded'])->toBeTrue()
        ->and($this->order->cod_collected_amount)->toBe('140.00')
        ->and($this->order->events()->count())->toBe(2)
        ->and($this->order->parcelEvents()->count())->toBe(1);
    Storage::disk('r2')->assertExists($this->order->deliveryProof->image_path);
    expect($transitions->delivered($this->order, $this->riderUser->id, 999, $this->rider->id, $this->hub->id))->toBeTrue()
        ->and($this->order->fresh()->cod_collected_amount)->toBe('140.00')
        ->and($this->order->deliveryProof()->count())->toBe(1);
});

it('denies another hub access to a saved proof', function () {
    $this->postJson($this->endpoint, ['proof_image' => UploadedFile::fake()->image('proof.jpg', 800, 800)])->assertSuccessful();
    $otherHubUser = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $otherHubUser->logisticsProfile()->create(['business_name' => 'Other Hub', 'status' => 'approved']);
    $this->actingAs($otherHubUser)->get(Portal::url('logistics', 'logistics/delivery-proofs/'.$this->order->order_number.'/image'))->assertForbidden();
});
