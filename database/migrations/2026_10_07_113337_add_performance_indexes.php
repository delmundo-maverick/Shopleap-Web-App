<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('seller_id');
            $table->index('buyer_id');
            $table->index('created_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('seller_id');
            $table->index('status');
            $table->index('category');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['seller_id']);
            $table->dropIndex(['buyer_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['seller_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['category']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
        });
    }
};
