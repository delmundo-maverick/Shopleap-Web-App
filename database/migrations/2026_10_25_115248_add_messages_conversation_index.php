<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chat polling reads a thread by conversation and id. Postgres doesn't index foreign keys.
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'id'], 'messages_conversation_id_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conversation_id_id_idx');
        });
    }
};
