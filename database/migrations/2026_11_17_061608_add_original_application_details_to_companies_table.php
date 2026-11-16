<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('middle_initial', 5)->nullable();
            $table->enum('sex', ['male', 'female'])->nullable();
            $table->date('birthday')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('province')->nullable();
            $table->string('municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('street_address')->nullable();
            $table->json('service_regions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'middle_initial', 'sex', 'birthday', 'age', 'province', 'municipality', 'barangay', 'street_address', 'service_regions']);
        });
    }
};
