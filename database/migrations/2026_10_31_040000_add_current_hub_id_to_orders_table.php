<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('current_hub_id')
                ->nullable()
                ->after('destination_hub_id')
                ->constrained('logistics_profiles')
                ->nullOnDelete();

            $table->index('current_hub_id', 'orders_current_hub_id_idx');
        });

        // The hub that was holding the parcel is the same one stored in
        // logistics_profile_id before this column existed.
        DB::table('orders')
            ->whereNotNull('logistics_profile_id')
            ->update([
                'current_hub_id' => DB::raw('logistics_profile_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['current_hub_id']);
            $table->dropIndex('orders_current_hub_id_idx');
            $table->dropColumn('current_hub_id');
        });
    }
};
