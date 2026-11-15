<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Services\LedgerService;
use App\Services\OrderLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOrderLedger extends Command
{
    protected $signature = 'ledger:backfill-orders {--dry-run : Print planned postings without writing anything}';

    protected $description = 'Post delivered orders with frozen snapshots to the ledger';

    public function handle(OrderLedger $ledger): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $skipped = $planned = 0;
        Order::whereNotNull('delivered_at')->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->chunkById(100, function ($orders) use ($ledger, $dryRun, &$skipped, &$planned): void {
                foreach ($orders as $candidate) {
                    DB::transaction(function () use ($candidate, $ledger, $dryRun, &$skipped, &$planned): void {
                        $order = Order::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                        $reason = $ledger->deliverySkipReason($order);
                        if ($reason !== null) {
                            $this->warn($order->order_number.': skipped — '.$reason);
                            $skipped++;

                            return;
                        }
                        $actions = [];
                        $hasDelivery = $ledger->hasDelivery($order->id);
                        if (! $hasDelivery) {
                            $actions[] = 'delivery (items '.$order->total_amount.', commission '.$order->commission_amount.', seller net '.LedgerService::decimal(LedgerService::cents($order->total_amount) - LedgerService::cents($order->commission_amount)).', shipping '.$order->delivered_shipping_fee.', cash '.$order->cod_collected_amount.', rider pay '.($order->rider_pay ?? 'unknown; omitted').')';
                        }
                        $pendingItems = $hasDelivery ? $ledger->pendingItems($order->id) : LedgerService::cents($order->total_amount);
                        if ($order->status === Order::STATUS_COMPLETED && $pendingItems > 0 && ! DB::table('ledger_transactions')->where('idempotency_key', 'order:'.$order->id.':completed')->exists()) {
                            $actions[] = 'release remaining pending funds';
                        }
                        $returns = $order->returns()->whereIn('status', [OrderReturn::STATUS_APPROVED, OrderReturn::STATUS_SHIPPED_BACK, OrderReturn::STATUS_REFUND_PENDING, OrderReturn::STATUS_REFUNDED])->orderBy('id')->get();
                        foreach ($returns as $return) {
                            if (LedgerService::cents($return->items_amount) > 0 && ! DB::table('ledger_transactions')->where('idempotency_key', 'return:'.$return->id.':approved')->exists()) {
                                $actions[] = 'items refund '.$return->items_amount;
                            }
                        }
                        if ($actions === []) {
                            $this->line($order->order_number.': already posted');

                            return;
                        }
                        $this->line(($dryRun ? 'Would post ' : 'Posting ').$order->order_number.': '.implode('; ', $actions));
                        $planned++;
                        if ($dryRun) {
                            return;
                        }
                        $ledger->delivered($order);
                        if ($order->status === Order::STATUS_COMPLETED) {
                            $ledger->completed($order);
                        }
                        foreach ($returns as $return) {
                            $return->setRelation('order', $order);
                            $ledger->returnChanged($return);
                        }
                    });
                }
            });
        $this->info(($dryRun ? 'Dry run: ' : 'Finished: ').$planned.' order(s), '.$skipped.' skipped. No rates or order snapshots were changed.');

        return self::SUCCESS;
    }
}
