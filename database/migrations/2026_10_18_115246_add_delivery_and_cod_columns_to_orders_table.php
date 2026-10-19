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
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('cod_collected_amount', 10, 2)->nullable();
            $table->timestamp('cod_collected_at')->nullable();
        });

        // Orders already delivered get a delivered_at so auto-complete can pick them up.
        DB::table('orders')
            ->whereIn('status', ['DELIVERED', 'COMPLETED'])
            ->whereNull('delivered_at')
            ->update(['delivered_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'cod_collected_amount', 'cod_collected_at']);
        });
    }
};
