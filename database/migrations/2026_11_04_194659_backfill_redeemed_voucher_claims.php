<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shop_voucher_redemptions')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('voucher_claims')->insertOrIgnore([
                    'user_id' => $row->buyer_id, 'voucher_id' => $row->shop_voucher_id,
                    'claimed_at' => $row->created_at, 'created_at' => now(), 'updated_at' => now(),
                ]);
                if (in_array($row->status, ['applied', 'used'], true)) {
                    DB::table('voucher_claims')->where('user_id', $row->buyer_id)->where('voucher_id', $row->shop_voucher_id)
                        ->update(['used_at' => $row->used_at ?? $row->created_at, 'order_id' => $row->order_id]);
                }
            }
        });
    }

    public function down(): void
    {
        // Preserve historical and subsequently claimed vouchers; use a forward migration to change backfill data.
    }
};
