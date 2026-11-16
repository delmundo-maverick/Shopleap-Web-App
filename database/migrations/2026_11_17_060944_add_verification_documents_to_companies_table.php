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
            $table->string('id_upload_path')->nullable();
            $table->string('business_permit_path')->nullable();
            $table->string('sec_certificate_path')->nullable();
            $table->string('bir_registration_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['id_upload_path', 'business_permit_path', 'sec_certificate_path', 'bir_registration_path']);
        });
    }
};
