<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('seller_reply')->nullable()->after('comment');
            $table->timestamp('seller_replied_at')->nullable()->after('seller_reply');

            $table->index(['seller_id', 'seller_replied_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['seller_id', 'seller_replied_at']);
            $table->dropColumn(['seller_reply', 'seller_replied_at']);
        });
    }
};
