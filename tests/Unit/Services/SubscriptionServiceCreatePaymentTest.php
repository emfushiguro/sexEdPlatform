<?php

namespace Tests\Unit\Services;

use App\Enums\PaymentStatus;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceCreatePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_stores_pending_payment_with_null_method_until_checkout_selection(): void
    {
        $user = User::factory()->create();

        $plan = SubscriptionPlan::create([
            'name' => 'Premium Learner',
            'slug' => 'premium-learner-create-test',
            'description' => 'Premium access',
            'price' => 199.99,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $subscription = app(SubscriptionService::class)->create($user, $plan);
        $payment = $subscription->payments()->latest('id')->first();

        $this->assertNotNull($payment);
        $this->assertNull($payment->method);
        $this->assertSame(PaymentStatus::Pending->value, $payment->status->value);
    }
}
