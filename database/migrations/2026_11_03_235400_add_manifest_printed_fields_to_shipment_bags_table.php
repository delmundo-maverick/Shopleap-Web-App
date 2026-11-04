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
        Schema::table('shipment_bags', function (Blueprint $table) {
            $table->timestamp('manifest_printed_at')->nullable()->after('received_at');
            $table->foreignId('manifest_printed_by')->nullable()
                ->constrained('users')->nullOnDelete()->after('manifest_printed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_bags', function (Blueprint $table) {
            $table->dropForeign(['manifest_printed_by']);
            $table->dropColumn(['manifest_printed_at', 'manifest_printed_by']);
        });
    }
};
