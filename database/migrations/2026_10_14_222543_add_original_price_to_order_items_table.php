<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Pre-discount unit price at checkout. price_at_purchase stays the amount actually charged.
            $table->decimal('original_price', 10, 2)->nullable()->after('price_at_purchase');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('original_price');
        });
    }
};
