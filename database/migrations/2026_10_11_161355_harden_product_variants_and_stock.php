<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Variants are archived, never deleted: cart_items / order_items point at them,
            // and deleting would null those links (nullOnDelete) and lose the order history.
            $table->boolean('is_active')->default(true)->after('sku');

            // Postgres allows many NULLs in a unique index, so "no SKU" stays valid.
            $table->unique(['product_id', 'sku']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('UPDATE products SET stock_quantity = 0 WHERE stock_quantity < 0');
            DB::statement('UPDATE product_variants SET stock_quantity = 0 WHERE stock_quantity < 0');

            DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock_quantity_nonnegative CHECK (stock_quantity >= 0)');
            DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_stock_quantity_nonnegative CHECK (stock_quantity >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_stock_quantity_nonnegative');
            DB::statement('ALTER TABLE product_variants DROP CONSTRAINT IF EXISTS product_variants_stock_quantity_nonnegative');
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'sku']);
            $table->dropColumn('is_active');
        });
    }
};
