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
        Schema::create('sorting_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sorting_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('scanned_code');
            $table->enum('result', ['sorted', 'duplicate', 'rejected', 'needs_review']);
            $table->string('message');
            $table->string('destination_municipality')->nullable();
            $table->string('destination_province')->nullable();
            $table->string('destination_barangay')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['sorting_session_id', 'result']);
            // Note: No unique constraint - duplicate scans create separate rows for audit trail
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sorting_scans');
    }
};
