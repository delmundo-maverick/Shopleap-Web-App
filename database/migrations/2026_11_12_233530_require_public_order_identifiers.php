<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('order_number', 32)->nullable(false)->change();
            $table->string('waybill_code', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('order_number', 32)->nullable()->change();
            $table->string('waybill_code', 32)->nullable()->change();
        });
    }
};
