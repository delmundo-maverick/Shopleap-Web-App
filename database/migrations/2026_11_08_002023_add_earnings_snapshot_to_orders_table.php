<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('earning_hub_id')->nullable()->constrained('logistics_profiles')->restrictOnDelete();
            $table->foreignId('earning_courier_id')->nullable()->constrained('courier_profiles')->restrictOnDelete();
            $table->decimal('rider_pay', 10, 2)->nullable();
            $table->decimal('delivered_shipping_fee', 10, 2)->nullable();
            $table->timestamp('earnings_recorded_at')->nullable();
            $table->index(['earning_hub_id', 'delivered_at']);
            $table->index(['earning_courier_id', 'delivered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['earning_hub_id', 'delivered_at']);
            $table->dropIndex(['earning_courier_id', 'delivered_at']);
            $table->dropConstrainedForeignId('earning_hub_id');
            $table->dropConstrainedForeignId('earning_courier_id');
            $table->dropColumn(['rider_pay', 'delivered_shipping_fee', 'earnings_recorded_at']);
        });
    }
};
