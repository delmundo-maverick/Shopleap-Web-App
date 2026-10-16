<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('zone', 30)->unique();
            $table->string('label');
            $table->decimal('fee', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('shipping_rates')->insert([
            ['zone' => 'same_municipality',  'label' => 'Same city/municipality', 'fee' => 40,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['zone' => 'same_province',      'label' => 'Same province',          'fee' => 70,  'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['zone' => 'different_province', 'label' => 'Different province',     'fee' => 120, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
