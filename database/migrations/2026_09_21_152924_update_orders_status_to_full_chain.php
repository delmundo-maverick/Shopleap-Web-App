<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'PLACED'");

        DB::table('orders')->where('status', 'pending')->update(['status' => 'PLACED']);
        DB::table('orders')->where('status', 'to_ship')->update(['status' => 'CONFIRMED']);
        DB::table('orders')->where('status', 'shipped')->update(['status' => 'OUT_FOR_DELIVERY']);
        DB::table('orders')->where('status', 'delivered')->update(['status' => 'DELIVERED']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'CANCELLED']);

        DB::statement("
            ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN (
                'PLACED','CONFIRMED','PREPARING','READY_FOR_PICKUP','PICKED_UP',
                'AT_SORTING_CENTER','SORTED','ASSIGNED_TO_RIDER','OUT_FOR_DELIVERY',
                'DELIVERED','COMPLETED','CANCELLED','DELIVERY_FAILED','RETURNED'
            ))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");

        DB::table('orders')->whereIn('status', ['CONFIRMED', 'PREPARING', 'READY_FOR_PICKUP'])->update(['status' => 'to_ship']);
        DB::table('orders')->whereIn('status', ['PICKED_UP', 'AT_SORTING_CENTER', 'SORTED', 'ASSIGNED_TO_RIDER', 'OUT_FOR_DELIVERY'])->update(['status' => 'shipped']);
        DB::table('orders')->whereIn('status', ['DELIVERED', 'COMPLETED'])->update(['status' => 'delivered']);
        DB::table('orders')->whereIn('status', ['CANCELLED', 'DELIVERY_FAILED', 'RETURNED'])->update(['status' => 'cancelled']);
        DB::table('orders')->where('status', 'PLACED')->update(['status' => 'pending']);

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('pending','to_ship','shipped','delivered','cancelled'))");
        DB::statement("ALTER TABLE orders ALTER COLUMN status SET DEFAULT 'pending'");
    }
};
