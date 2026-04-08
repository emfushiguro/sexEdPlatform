<?php

namespace Tests\Unit\Services\Checkout;

use App\Enums\PaymentStatus;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Checkout\LearnerCheckoutService;
use App\Services\ModulePurchaseService;
use App\Services\PayMongoPaymentLinkService;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LearnerCheckoutServiceTest extends TestCase
{
    public function test_build_module_checkout_context_returns_expected_payload(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Healthy Boundaries 101',
            'description' => 'A paid module.',
            'access_type' => 'paid',
            'price_amount' => 499,
            'price_currency' => 'PHP',
            'is_published' => true,
            'current_review_status' => null,
        ]);

        $service = app(LearnerCheckoutService::class);

        $context = $service->buildModuleContext($learner, $module);

        $this->assertSame('module_purchase', $context['scope']);
        $this->assertSame('Healthy Boundaries 101', $context['item_name']);
        $this->assertSame(499.0, (float) $context['amount']);
        $this->assertSame('PHP', $context['currency']);
    }

    public function test_build_subscription_checkout_context_returns_expected_payload(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Premium Plus',
            'slug' => 'premium-plus-test',
            'description' => 'Premium plus plan',
            'price' => 299,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price_paid' => 299,
            'auto_renew' => true,
        ]);

        $service = app(LearnerCheckoutService::class);
        $context = $service->buildSubscriptionContext($learner, $subscription->fresh('plan'));

        $this->assertSame('subscription', $context['scope']);
        $this->assertSame('Premium Plus', $context['item_name']);
        $this->assertSame(299.0, (float) $context['amount']);
        $this->assertSame('PHP', $context['currency']);
    }

    public function test_create_checkout_for_module_delegates_to_module_purchase_service_and_adds_scope(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $module = Module::factory()->create([
            'created_by' => User::factory()->create(['role' => 'instructor'])->id,
            'access_type' => 'paid',
            'price_amount' => 499,
            'price_currency' => 'PHP',
            'is_published' => true,
            'current_review_status' => null,
        ]);

        $modulePurchaseService = Mockery::mock(ModulePurchaseService::class);
        $modulePurchaseService->shouldReceive('createCheckout')
            ->once()
            ->andReturnUsing(static fn () => [
                'status' => 'checkout_created',
                'checkout_url' => 'https://checkout.test/module',
                'payment_id' => 123,
                'message' => 'Redirecting to secure checkout.',
            ]);

        $paymongoService = Mockery::mock(PayMongoPaymentLinkService::class);

        $service = new LearnerCheckoutService($modulePurchaseService, $paymongoService);

        $result = $service->createCheckout(
            scope: 'module_purchase',
            user: $learner,
            subject: $module,
            paymentMethod: 'gcash',
            billing: [
                'name' => 'Learner One',
                'email' => 'learner@example.test',
                'phone' => '09171234567',
            ],
        );

        $this->assertSame('module_purchase', $result['scope']);
        $this->assertSame('checkout_created', $result['status']);
        $this->assertSame('https://checkout.test/module', $result['checkout_url']);
        $this->assertSame(123, $result['payment_id']);
    }

    public function test_create_checkout_for_subscription_returns_deterministic_payload(): void
    {
        Config::set('paymongo.payment_link.allowed_payment_method_types', ['gcash', 'paymaya', 'grab_pay', 'card']);

        $learner = User::factory()->create(['role' => 'learner']);
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Premium Plus',
            'slug' => 'premium-plus-test-2',
            'description' => 'Premium plus plan',
            'price' => 299,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price_paid' => 299,
            'auto_renew' => true,
        ]);

        $modulePurchaseService = Mockery::mock(ModulePurchaseService::class);

        $paymongoService = Mockery::mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_sub_checkout_1',
                        'attributes' => [
                            'checkout_url' => 'https://checkout.test/subscription',
                        ],
                    ],
                ]);
        });

        $service = new LearnerCheckoutService($modulePurchaseService, $paymongoService);

        $result = $service->createCheckout(
            scope: 'subscription',
            user: $learner,
            subject: $subscription,
            paymentMethod: 'card',
            billing: [
                'name' => 'Learner One',
                'email' => 'learner@example.test',
                'phone' => '09171234567',
            ],
        );

        $this->assertSame('subscription', $result['scope']);
        $this->assertSame('checkout_created', $result['status']);
        $this->assertSame('https://checkout.test/subscription', $result['checkout_url']);
        $this->assertIsInt($result['payment_id']);

        $payment = Payment::query()->findOrFail($result['payment_id']);
        $this->assertSame(PaymentStatus::Pending->value, $payment->status->value);
        $this->assertSame('card', $payment->method);
        $this->assertSame('subscription', data_get($payment->payment_details, 'payment_scope'));
        $this->assertSame('cs_sub_checkout_1', data_get($payment->payment_details, 'paymongo_checkout_session_id'));
    }
}
