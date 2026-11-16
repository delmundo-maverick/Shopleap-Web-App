<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->decimal('default_rider_rate', 10, 2)->nullable();
        });
        Schema::create('company_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('meta')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at');
            $table->index(['company_id', 'created_at', 'id']);
            $table->index(['company_id', 'action', 'created_at']);
        });
        Schema::create('company_announcements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_code')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'created_at', 'id']);
        });
        Schema::create('company_announcement_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('notification_id')->nullable()->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->unique(['company_announcement_id', 'user_id'], 'company_announcement_recipient_unique');
            $table->index(['user_id', 'dismissed_at', 'created_at'], 'company_announcement_banner_index');
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['earning_hub_id', 'delivered_at'], 'company_reports_delivery_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('company_reports_delivery_index');
        });
        Schema::dropIfExists('company_announcement_receipts');
        Schema::dropIfExists('company_announcements');
        Schema::dropIfExists('company_activity_logs');
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('default_rider_rate');
        });
    }
};
