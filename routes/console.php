<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep lifecycle reconciliation frequent enough to prevent stale premium access windows.
Schedule::command('subscriptions:expire')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('subscriptions:process-renewals')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
