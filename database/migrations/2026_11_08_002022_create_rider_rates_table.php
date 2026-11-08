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
        Schema::create('rider_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_profile_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamp('effective_from');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['logistics_profile_id', 'effective_from', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_rates');
    }
};
