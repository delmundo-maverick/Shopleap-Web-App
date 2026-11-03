<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');

        DB::statement("
            ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN (
                'PLACED','CONFIRMED','PREPARING','READY_FOR_PICKUP','PICKED_UP',
                'AT_SORTING_CENTER','SORTED','AWAITING_TRANSFER','ASSIGNED_TO_RIDER',
                'OUT_FOR_DELIVERY','DELIVERED','COMPLETED','CANCELLED','DELIVERY_FAILED','RETURNED'
            ))
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::table('orders')
            ->where('status', 'AWAITING_TRANSFER')
            ->update(['status' => 'SORTED']);

        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');

        DB::statement("
            ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN (
                'PLACED','CONFIRMED','PREPARING','READY_FOR_PICKUP','PICKED_UP',
                'AT_SORTING_CENTER','SORTED','ASSIGNED_TO_RIDER','OUT_FOR_DELIVERY',
                'DELIVERED','COMPLETED','CANCELLED','DELIVERY_FAILED','RETURNED'
            ))
        ");
    }
};
