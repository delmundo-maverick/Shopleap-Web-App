<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_cash_handovers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hub_id')->constrained('logistics_profiles')->restrictOnDelete();
            $table->foreignId('rider_id')->constrained('courier_profiles')->restrictOnDelete();
            $table->string('reference_no', 36)->unique();
            $table->string('request_key', 100)->unique();
            foreach (['amount_expected', 'amount_received', 'short_amount', 'settled_amount'] as $column) {
                $table->decimal($column, 12, 2)->default(0);
            }
            $table->string('status', 20);
            $table->foreignId('confirmed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at');
            $table->text('notes')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->text('settlement_note')->nullable();
            $table->timestamps();
            $table->index(['hub_id', 'rider_id', 'status']);
            $table->index(['rider_id', 'confirmed_at']);
        });
        Schema::create('rider_cash_handover_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('handover_id')->constrained('rider_cash_handovers')->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unique(['handover_id', 'order_id']);
            $table->index('order_id');
        });
        Schema::create('remittances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hub_id')->constrained('logistics_profiles')->restrictOnDelete();
            $table->string('reference_no', 36)->unique();
            $table->string('request_key', 100)->unique();
            $table->decimal('total_amount', 12, 2);
            $table->string('status', 20)->default('submitted');
            $table->string('proof_path')->nullable();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->index(['hub_id', 'status', 'submitted_at']);
            $table->index(['status', 'submitted_at']);
        });
        Schema::create('remittance_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('remittance_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('active_order_id')->nullable()->unique()->constrained('orders')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unique(['remittance_id', 'order_id']);
        });

    }

    public function down(): void
    {
        foreach (['remittance_items', 'remittances', 'rider_cash_handover_items', 'rider_cash_handovers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
