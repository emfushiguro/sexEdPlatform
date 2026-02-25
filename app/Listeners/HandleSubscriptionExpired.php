<?php

namespace App\Listeners;

use App\Events\SubscriptionExpired;
use App\Jobs\SendSubscriptionExpiredEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleSubscriptionExpired implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(SubscriptionExpired $event): void
    {
        SendSubscriptionExpiredEmail::dispatch($event->subscription)->onQueue('emails');
    }
}
