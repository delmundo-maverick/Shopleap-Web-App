<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('hub_id')->nullable()
                ->constrained('logistics_profiles')->nullOnDelete();

            $table->foreignId('rider_id')->nullable()
                ->constrained('courier_profiles')->nullOnDelete();

            $table->string('event_type', 40);

            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            $table->text('note')->nullable();

            $table->foreignId('actor_user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['order_id', 'id'], 'parcel_events_order_id_id_idx');
            $table->index(['order_id', 'created_at'], 'parcel_events_order_created_idx');
            $table->index('event_type', 'parcel_events_event_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_events');
    }
};
