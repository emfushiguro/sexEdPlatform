<?php

namespace Tests\Feature\Console;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ExpireSubscriptionsNormalizedDatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_expires_active_subscription_using_normalized_ends_at_when_legacy_end_date_is_null(): void
    {
        $subscription = $this->createSubscription([
            'status' => 'active',
            'end_date' => null,
            'ends_at' => now()->subMinute(),
        ]);

        Artisan::call('subscriptions:expire');

        $subscription->refresh();

        $this->assertSame('expired', $subscription->status->value);
    }

    public function test_command_keeps_active_subscription_when_ends_at_is_in_future(): void
    {
        $subscription = $this->createSubscription([
            'status' => 'active',
            'end_date' => null,
            'ends_at' => now()->addDay(),
        ]);

        Artisan::call('subscriptions:expire');

        $subscription->refresh();

        $this->assertSame('active', $subscription->status->value);
    }

    private function createSubscription(array $attributes): Subscription
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Console Expiry Plan',
            'slug' => 'console-expiry-plan-' . uniqid(),
            'description' => 'Command expiry test plan',
            'price' => 199,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Subscription::create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->subDay(),
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
            'price_paid' => 199,
            'auto_renew' => true,
        ], $attributes));
    }
}
