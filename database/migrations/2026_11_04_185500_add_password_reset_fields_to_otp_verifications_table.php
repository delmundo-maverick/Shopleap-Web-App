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
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('purpose')->default('registration')->index();
            $table->timestamp('used_at')->nullable();
            $table->string('reset_token_hash')->nullable();
            $table->timestamp('reset_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'used_at', 'reset_token_hash', 'reset_expires_at']);
        });
    }
};
