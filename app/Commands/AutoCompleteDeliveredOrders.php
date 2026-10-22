<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCompleteDeliveredOrders extends Command
{
    protected $signature = 'orders:auto-complete';

    protected $description = 'Complete delivered orders the buyer never confirmed';

    public function handle(): int
    {
        $days = Order::AUTO_COMPLETE_DAYS;
        $completed = 0;

        Order::where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', now()->subDays($days))
            ->whereDoesntHave('returns', fn($q) => $q->whereIn('status', OrderReturn::OPEN_STATUSES))
            ->chunkById(100, function ($orders) use ($days, &$completed) {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, $days, &$completed) {
                        $moved = Order::whereKey($order->id)
                            ->where('status', Order::STATUS_DELIVERED)
                            ->update(['status' => Order::STATUS_COMPLETED]) > 0;

                        if (! $moved) {
                            return;
                        }

                        $order->refresh();
                        $order->logStatusEvent("Automatically completed {$days} days after delivery.");
                        $completed++;
                    });
                }
            });

        $this->info("Auto-completed {$completed} order(s).");

        return self::SUCCESS;
    }
}
