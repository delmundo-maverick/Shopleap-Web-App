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
        Schema::create('rider_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hub_id')->constrained('logistics_profiles')->restrictOnDelete();
            $table->foreignId('rider_id')->constrained('courier_profiles')->restrictOnDelete();
            $table->string('reference_no', 36)->unique();
            $table->string('request_key', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('paid');
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['hub_id', 'paid_at']);
            $table->index(['rider_id', 'paid_at']);
        });
        Schema::create('rider_payout_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rider_payout_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
        });
        Schema::create('seller_payout_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 20);
            $table->string('account_name', 150);
            $table->char('account_number_masked', 4);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['seller_id', 'is_default']);
        });
        Schema::create('seller_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('active_seller_id')->nullable()->unique()->constrained('users')->restrictOnDelete();
            $table->string('reference_no', 36)->unique();
            $table->string('request_key', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('method_type', 20);
            $table->string('account_name', 150);
            $table->char('account_number_masked', 4);
            $table->string('status', 20)->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('processing_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['seller_id', 'requested_at']);
            $table->index(['status', 'requested_at']);
        });
        Schema::create('refund_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_return_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->string('reference_no', 36)->unique();
            $table->timestamp('requested_at');
            $table->foreignId('paid_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'requested_at']);
            $table->index(['order_id', 'buyer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['refund_payouts', 'seller_payouts', 'seller_payout_methods', 'rider_payout_items', 'rider_payouts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
