<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SubscriptionSchedulerRegistrationTest extends TestCase
{
    public function test_subscription_lifecycle_commands_are_scheduled(): void
    {
        $scheduledCommands = collect(app(Schedule::class)->events())
            ->map(fn ($event) => (string) ($event->command ?? ''));

        $this->assertTrue(
            $scheduledCommands->contains(fn (string $command): bool => str_contains($command, 'subscriptions:expire')),
            'Expected subscriptions:expire to be registered in scheduler.'
        );

        $this->assertTrue(
            $scheduledCommands->contains(fn (string $command): bool => str_contains($command, 'subscriptions:process-renewals')),
            'Expected subscriptions:process-renewals to be registered in scheduler.'
        );
    }
}
