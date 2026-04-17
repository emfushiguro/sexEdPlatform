<?php

namespace Tests\Feature\Learner;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\EntitlementService;
use App\Support\SubscriptionFeatureKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerSubscriptionExpiryEntitlementFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_active_subscription_drops_entitlements_to_free_baseline(): void
    {
        [$user, $subscription] = $this->createExpiredActiveSubscriptionWithEntitlement();

        $this->assertFalse($user->fresh()->isPremium());
        $this->assertFalse(
            app(EntitlementService::class)->canAccessFeature($user, SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS)
        );

        $summary = app(EntitlementService::class)->getSubscriptionSummary($user);

        $this->assertFalse((bool) $summary['has_subscription']);
        $this->assertNull($summary['status']);

        $subscription->refresh();
        $this->assertSame('expired', $subscription->status->value);
    }

    public function test_subscription_index_reconciles_stale_active_records_before_rendering(): void
    {
        [$user, $subscription] = $this->createExpiredActiveSubscriptionWithEntitlement();

        $this->actingAs($user)
            ->get(route('subscription.index'))
            ->assertOk();

        $subscription->refresh();

        $this->assertSame('expired', $subscription->status->value);
        $this->assertFalse($user->fresh()->isPremium());
    }

    /**
     * @return array{0: User, 1: Subscription}
     */
    private function createExpiredActiveSubscriptionWithEntitlement(): array
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Learner Premium',
            'slug' => 'learner-premium-' . uniqid(),
            'description' => 'Premium learner plan',
            'price' => 199,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $feature = FeatureCatalog::query()->updateOrCreate(
            ['key' => SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS],
            [
                'name' => 'Unlimited Quiz Shields',
                'value_type' => 'boolean',
                'category' => 'learner',
                'is_active' => true,
            ]
        );

        PlanFeatureEntitlement::create([
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
            'is_enabled' => true,
            'is_unlimited' => true,
        ]);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->subMinute(),
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subMinute(),
            'price_paid' => 199,
            'auto_renew' => true,
        ]);

        return [$user, $subscription];
    }
}
