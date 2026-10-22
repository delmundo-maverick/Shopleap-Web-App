<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 20);                 // refund_only | return_and_refund
            $table->string('reason', 30);               // key of OrderReturn::REASONS
            $table->text('details');
            $table->string('status', 20)->default('requested');

            // Snapshot of the money at request time
            $table->decimal('items_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->decimal('commission_reversal', 10, 2)->default(0);

            // Where Shopleap sends the refund (COD: nothing to reverse)
            $table->string('refund_method', 20);        // gcash | maya | bank
            $table->string('refund_account_name', 150);
            $table->string('refund_account_number', 60);

            // Seller response
            $table->timestamp('response_due_at')->nullable();
            $table->string('seller_response', 255)->nullable();
            $table->timestamp('seller_responded_at')->nullable();
            $table->timestamp('escalate_until')->nullable();

            // Returning the item
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('ship_by_at')->nullable();
            $table->string('return_courier', 100)->nullable();
            $table->string('return_tracking_no', 100)->nullable();
            $table->timestamp('return_shipped_at')->nullable();
            $table->timestamp('receipt_due_at')->nullable();
            $table->timestamp('return_received_at')->nullable();

            // Dispute (handled by an admin in the next step)
            $table->string('escalated_by', 10)->nullable();   // buyer | seller | system
            $table->string('escalation_reason', 500)->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            // Refund payout
            $table->string('refund_reference', 100)->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'response_due_at']);
            $table->index(['seller_id', 'status']);
            $table->index(['buyer_id', 'status']);
        });

        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained('order_returns')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['order_return_id', 'order_item_id']);
        });

        Schema::create('order_return_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained('order_returns')->cascadeOnDelete();
            $table->string('path');
            $table->string('type', 10);                 // image | video
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('order_return_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained('order_returns')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_events');
        Schema::dropIfExists('order_return_media');
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
    }
};
