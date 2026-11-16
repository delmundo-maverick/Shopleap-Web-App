<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin','buyer','seller','logistics','courier','logistics_company'))");

        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_code')->unique();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->text('address');
            $table->string('contact_phone', 20);
            $table->string('contact_email');
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('phone', 20)->nullable();
        });
        Schema::table('logistics_profiles', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('public_code')->nullable()->unique();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('manager_invite_token', 64)->nullable();
            $table->timestamp('manager_invite_expires_at')->nullable();
            $table->timestamp('manager_password_set_at')->nullable();
        });
        // Company verification replaces the standalone hub's personal documents.
        DB::statement('ALTER TABLE logistics_profiles ALTER COLUMN sex DROP NOT NULL, ALTER COLUMN birthday DROP NOT NULL, ALTER COLUMN age DROP NOT NULL, ALTER COLUMN id_upload_path DROP NOT NULL, ALTER COLUMN business_permit_path DROP NOT NULL');
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', 'logistics_company')->exists()
            || DB::table('logistics_profiles')->whereNotNull('company_id')->exists()) {
            throw new RuntimeException('Rollback stopped: company accounts or company hubs still exist. Preserve or migrate these accounts before rolling back.');
        }
        DB::statement('ALTER TABLE logistics_profiles ALTER COLUMN sex SET NOT NULL, ALTER COLUMN birthday SET NOT NULL, ALTER COLUMN age SET NOT NULL, ALTER COLUMN id_upload_path SET NOT NULL, ALTER COLUMN business_permit_path SET NOT NULL');
        Schema::table('logistics_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['public_code', 'latitude', 'longitude', 'manager_invite_token', 'manager_invite_expires_at', 'manager_password_set_at']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('phone');
        });
        Schema::dropIfExists('companies');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin','buyer','seller','logistics','courier'))");
    }
};
