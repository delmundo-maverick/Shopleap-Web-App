<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderCancellation;
use Illuminate\Console\Command;

class AutoApproveCancellationRequests extends Command
{
    protected $signature = 'orders:auto-approve-cancellations';

    protected $description = 'Approve buyer cancellation requests the seller did not answer in time';

    public function handle(OrderCancellation $cancellation): int
    {
        $hours = Order::CANCEL_REQUEST_AUTO_APPROVE_HOURS;
        $approved = 0;
        $expired = 0;

        Order::where('cancel_request_status', 'pending')
            ->where('cancel_requested_at', '<=', now()->subHours($hours))
            ->chunkById(100, function ($orders) use ($cancellation, $hours, &$approved, &$expired) {
                foreach ($orders as $order) {
                    $done = in_array($order->status, Order::BUYER_CANCEL_REQUEST_FROM, true)
                        && $cancellation->approveRequest(
                            $order,
                            null,
                            "Cancelled at the buyer's request. The seller did not respond within {$hours} hours. Reason: "
                                .($order->cancel_request_reason ?? 'Changed my mind').'.'
                        );

                    if ($done) {
                        $approved++;
                    } else {
                        // The courier already collected it, or it was cancelled another way.
                        $cancellation->expire($order);
                        $expired++;
                    }
                }
            });

        $this->info("Auto-approved {$approved} cancellation request(s); {$expired} expired.");

        return self::SUCCESS;
    }
}
