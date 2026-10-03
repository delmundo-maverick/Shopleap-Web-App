<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();    // snapshot, survives the user being deleted
            $table->string('actor_role')->nullable();
            $table->string('action');                     // e.g. "seller.approved", "order.cancelled"
            $table->string('category');                    // e.g. "User Management", "Orders", "Logistics"
            $table->text('description');
            $table->string('subject_type')->nullable();   // e.g. "SellerProfile"
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['category', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
