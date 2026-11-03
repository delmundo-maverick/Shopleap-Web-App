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
            $table->foreignId('origin_hub_id')
                ->nullable()
                ->after('logistics_profile_id')
                ->constrained('logistics_profiles')
                ->nullOnDelete();

            $table->foreignId('destination_hub_id')
                ->nullable()
                ->after('origin_hub_id')
                ->constrained('logistics_profiles')
                ->nullOnDelete();

            $table->index('origin_hub_id', 'orders_origin_hub_id_idx');
            $table->index('destination_hub_id', 'orders_destination_hub_id_idx');
        });

        // Every existing order was handled by one hub, so both ends are that hub.
        DB::table('orders')
            ->whereNotNull('logistics_profile_id')
            ->update([
                'origin_hub_id' => DB::raw('logistics_profile_id'),
                'destination_hub_id' => DB::raw('logistics_profile_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['origin_hub_id']);
            $table->dropForeign(['destination_hub_id']);
            $table->dropIndex('orders_origin_hub_id_idx');
            $table->dropIndex('orders_destination_hub_id_idx');
            $table->dropColumn(['origin_hub_id', 'destination_hub_id']);
        });
    }
};
