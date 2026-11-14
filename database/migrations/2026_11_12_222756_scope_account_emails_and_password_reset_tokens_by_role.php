<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('users')->selectRaw('LOWER(TRIM(email)) AS email, role, COUNT(*) AS accounts')
            ->groupByRaw('LOWER(TRIM(email)), role')->havingRaw('COUNT(*) > 1')->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('Migration stopped: duplicate normalized (email, role) pairs exist: '.$duplicates->toJson().'. Resolve these accounts manually before retrying. No accounts were changed.');
        }

        $unscopedTokens = DB::select('SELECT tokens.email FROM password_reset_tokens AS tokens LEFT JOIN users ON LOWER(TRIM(users.email)) = LOWER(TRIM(tokens.email)) GROUP BY tokens.email HAVING COUNT(users.id) <> 1');
        if ($unscopedTokens !== []) {
            throw new RuntimeException('Migration stopped: legacy password reset tokens cannot be assigned to exactly one account. Expire these tokens before retrying. No accounts were changed.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
            $table->unique(['email', 'role']);
        });

        // Preserve stored emails while also protecting normalized account identity.
        DB::statement('CREATE UNIQUE INDEX users_normalized_email_role_unique ON users (LOWER(TRIM(email)), role)');

        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->string('role', 20)->nullable();
        });
        DB::statement('UPDATE password_reset_tokens SET role = users.role FROM users WHERE LOWER(TRIM(password_reset_tokens.email)) = LOWER(TRIM(users.email))');
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->dropPrimary();
            $table->string('role', 20)->nullable(false)->change();
            $table->primary(['email', 'role']);
        });
    }

    public function down(): void
    {
        if (DB::table('users')->select('email')->groupBy('email')->havingRaw('COUNT(*) > 1')->exists()
            || DB::table('password_reset_tokens')->select('email')->groupBy('email')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Rollback stopped: an email is used in multiple roles. Global email uniqueness cannot be restored without deleting independent accounts or tokens.');
        }

        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->dropPrimary();
            $table->dropColumn('role');
            $table->primary('email');
        });
        DB::statement('DROP INDEX users_normalized_email_role_unique');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['email', 'role']);
            $table->unique('email');
        });
    }
};
