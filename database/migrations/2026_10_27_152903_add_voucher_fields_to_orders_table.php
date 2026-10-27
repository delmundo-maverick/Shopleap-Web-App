<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shop_voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->string('voucher_code')->nullable()->index();
            $table->decimal('voucher_discount', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shop_voucher_id']);
            $table->dropColumn(['shop_voucher_id', 'voucher_code', 'voucher_discount']);
        });
    }
};
