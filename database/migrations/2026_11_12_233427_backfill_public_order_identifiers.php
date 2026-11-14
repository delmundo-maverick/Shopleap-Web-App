<?php

use App\Support\OrderCodes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where(fn ($query) => $query->whereNull('order_number')->orWhereNull('waybill_code'))
            ->orderBy('id')->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    for ($attempt = 0; $attempt < 10; $attempt++) {
                        try {
                            DB::transaction(function () use ($order): void {
                                $current = DB::table('orders')->where('id', $order->id)->lockForUpdate()->first();
                                DB::table('orders')->where('id', $order->id)->update([
                                    'order_number' => $current->order_number ?? OrderCodes::generate('SL', 8),
                                    'waybill_code' => $current->waybill_code ?? ($current->waybill_printed_at
                                        ? str_pad((string) $current->id, 10, '0', STR_PAD_LEFT)
                                        : OrderCodes::generate('WB', 10)),
                                ]);
                            });

                            continue 2;
                        } catch (UniqueConstraintViolationException $exception) {
                            if ($attempt === 9) {
                                throw $exception;
                            }
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        // Allocated references must survive rollback because labels may be printed.
    }
};
