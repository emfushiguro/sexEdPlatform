<?php

use App\Services\GuardianRelationshipCorrectionDraft;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('guardian-relationships:prune-correction-drafts', function (GuardianRelationshipCorrectionDraft $drafts) {
    $drafts->pruneExpired();
    $this->info('Expired relationship correction drafts removed.');
})->purpose('Remove expired private relationship correction drafts');

Schedule::command('guardian-relationships:prune-correction-drafts')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('seminars:send-notices')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('connectors:expire-verification-documents')
    ->dailyAt('01:15')
    ->withoutOverlapping();

// Keep lifecycle reconciliation frequent enough to prevent stale premium access windows.
Schedule::command('subscriptions:expire')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('subscriptions:process-renewals')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
