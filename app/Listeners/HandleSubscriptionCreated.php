<?php

namespace App\Listeners;

use App\Events\SubscriptionCreated;
use App\Jobs\SendSubscriptionWelcomeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleSubscriptionCreated implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(SubscriptionCreated $event): void
    {
        SendSubscriptionWelcomeEmail::dispatch($event->subscription)->onQueue('emails');
    }
}
