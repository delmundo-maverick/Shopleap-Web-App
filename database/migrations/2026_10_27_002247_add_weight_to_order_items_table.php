<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Snapshot of the whole line's weight (unit weight x quantity), in kilograms.
            $table->decimal('weight_kg', 10, 3)->nullable()->after('quantity');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_weight_kg_nonnegative CHECK (weight_kg IS NULL OR weight_kg >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE order_items DROP CONSTRAINT IF EXISTS order_items_weight_kg_nonnegative');
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('weight_kg');
        });
    }
};
