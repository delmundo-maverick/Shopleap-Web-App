<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('sku', 100)->nullable()->index();
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('requires_review')->default(false);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_status_check');
            DB::statement("ALTER TABLE products ADD CONSTRAINT products_status_check CHECK (status IN ('draft', 'active', 'archived', 'pending_review', 'rejected'))");
        } else {
            Schema::table('products', function (Blueprint $table): void {
                $table->enum('status', ['draft', 'active', 'archived', 'pending_review', 'rejected'])->default('active')->change();
            });
        }
    }

    public function down(): void
    {
        DB::table('products')->whereIn('status', ['pending_review', 'rejected'])->update(['status' => 'draft']);
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_status_check');
            DB::statement("ALTER TABLE products ADD CONSTRAINT products_status_check CHECK (status IN ('draft', 'active', 'archived'))");
        } else {
            Schema::table('products', function (Blueprint $table): void {
                $table->enum('status', ['draft', 'active', 'archived'])->default('active')->change();
            });
        }
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['sku']);
            $table->dropColumn(['sku', 'low_stock_threshold', 'rejection_reason', 'requires_review']);
        });
    }
};
