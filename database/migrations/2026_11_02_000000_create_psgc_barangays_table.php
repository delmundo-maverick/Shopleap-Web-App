<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psgc_barangays', function (Blueprint $table) {
            $table->id();
            $table->string('province_code');
            $table->string('province');
            $table->string('municipality_code');
            $table->string('municipality');
            $table->string('barangay_code')->nullable();
            $table->string('barangay');
            $table->timestamps();

            $table->index(['province_code', 'municipality_code'], 'psgc_barangays_province_municipality_idx');
            $table->index(['province', 'municipality', 'barangay'], 'psgc_barangays_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psgc_barangays');
    }
};
