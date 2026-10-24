<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index(['user_id', 'created_at']);
        });

        // Supabase exposes public tables through its REST API. Enabling RLS
        // with no policies keeps the anon key out; Laravel's DB role bypasses it.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE wishlist_items ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
