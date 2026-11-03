<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_bags', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            $table->foreignId('from_hub_id')
                ->constrained('logistics_profiles')
                ->cascadeOnDelete();

            $table->foreignId('to_hub_id')
                ->constrained('logistics_profiles')
                ->cascadeOnDelete();

            $table->string('status', 20)->default('open');

            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->foreignId('sealed_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->foreignId('received_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamp('sealed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();

            // Set by the stale-bag command when a bag stays in transit too long.
            $table->timestamp('flagged_at')->nullable();
            $table->string('flag_note')->nullable();

            $table->timestamps();

            $table->index(['from_hub_id', 'status'], 'shipment_bags_from_hub_status_idx');
            $table->index(['to_hub_id', 'status'], 'shipment_bags_to_hub_status_idx');
            $table->index('dispatched_at', 'shipment_bags_dispatched_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_bags');
    }
};
