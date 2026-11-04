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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('terms_version')->nullable()->after('avatar_path');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_version');
            $table->unsignedInteger('privacy_version')->nullable()->after('terms_accepted_at');
            $table->timestamp('privacy_accepted_at')->nullable()->after('privacy_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'terms_version',
                'terms_accepted_at',
                'privacy_version',
                'privacy_accepted_at',
            ]);
        });
    }
};
