<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_service_area_barangays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_service_area_id')->constrained('hub_service_areas')->cascadeOnDelete();
            $table->string('barangay');
            $table->foreignId('rider_id')->nullable()->constrained('courier_profiles')->nullOnDelete();
            $table->timestamps();

            $table->unique(['hub_service_area_id', 'barangay']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_service_area_barangays');
    }
};
