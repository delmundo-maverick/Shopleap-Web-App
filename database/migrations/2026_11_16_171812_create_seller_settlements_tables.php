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
        Schema::create('seller_settlement_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('hub_id')->constrained('logistics_profiles')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->decimal('seller_amount', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->timestamps();
            $table->index(['hub_id', 'seller_id']);
        });
        Schema::create('seller_settlements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_no', 36)->unique();
            $table->string('request_key', 100)->unique();
            $table->foreignId('hub_id')->constrained('logistics_profiles')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->string('method_type', 20);
            $table->string('account_name', 150);
            $table->char('account_number_masked', 4);
            $table->string('transfer_reference', 150)->nullable()->unique();
            $table->string('proof_path')->nullable();
            $table->timestamp('due_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('disputed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['hub_id', 'status']);
            $table->index(['seller_id', 'status']);
        });
        Schema::create('seller_settlement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('settlement_id')->constrained('seller_settlements')->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('active_order_id')->nullable()->unique()->constrained('orders')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unique(['settlement_id', 'order_id']);
        });
        Schema::create('seller_settlement_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('settlement_id')->nullable()->constrained('seller_settlements')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 40);
            $table->text('note')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('transfer_reference', 150)->nullable()->unique();
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamps();
        });
        Schema::table('refund_payouts', function (Blueprint $table): void {
            $table->string('transfer_reference', 150)->nullable()->unique();
            $table->string('proof_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('Settlement history must be preserved. Use a forward migration.');
    }
};
