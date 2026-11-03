<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\ParcelEvent;
use App\Services\ParcelLifecycle;
use Illuminate\Console\Command;

class AutoCompleteDeliveredOrders extends Command
{
    protected $signature = 'orders:auto-complete';

    protected $description = 'Complete delivered orders the buyer never confirmed';

    public function handle(ParcelLifecycle $lifecycle): int
    {
        $days = Order::AUTO_COMPLETE_DAYS;
        $completed = 0;

        Order::where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', now()->subDays($days))
            ->whereDoesntHave('returns', fn ($q) => $q->whereIn('status', OrderReturn::OPEN_STATUSES))
            ->chunkById(100, function ($orders) use ($days, $lifecycle, &$completed) {
                foreach ($orders as $order) {
                    $moved = $lifecycle->transition(
                        $order,
                        Order::STATUS_DELIVERED,
                        Order::STATUS_COMPLETED,
                        [],
                        "Automatically completed {$days} days after delivery.",
                        eventType: ParcelEvent::TYPE_COMPLETED,
                    );

                    if ($moved) {
                        $completed++;
                    }
                }
            });

        $this->info("Auto-completed {$completed} order(s).");

        return self::SUCCESS;
    }
}
