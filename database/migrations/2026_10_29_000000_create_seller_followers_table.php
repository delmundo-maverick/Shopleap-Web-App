<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['buyer_id', 'seller_id']);
            $table->index(['seller_id', 'created_at']);
        });

        // Supabase exposes public tables through its REST API. Enabling RLS
        // with no policies keeps the anon key out; Laravel's DB role bypasses it.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE seller_followers ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_followers');
    }
};
