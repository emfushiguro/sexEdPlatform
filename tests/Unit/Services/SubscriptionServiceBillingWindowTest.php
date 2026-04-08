<?php

namespace Tests\Unit\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceBillingWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_uses_plan_price_duration_for_precise_timestamps(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 6, 10, 0, 0));

        try {
            $user = User::factory()->create();
            $plan = $this->createPlanWithDuration('hour', 6);

            $subscription = app(SubscriptionService::class)->create($user, $plan)->fresh();

            $this->assertSame('pending', $subscription->status->value);
            $this->assertNotNull($subscription->plan_price_id);
            $this->assertTrue($subscription->starts_at->equalTo(now()));
            $this->assertTrue($subscription->ends_at->equalTo(now()->copy()->addHours(6)));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_activate_recomputes_start_and_end_dates_from_activation_time(): void
    {
        $createdAt = Carbon::create(2026, 4, 6, 8, 0, 0);
        Carbon::setTestNow($createdAt);

        try {
            $user = User::factory()->create();
            $plan = $this->createPlanWithDuration('day', 3);
            $service = app(SubscriptionService::class);

            $subscription = $service->create($user, $plan);

            $activationTime = $createdAt->copy()->addHours(5);
            Carbon::setTestNow($activationTime);

            $service->activate($subscription->fresh());
            $subscription->refresh();

            $this->assertSame('active', $subscription->status->value);
            $this->assertTrue($subscription->starts_at->equalTo($activationTime));
            $this->assertTrue($subscription->ends_at->equalTo($activationTime->copy()->addDays(3)));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_renew_extends_from_existing_end_when_cancellation_period_is_still_active(): void
    {
        $renewedAt = Carbon::create(2026, 4, 6, 9, 30, 0);
        Carbon::setTestNow($renewedAt);

        try {
            $user = User::factory()->create();
            $plan = $this->createPlanWithDuration('week', 2);
            $price = $plan->defaultPlanPrice()->first();

            $existingEnd = $renewedAt->copy()->addDays(4);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'plan_price_id' => $price?->id,
                'plan' => 'premium',
                'status' => 'cancelled',
                'start_date' => $renewedAt->copy()->subDays(10),
                'end_date' => $existingEnd,
                'starts_at' => $renewedAt->copy()->subDays(10),
                'ends_at' => $existingEnd,
                'price_paid' => 199,
                'auto_renew' => false,
                'cancelled_at' => $renewedAt->copy()->subDay(),
            ]);

            app(SubscriptionService::class)->renew($subscription);
            $subscription->refresh();
            $renewalPayment = $subscription->payments()->latest('id')->first();

            $this->assertSame('active', $subscription->status->value);
            $this->assertTrue($subscription->starts_at->equalTo($renewedAt));
            $this->assertTrue($subscription->ends_at->equalTo($existingEnd->copy()->addWeeks(2)));
            $this->assertTrue((bool) $subscription->auto_renew);
            $this->assertSame('manual_renewal', $subscription->source_provider);
            $this->assertNotEmpty($subscription->source_reference);
            $this->assertNotNull($renewalPayment);
            $this->assertSame('completed', $renewalPayment->status->value);
            $this->assertSame(199.0, (float) $renewalPayment->amount);
            $this->assertSame('subscription', data_get($renewalPayment->payment_details, 'payment_scope'));
            $this->assertSame('renewal', data_get($renewalPayment->payment_details, 'lifecycle_action'));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createPlanWithDuration(string $durationUnit, int $durationCount): SubscriptionPlan
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Premium Plan ' . uniqid(),
            'slug' => 'premium-plan-' . uniqid(),
            'description' => 'Premium access',
            'price' => 199,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $plan->planPrices()->create([
            'duration_mode' => 'preset',
            'duration_unit' => $durationUnit,
            'duration_count' => $durationCount,
            'duration_label' => ucfirst($durationUnit),
            'amount_minor' => 19900,
            'currency' => 'PHP',
            'is_default' => true,
            'is_active' => true,
        ]);

        return $plan;
    }
}
