<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_bag_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bag_id')
                ->constrained('shipment_bags')
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Null until the receiving hub scans/confirms the parcel.
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->index(['bag_id', 'received_at'], 'shipment_bag_items_bag_received_idx');
            $table->index('order_id', 'shipment_bag_items_order_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_bag_items');
    }
};
