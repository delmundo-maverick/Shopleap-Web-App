<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Personal info
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_initial', 5)->nullable();
            $table->enum('sex', ['male', 'female']);
            $table->string('contact_no', 20);
            $table->date('birthday');
            $table->unsignedTinyInteger('age');

            // Address
            $table->string('province');
            $table->string('municipality');
            $table->string('barangay');
            $table->string('street_address'); // house no., street, etc.

            // Business info
            $table->string('business_name');
            $table->string('line_of_business');

            // Uploads (stored paths)
            $table->string('id_upload_path');
            $table->string('business_permit_path');

            // Approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
