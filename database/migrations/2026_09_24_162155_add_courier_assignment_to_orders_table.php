<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('logistics_profile_id')->nullable()->after('seller_id')
                ->constrained('logistics_profiles')->nullOnDelete();
            $table->foreignId('assigned_rider_id')->nullable()->after('logistics_profile_id')
                ->constrained('courier_profiles')->nullOnDelete();
            $table->string('delivery_area')->nullable()->after('assigned_rider_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_rider_id');
            $table->dropConstrainedForeignId('logistics_profile_id');
            $table->dropColumn('delivery_area');
        });
    }
};
