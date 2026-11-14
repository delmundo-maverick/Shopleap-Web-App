<?php

namespace App\Console\Commands;

use App\Services\OrderReturns;
use Illuminate\Console\Command;

class ProcessReturnDeadlines extends Command
{
    protected $signature = 'returns:process-deadlines';

    protected $description = 'Auto-approve unanswered return requests, close or expire stale ones, and auto-confirm unconfirmed receipts';

    public function handle(OrderReturns $returns): int
    {
        $r = $returns->processDeadlines();

        $this->info("Auto-approved {$r['auto_approved']}, closed {$r['closed']}, expired {$r['expired']}, auto-confirmed {$r['auto_confirmed']}.");

        return self::SUCCESS;
    }
}
