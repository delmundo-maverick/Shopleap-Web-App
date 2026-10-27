<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_purchase_amount', 10, 2)->default(0);
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->unsignedInteger('total_quantity')->nullable();
            $table->unsignedInteger('used_quantity')->default(0);
            $table->unsignedInteger('per_buyer_limit')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('draft'); // draft, scheduled, active, expired, exhausted, disabled
            $table->boolean('is_active')->default(false);
            $table->boolean('combine_with_promotions')->default(false);
            $table->json('eligible_product_ids')->nullable();
            $table->json('eligible_category_ids')->nullable();
            $table->unsignedInteger('min_quantity')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['is_active', 'starts_at', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_vouchers');
    }
};
