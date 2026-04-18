<?php

namespace Tests\Feature\Learner;

use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerSubscriptionRenewalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_subscription_is_renewable_via_subscription_endpoint(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 6, 9, 0, 0));

        try {
            [$user, $subscription] = $this->createSubscriptionWithPricing([
                'status' => 'expired',
                'end_date' => now()->subDay(),
                'ends_at' => now()->subDay(),
            ], renewalWarningDays: 10);

            $this->actingAs($user)
                ->post(route('subscription.renew'))
                ->assertRedirect(route('payment.create', ['subscription' => $subscription->id]));

            $subscription->refresh();
            $renewalPayment = $subscription->payments()->latest('id')->first();

            $this->assertSame('expired', $subscription->status->value);
            $this->assertTrue($subscription->ends_at->equalTo(now()->subDay()));
            $this->assertTrue((bool) $subscription->auto_renew);
            $this->assertNotNull($renewalPayment);
            $this->assertSame('pending', $renewalPayment->status->value);
            $this->assertSame(199.0, (float) $renewalPayment->amount);
            $this->assertSame('subscription', data_get($renewalPayment->payment_details, 'payment_scope'));
            $this->assertSame('renewal_checkout', data_get($renewalPayment->payment_details, 'lifecycle_action'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_expiring_soon_subscription_uses_extension_anchor_from_current_expiry(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 6, 10, 0, 0));

        try {
            $existingEnd = now()->addDays(2);
            [$user, $subscription, $price] = $this->createSubscriptionWithPricing([
                'status' => 'active',
                'end_date' => $existingEnd,
                'ends_at' => $existingEnd,
            ], renewalWarningDays: 5, durationUnit: 'day', durationCount: 30);

            $this->assertNotNull($price);

            $this->actingAs($user)
                ->post(route('subscription.renew'))
                ->assertRedirect(route('payment.create', ['subscription' => $subscription->id]));

            $subscription->refresh();
            $renewalPayment = $subscription->payments()->latest('id')->first();

            $this->assertSame('active', $subscription->status->value);
            $this->assertTrue($subscription->starts_at->equalTo(now()->subMonth()));
            $this->assertTrue($subscription->ends_at->equalTo($existingEnd));
            $this->assertNotNull($renewalPayment);
            $this->assertSame('pending', $renewalPayment->status->value);
            $this->assertSame($subscription->id, $renewalPayment->subscription_id);
            $this->assertSame('subscription', data_get($renewalPayment->payment_details, 'payment_scope'));
            $this->assertSame('renewal_checkout', data_get($renewalPayment->payment_details, 'lifecycle_action'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_non_expiring_subscription_outside_warning_window_is_not_renewable(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 6, 10, 0, 0));

        try {
            [$user, $subscription] = $this->createSubscriptionWithPricing([
                'status' => 'active',
                'end_date' => now()->addDays(20),
                'ends_at' => now()->addDays(20),
            ], renewalWarningDays: 5);

            $this->actingAs($user)
                ->from(route('subscription.index'))
                ->post(route('subscription.renew'))
                ->assertRedirect(route('subscription.index'));

            $subscription->refresh();

            $this->assertSame('active', $subscription->status->value);
            $this->assertTrue($subscription->ends_at->equalTo(now()->addDays(20)));
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{0: User, 1: Subscription, 2: ?PlanPrice}
     */
    private function createSubscriptionWithPricing(
        array $subscriptionAttributes,
        int $renewalWarningDays,
        string $durationUnit = 'month',
        int $durationCount = 1
    ): array {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Renewal Flow Plan',
            'slug' => 'renewal-flow-plan-' . uniqid(),
            'description' => 'Renewal flow testing plan',
            'price' => 199,
            'features' => [],
            'renewal_warning_days' => $renewalWarningDays,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $price = PlanPrice::create([
            'plan_id' => $plan->id,
            'duration_mode' => 'preset',
            'duration_unit' => $durationUnit,
            'duration_count' => $durationCount,
            'duration_label' => ucfirst($durationUnit),
            'amount_minor' => 19900,
            'currency' => 'PHP',
            'is_default' => true,
            'is_active' => true,
        ]);

        $subscription = Subscription::create(array_merge([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_price_id' => $price->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addDay(),
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDay(),
            'price_paid' => 199,
            'auto_renew' => true,
        ], $subscriptionAttributes));

        return [$user, $subscription, $price];
    }
}
