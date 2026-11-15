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
        Schema::create('delivery_proofs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('courier_id')->index()->constrained('courier_profiles')->restrictOnDelete();
            $table->foreignId('logistics_profile_id')->index()->constrained()->restrictOnDelete();
            $table->string('image_path');
            $table->string('thumbnail_path');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size_bytes');
            $table->timestampTz('captured_at')->nullable();
            $table->timestampTz('uploaded_at')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestampTz('expired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_proofs');
    }
};
