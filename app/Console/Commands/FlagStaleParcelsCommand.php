<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ParcelEvent;
use App\Services\Notifier;
use App\Services\ParcelLifecycle;
use Illuminate\Console\Command;

class FlagStaleParcelsCommand extends Command
{
    /** Statuses where a parcel is still expected to move. */
    const ACTIVE_STATUSES = [
        Order::STATUS_PLACED,
        Order::STATUS_CONFIRMED,
        Order::STATUS_PREPARING,
        Order::STATUS_READY_FOR_PICKUP,
        Order::STATUS_PICKED_UP,
        Order::STATUS_AT_SORTING_CENTER,
        Order::STATUS_SORTED,
        Order::STATUS_AWAITING_TRANSFER,
        Order::STATUS_ASSIGNED_TO_RIDER,
        Order::STATUS_OUT_FOR_DELIVERY,
        Order::STATUS_DELIVERY_FAILED,
    ];

    protected $signature = 'parcels:flag-stale-events {--hours= : Hours without a tracking event before a parcel is flagged}';

    protected $description = 'Flag parcels with no tracking update for too long and notify the hub holding them';

    public function handle(Notifier $notifier, ParcelLifecycle $lifecycle): int
    {
        $hours = (int) ($this->option('hours') ?? config('logistics.stale_parcel_hours', 24));
        $threshold = now()->subHours($hours);
        $flagged = 0;

        Order::with('currentHub.user')
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereDoesntHave('parcelEvents', fn ($query) => $query->where('created_at', '>', $threshold))
            ->where('updated_at', '<=', $threshold)
            ->chunkById(100, function ($orders) use ($hours, $notifier, $lifecycle, &$flagged) {
                foreach ($orders as $order) {
                    $lifecycle->record($order, ParcelEvent::TYPE_STALE, [
                        'hub_id' => $order->current_hub_id,
                        'note' => "No tracking update for more than {$hours} hours.",
                        'actor_user_id' => null,
                    ]);

                    if ($order->current_hub_id !== null) {
                        $notifier->staleParcel($order, $hours);
                    }

                    $flagged++;
                }
            });

        $this->info("Flagged {$flagged} stale parcel(s).");

        return self::SUCCESS;
    }
}
