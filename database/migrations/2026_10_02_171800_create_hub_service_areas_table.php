<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_profile_id')->constrained('logistics_profiles')->cascadeOnDelete();
            $table->string('province');
            $table->string('municipality');
            $table->string('zone_name')->nullable();
            $table->foreignId('rider_id')->nullable()->constrained('courier_profiles')->nullOnDelete();
            $table->timestamps();

            $table->unique(['logistics_profile_id', 'province', 'municipality']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_service_areas');
    }
};
