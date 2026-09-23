<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE cart_items DROP CONSTRAINT IF EXISTS cart_items_user_id_product_id_unique');
        DB::statement('ALTER TABLE cart_items ADD CONSTRAINT cart_items_user_variant_unique UNIQUE (user_id, product_id, product_variant_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cart_items DROP CONSTRAINT IF EXISTS cart_items_user_variant_unique');
        DB::statement('ALTER TABLE cart_items ADD CONSTRAINT cart_items_user_id_product_id_unique UNIQUE (user_id, product_id)');
    }
};
