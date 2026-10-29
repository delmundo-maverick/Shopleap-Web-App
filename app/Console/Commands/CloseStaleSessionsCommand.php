<?php

namespace App\Console\Commands;

use App\Models\SortingSession;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;

class CloseStaleSessionsCommand extends Command
{
    protected $signature = 'sorting:close-stale-sessions {--hours=12 : Hours of inactivity before auto-closing}';

    protected $description = 'Automatically close sorting sessions that have been inactive beyond the threshold';

    public function handle()
    {
        $hours = (int) $this->option('hours');
        $threshold = now()->subHours($hours);

        $staleSessions = SortingSession::active()
            ->where('started_at', '<=', $threshold)
            ->get();

        if ($staleSessions->isEmpty()) {
            $this->info('No stale sessions found.');

            return 0;
        }

        foreach ($staleSessions as $session) {
            $endedAt = now();
            $duration = (int) floor($session->started_at->diffInSeconds($endedAt));

            $session->update([
                'ended_at' => $endedAt,
                'duration_seconds' => $duration,
                'auto_closed' => true,
            ]);

            ActivityLogger::log(
                'sorting_session_auto_closed',
                'sorting_session',
                "Sorting session #{$session->id} auto-closed after {$hours} hours of inactivity.",
                $session
            );
        }

        $this->info("Auto-closed {$staleSessions->count()} stale session(s).");

        return 0;
    }
}
