<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('logistics_profile_id')->constrained('logistics_profiles')->cascadeOnDelete();

            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_initial', 5)->nullable();
            $table->enum('sex', ['male', 'female']);
            $table->string('contact_no', 20);
            $table->date('birthday');
            $table->unsignedTinyInteger('age');

            $table->string('province');
            $table->string('municipality');
            $table->string('barangay');
            $table->string('street_address');

            $table->string('vehicle_type');
            $table->string('plate_number');
            $table->string('or_cr_upload_path');
            $table->string('license_upload_path');

            $table->string('delivery_area')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_profiles');
    }
};
