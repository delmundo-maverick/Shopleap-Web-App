<?php

namespace App\Console\Commands;

use App\Models\ShipmentBag;
use App\Services\Notifier;
use Illuminate\Console\Command;

class FlagStaleBagsCommand extends Command
{
    protected $signature = 'bags:flag-stale-in-transit {--hours= : Hours in transit before a bag is flagged}';

    protected $description = 'Flag bags that have been in transit longer than expected and notify both hubs';

    public function handle(Notifier $notifier): int
    {
        $hours = (int) ($this->option('hours') ?? config('logistics.stale_bag_hours', 48));
        $threshold = now()->subHours($hours);
        $flagged = 0;

        ShipmentBag::with(['fromHub.user', 'toHub.user'])
            ->where('status', ShipmentBag::STATUS_IN_TRANSIT)
            ->whereNotNull('dispatched_at')
            ->where('dispatched_at', '<=', $threshold)
            ->whereNull('flagged_at')
            ->chunkById(100, function ($bags) use ($hours, $notifier, &$flagged) {
                foreach ($bags as $bag) {
                    $bag->update([
                        'flagged_at' => now(),
                        'flag_note' => "In transit for more than {$hours} hours.",
                    ]);

                    $notifier->staleBag($bag);
                    $flagged++;
                }
            });

        $this->info("Flagged {$flagged} stale bag(s).");

        return self::SUCCESS;
    }
}
