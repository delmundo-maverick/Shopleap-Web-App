<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('platform_settings')->insertOrIgnore(['key' => 'minimum_payout', 'value' => '100.00', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('order_returns')->orderBy('id')->chunkById(100, function ($returns): void {
            foreach ($returns as $return) {
                DB::transaction(function () use ($return): void {
                    $return = DB::table('order_returns')->where('id', $return->id)->lockForUpdate()->first();
                    DB::table('order_returns')->where('id', $return->id)->update([
                        'refund_account_number' => substr(preg_replace('/\D/', '', (string) $return->refund_account_number), -4),
                    ]);
                    if (! in_array($return->status, ['refund_pending', 'refunded'], true)
                        || DB::table('refund_payouts')->where('order_return_id', $return->id)->exists()) {
                        return;
                    }
                    for ($attempt = 0; $attempt < 10; $attempt++) {
                        try {
                            DB::transaction(function () use ($return): void {
                                DB::table('refund_payouts')->insert([
                                    'order_return_id' => $return->id, 'order_id' => $return->order_id, 'buyer_id' => $return->buyer_id,
                                    'amount' => $return->refund_amount, 'status' => $return->status === 'refunded' ? 'paid' : 'pending',
                                    'reference_no' => 'RF-'.Str::upper(Str::random(10)), 'requested_at' => $return->updated_at,
                                    'paid_by' => $return->refunded_by, 'paid_at' => $return->refunded_at, 'created_at' => now(), 'updated_at' => now(),
                                ]);
                            });

                            return;
                        } catch (UniqueConstraintViolationException $exception) {
                            if ($attempt === 9) {
                                throw $exception;
                            }
                        }
                    }
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Masked account digits cannot be restored. Retain financial records and settings.
    }
};
