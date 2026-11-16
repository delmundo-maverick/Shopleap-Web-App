<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('logistics_login_suspended_at')->nullable();
            $table->unsignedInteger('logistics_session_version')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['last_login_at', 'logistics_login_suspended_at', 'logistics_session_version']);
        });
    }
};
