<?php

namespace Tests\Feature\Admin;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionRenewalWarningConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_plans_table_supports_renewal_warning_days_column(): void
    {
        $this->assertTrue(Schema::hasColumn('subscription_plans', 'renewal_warning_days'));
    }

    public function test_service_uses_plan_level_warning_days_with_safe_default_fallback(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Renewal Window Plan',
            'slug' => 'renewal-window-plan-' . uniqid(),
            'description' => 'Plan-level renewal warning configuration',
            'price' => 199,
            'features' => [],
            'renewal_warning_days' => null,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $subscription = $this->createSubscriptionForPlan($plan);

        $service = app(SubscriptionService::class);

        $this->assertSame(
            (int) config('billing.subscription.renewal_warning_days', 7),
            $service->getRenewalWarningDays($subscription)
        );

        $plan->update(['renewal_warning_days' => 14]);

        $this->assertSame(14, $service->getRenewalWarningDays($subscription->fresh()));
    }

    private function createSubscriptionForPlan(SubscriptionPlan $plan): Subscription
    {
        $user = User::factory()->create();

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(10),
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(10),
            'price_paid' => 199,
            'auto_renew' => true,
        ]);
    }
}
