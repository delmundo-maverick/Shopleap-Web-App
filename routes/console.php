<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Automatically complete delivered orders.
Schedule::command('orders:auto-complete')->dailyAt('02:00');

// Automatically approve eligible pending cancellation requests after 24 hours.
Schedule::command('orders:auto-approve-cancellations')->hourly();

// Process return/refund deadlines and eligible automatic actions.
Schedule::command('returns:process-deadlines')->hourly();

// Close stale sorting sessions after 12 hours of inactivity.
Schedule::command('sorting:close-stale-sessions')->hourly();

// Flag bags that stay in transit too long and parcels with no tracking updates.
Schedule::command('bags:flag-stale-in-transit')->hourly();
Schedule::command('parcels:flag-stale-events')->hourly();
