<?php

namespace Tests\Unit\Services;

use App\Services\SubscriptionDunningService;
use App\Services\SubscriptionService;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLifecycleStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_to_scheduled_cancel_transition(): void
    {
        $subscription = $this->createSubscription('active');

        app(SubscriptionService::class)->scheduleCancelAtPeriodEnd($subscription);

        $subscription->refresh();

        $this->assertSame('scheduled_cancel', $subscription->status->value);
        $this->assertNotNull($subscription->cancel_at);
    }

    public function test_active_to_grace_period_transition(): void
    {
        $subscription = $this->createSubscription('active');

        app(SubscriptionService::class)->moveToGracePeriod($subscription, now()->addDays(7));

        $subscription->refresh();

        $this->assertSame('grace_period', $subscription->status->value);
        $this->assertNotNull($subscription->grace_ends_at);
    }

    public function test_grace_period_to_expired_transition(): void
    {
        $subscription = $this->createSubscription('grace_period');
        $subscription->update(['grace_ends_at' => now()->subMinute()]);

        app(SubscriptionDunningService::class)->expireGracePeriodSubscription($subscription);

        $subscription->refresh();

        $this->assertSame('expired', $subscription->status->value);
    }

    public function test_grace_period_to_active_recovery_transition(): void
    {
        $subscription = $this->createSubscription('grace_period');

        app(SubscriptionService::class)->recoverFromGracePeriod($subscription);

        $subscription->refresh();

        $this->assertSame('active', $subscription->status->value);
        $this->assertNull($subscription->grace_ends_at);
    }

    private function createSubscription(string $status): Subscription
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Premium Learner',
            'slug' => 'premium-learner',
            'description' => 'Premium access',
            'price' => 199,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => $status,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price_paid' => 199,
            'auto_renew' => true,
            'grace_ends_at' => $status === 'grace_period' ? now()->addDays(7) : null,
        ]);
    }
}
