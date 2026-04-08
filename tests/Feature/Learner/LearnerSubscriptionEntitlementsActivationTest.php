<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PayMongoPaymentLinkService;
use App\Services\SubscriptionService;
use App\Support\SubscriptionFeatureKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class LearnerSubscriptionEntitlementsActivationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_subscription_simulate_success_sets_premium_and_grants_plan_entitlements(): void
    {
        Queue::fake();

        [$learner, $subscription, $payment] = $this->createPendingLearnerSubscriptionWithEntitlements();

        $this->actingAs($learner)
            ->get(route('payment.simulate-success', $payment))
            ->assertRedirect(route('subscription.index'));

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscription->id,
            'status' => 'active',
            'plan_id' => $subscription->plan_id,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);

        $freshLearner = $learner->fresh();
        $this->assertTrue($freshLearner->isPremium());

        $subscriptionService = app(SubscriptionService::class);

        $this->assertTrue($subscriptionService->hasFeature($freshLearner, SubscriptionFeatureKeys::UNLIMITED_USERNAME_CHANGE));
        $this->assertTrue($subscriptionService->hasFeature($freshLearner, SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS));
        $this->assertTrue($subscriptionService->hasFeature($freshLearner, SubscriptionFeatureKeys::DOWNLOADABLE_CERTIFICATES));
    }

    public function test_subscription_success_page_recovers_missed_callback_and_activates_entitlements(): void
    {
        Queue::fake();

        [$learner, $subscription, $payment] = $this->createPendingLearnerSubscriptionWithEntitlements();

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'paymongo_checkout_session_id' => 'cs_sub_entitlement_activation_001',
                'payment_scope' => 'subscription',
            ]),
        ]);

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_sub_entitlement_activation_001',
                        'attributes' => [
                            'status' => 'completed',
                            'payments' => [
                                ['id' => 'pay_sub_entitlement_activation_001'],
                            ],
                        ],
                    ],
                ]);

            $mock->shouldReceive('getActualPaymentIdFromCheckoutSession')
                ->once()
                ->andReturn('pay_sub_entitlement_activation_001');
        });

        $this->actingAs($learner)
            ->get(route('payment.success', [
                'scope' => 'subscription',
                'payment_id' => $payment->id,
            ]))
            ->assertRedirect(route('subscription.index'));

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscription->id,
            'status' => 'active',
            'plan_id' => $subscription->plan_id,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);

        $freshLearner = $learner->fresh();
        $this->assertTrue($freshLearner->isPremium());

        $subscriptionService = app(SubscriptionService::class);

        $this->assertTrue($subscriptionService->hasFeature($freshLearner, SubscriptionFeatureKeys::UNLIMITED_USERNAME_CHANGE));
        $this->assertTrue($subscriptionService->hasFeature($freshLearner, SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS));
        $this->assertTrue($subscriptionService->hasFeature($freshLearner, SubscriptionFeatureKeys::DOWNLOADABLE_CERTIFICATES));
    }

    public function test_subscription_index_does_not_keep_expired_active_subscription_as_premium(): void
    {
        [$learner, $subscription] = $this->createExpiredActiveSubscription();

        $this->actingAs($learner)
            ->get(route('subscription.index'))
            ->assertOk();

        $subscription->refresh();

        $this->assertSame('expired', $subscription->status->value);
        $this->assertFalse($learner->fresh()->isPremium());
    }

    /**
     * @return array{0:User,1:Subscription,2:\App\Models\Payment}
     */
    private function createPendingLearnerSubscriptionWithEntitlements(): array
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Learner Entitlement Plan',
            'slug' => 'learner-entitlement-plan-' . uniqid(),
            'description' => 'Test plan for entitlement activation.',
            'price' => 199,
            'features' => [],
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PlanPrice::query()->create([
            'plan_id' => $plan->id,
            'duration_mode' => 'preset',
            'duration_unit' => 'month',
            'duration_count' => 1,
            'duration_label' => 'Monthly',
            'amount_minor' => 19900,
            'currency' => 'PHP',
            'compare_at_minor' => null,
            'is_default' => true,
            'is_active' => true,
        ]);

        $usernameFeature = FeatureCatalog::query()->firstOrCreate(
            ['key' => SubscriptionFeatureKeys::UNLIMITED_USERNAME_CHANGE],
            [
                'name' => 'Unlimited Username Changes',
                'value_type' => 'boolean',
                'category' => 'learner',
                'is_active' => true,
            ]
        );

        $shieldFeature = FeatureCatalog::query()->firstOrCreate(
            ['key' => SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS],
            [
                'name' => 'Unlimited Quiz Shields',
                'value_type' => 'boolean',
                'category' => 'learner',
                'is_active' => true,
            ]
        );

        $certificateFeature = FeatureCatalog::query()->firstOrCreate(
            ['key' => SubscriptionFeatureKeys::DOWNLOADABLE_CERTIFICATES],
            [
                'name' => 'Downloadable Certificates',
                'value_type' => 'boolean',
                'category' => 'learner',
                'is_active' => true,
            ]
        );

        foreach ([$usernameFeature, $shieldFeature, $certificateFeature] as $feature) {
            PlanFeatureEntitlement::query()->create([
                'plan_id' => $plan->id,
                'feature_id' => $feature->id,
                'is_enabled' => true,
                'is_unlimited' => true,
                'quota_value' => null,
            ]);
        }

        $subscription = app(SubscriptionService::class)->create($learner, $plan);
        $payment = $subscription->payments()->latest('id')->firstOrFail();

        return [$learner, $subscription, $payment];
    }

    /**
     * @return array{0:User,1:Subscription}
     */
    private function createExpiredActiveSubscription(): array
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $plan = SubscriptionPlan::query()->create([
            'name' => 'Stale Active Plan',
            'slug' => 'stale-active-plan-' . uniqid(),
            'description' => 'Expired active test plan.',
            'price' => 199,
            'features' => [],
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
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

        return [$learner, $subscription];
    }
}
