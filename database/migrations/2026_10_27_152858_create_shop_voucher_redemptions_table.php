<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('order_subtotal', 10, 2)->default(0);
            $table->string('status')->default('applied'); // applied, used, cancelled, refunded
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['buyer_id', 'shop_voucher_id']);
            $table->index(['seller_id']);
            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_voucher_redemptions');
    }
};
