<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // The cancellation itself
            $table->string('cancelled_by', 20)->nullable();        // buyer | seller | system
            $table->string('cancel_reason', 100)->nullable();
            $table->string('cancel_note', 255)->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // The buyer's request (CONFIRMED / PREPARING / READY_FOR_PICKUP)
            $table->string('cancel_request_status', 20)->nullable(); // pending | approved | declined | withdrawn | expired
            $table->string('cancel_request_reason', 100)->nullable();
            $table->string('cancel_request_note', 255)->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->string('cancel_request_response', 255)->nullable(); // the seller's reason for declining
            $table->timestamp('cancel_request_resolved_at')->nullable();

            $table->index(['cancel_request_status', 'cancel_requested_at']);
        });

        // Orders cancelled before this feature were all cancelled by the seller.
        DB::table('orders')
            ->where('status', 'CANCELLED')
            ->whereNull('cancelled_at')
            ->update(['cancelled_by' => 'seller', 'cancelled_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['cancel_request_status', 'cancel_requested_at']);
            $table->dropColumn([
                'cancelled_by',
                'cancel_reason',
                'cancel_note',
                'cancelled_at',
                'cancel_request_status',
                'cancel_request_reason',
                'cancel_request_note',
                'cancel_requested_at',
                'cancel_request_response',
                'cancel_request_resolved_at',
            ]);
        });
    }
};
