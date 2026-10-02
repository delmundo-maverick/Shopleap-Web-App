<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('assigned_rider_id', 'pickup_rider_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_rider_id')->nullable()->after('pickup_rider_id')
                ->constrained('courier_profiles')->nullOnDelete();

            $table->string('pickup_status')->nullable();          // requested | accepted | declined
            $table->date('pickup_date')->nullable();
            $table->string('pickup_time_slot')->nullable();       // morning | afternoon
            $table->text('pickup_decline_reason')->nullable();
            $table->unsignedTinyInteger('delivery_attempts')->default(0);
            $table->timestamp('stock_restored_at')->nullable();

            // Delivery address snapshot taken at checkout
            $table->string('ship_province')->nullable();
            $table->string('ship_municipality')->nullable();
            $table->string('ship_barangay')->nullable();
            $table->string('ship_street')->nullable();
            $table->string('ship_contact_no', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_rider_id');
            $table->dropColumn([
                'pickup_status',
                'pickup_date',
                'pickup_time_slot',
                'pickup_decline_reason',
                'delivery_attempts',
                'stock_restored_at',
                'ship_province',
                'ship_municipality',
                'ship_barangay',
                'ship_street',
                'ship_contact_no',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('pickup_rider_id', 'assigned_rider_id');
        });
    }
};
