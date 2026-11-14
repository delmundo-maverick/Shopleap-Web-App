<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('order_number', 32)->nullable()->unique();
            $table->string('waybill_code', 32)->nullable()->unique();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Public order references may already be printed. Preserve them and use a forward migration.');
    }
};
