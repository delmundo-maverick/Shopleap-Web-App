<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Weight of one unit of this product, in kilograms.
            $table->decimal('weight_kg', 8, 3)->nullable()->after('stock_quantity');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            // Optional per-variant override. Falls back to the product weight.
            $table->decimal('weight_kg', 8, 3)->nullable()->after('stock_quantity');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_weight_kg_nonnegative CHECK (weight_kg IS NULL OR weight_kg >= 0)');
            DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_weight_kg_nonnegative CHECK (weight_kg IS NULL OR weight_kg >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_weight_kg_nonnegative');
            DB::statement('ALTER TABLE product_variants DROP CONSTRAINT IF EXISTS product_variants_weight_kg_nonnegative');
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('weight_kg');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('weight_kg');
        });
    }
};
