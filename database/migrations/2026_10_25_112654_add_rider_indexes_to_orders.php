<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Dashboard counts, deliveries list, history and earnings
            $table->index(['delivery_rider_id', 'status', 'delivered_at'], 'orders_delivery_rider_status_idx');
            // Pickups list and counts
            $table->index(['pickup_rider_id', 'status'], 'orders_pickup_rider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_delivery_rider_status_idx');
            $table->dropIndex('orders_pickup_rider_status_idx');
        });
    }
};
