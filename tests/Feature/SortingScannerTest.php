<?php

use App\Models\Order;
use App\Models\SortingScan;
use App\Models\SortingSession;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->default('logistics');
        $table->string('account_status')->default('active');
    });
    Schema::create('logistics_profiles', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('business_name');
        $table->string('status')->default('approved');
        $table->timestamps();
    });
    Schema::create('orders', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('logistics_profile_id');
        $table->unsignedBigInteger('current_hub_id');
        $table->string('status');
        $table->timestamps();
    });
    foreach ([
        '2026_10_28_163144_create_sorting_sessions_table.php',
        '2026_10_28_163615_create_sorting_scans_table.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }

    $user = User::factory()->create(['role' => 'logistics', 'account_status' => 'active']);
    $hub = $user->logisticsProfile()->create(['business_name' => 'Sorting Hub', 'status' => 'approved']);
    $this->actingAs($user);
    $this->sortingSession = SortingSession::create([
        'logistics_profile_id' => $hub->id,
        'user_id' => $user->id,
        'started_at' => now(),
    ]);
    $this->order = Order::create([
        'logistics_profile_id' => $hub->id,
        'current_hub_id' => $hub->id,
        'status' => Order::STATUS_AWAITING_TRANSFER,
    ]);
});

it('returns a friendly duplicate result after upgrading the legacy scan constraint', function (string $originalResult) {
    Schema::table('sorting_scans', function (Blueprint $table): void {
        $table->unique(['sorting_session_id', 'order_id'], 'unique_session_order');
    });
    $original = $this->sortingSession->scans()->create([
        'order_id' => $this->order->id,
        'scanned_code' => 'SHOPLEAP:ORDER:'.$this->order->id,
        'result' => $originalResult,
        'message' => 'Original scan.',
        'destination_municipality' => 'City of Dasmariñas',
        'destination_province' => 'Cavite',
        'destination_barangay' => 'Langkaan II',
        'metadata' => ['awaiting_transfer' => true],
    ]);

    $migration = require database_path('migrations/2026_11_11_225813_remove_legacy_unique_session_order_from_sorting_scans.php');
    $migration->up();
    $migration->up();

    foreach ([1, 2] as $duplicateCount) {
        $this->postJson(route('logistics.sorting.sessions.scan', $this->sortingSession), [
            'code' => 'SHOPLEAP:ORDER:'.$this->order->id,
        ])->assertSuccessful()
            ->assertJsonPath('result', 'duplicate')
            ->assertJsonPath('message', "Order #{$this->order->id} was already scanned in this session.")
            ->assertJsonPath('scan.destination_barangay', 'Langkaan II')
            ->assertJsonPath('scan.metadata.awaiting_transfer', true)
            ->assertJsonPath('progress.total_scans', $duplicateCount + 1)
            ->assertJsonPath('progress.duplicates', $duplicateCount)
            ->assertJsonPath('progress.sorted', $originalResult === SortingScan::RESULT_SORTED ? 1 : 0)
            ->assertJsonPath('progress.needs_review', $originalResult === SortingScan::RESULT_NEEDS_REVIEW ? 1 : 0);
    }

    expect($original->fresh()->result)->toBe($originalResult)
        ->and($this->order->fresh()->status)->toBe(Order::STATUS_AWAITING_TRANSFER);

    $migration->down();
    expect($this->sortingSession->scans()->count())->toBe(3);
})->with([SortingScan::RESULT_SORTED, SortingScan::RESULT_NEEDS_REVIEW]);

it('can apply the correction to a fresh database without the legacy constraint', function () {
    $migration = require database_path('migrations/2026_11_11_225813_remove_legacy_unique_session_order_from_sorting_scans.php');
    $migration->up();

    expect(Schema::hasIndex('sorting_scans', 'unique_session_order'))->toBeFalse();

    foreach ([1, 2] as $rejectedCount) {
        $this->postJson(route('logistics.sorting.sessions.scan', $this->sortingSession), [
            'code' => 'SHOPLEAP:ORDER:'.$this->order->id,
        ])->assertSuccessful()
            ->assertJsonPath('result', 'rejected')
            ->assertJsonPath('progress.rejected', $rejectedCount);
    }
});
