<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic module publishing
Schedule::command('modules:publish-scheduled')->everyMinute();

// Schedule subscription expiration check - runs every minute for short-duration test plans
Schedule::command('subscriptions:expire')->everyMinute();

// Schedule subscription renewals and dunning - runs hourly
Schedule::command('subscriptions:process-renewals')->hourly();

// Schedule analytics report - runs weekly on Mondays
Schedule::command('analytics:generate-report weekly')->weeklyOn(1, '8:00');
