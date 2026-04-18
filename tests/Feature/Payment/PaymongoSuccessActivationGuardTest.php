<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PayMongoPaymentLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PaymongoSuccessActivationGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_paymongo_success_keeps_subscription_pending_when_payment_is_not_verified_paid(): void
    {
        $user = $this->createLearnerUser();
        $subscription = $this->createPendingSubscriptionWithPayment($user, 'cs_pending_guard_1');
        $payment = $subscription->payments()->latest('id')->firstOrFail();

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_pending_guard_1',
                        'attributes' => [
                            'status' => 'active',
                            'payments' => [
                                ['id' => 'pay_failed_1', 'attributes' => ['status' => 'failed']],
                            ],
                        ],
                    ],
                ]);

            $mock->shouldNotReceive('getActualPaymentIdFromCheckoutSession');
        });

        $this->actingAs($user)
            ->get(route('payment.paymongo.success', ['subscription' => $subscription->id]))
            ->assertRedirect(route('payment.pending', ['payment' => $payment->id]))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_paymongo_success_activates_subscription_when_paymongo_confirms_paid(): void
    {
        $user = $this->createLearnerUser();
        $subscription = $this->createPendingSubscriptionWithPayment($user, 'cs_paid_guard_1');
        $payment = $subscription->payments()->latest('id')->firstOrFail();

        $this->mock(PayMongoPaymentLinkService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('retrieveCheckoutSession')
                ->once()
                ->andReturn([
                    'data' => [
                        'id' => 'cs_paid_guard_1',
                        'attributes' => [
                            'status' => 'paid',
                            'payments' => [
                                ['id' => 'pay_paid_1', 'attributes' => ['status' => 'paid']],
                            ],
                        ],
                    ],
                ]);

            $mock->shouldReceive('getActualPaymentIdFromCheckoutSession')
                ->once()
                ->andReturn('pay_paid_1');
        });

        $this->actingAs($user)
            ->get(route('payment.paymongo.success', ['subscription' => $subscription->id]))
            ->assertRedirect(route('subscription.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subscribers', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Active->value,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Completed->value,
        ]);
    }

    public function test_subscription_success_page_without_payment_id_does_not_activate_pending_from_unrelated_completed_payment(): void
    {
        $user = $this->createLearnerUser();

        $completedSubscription = $this->createPendingSubscriptionWithPayment($user, 'cs_completed_guard_1');
        $completedPayment = Payment::query()->where('subscription_id', $completedSubscription->id)->latest('id')->firstOrFail();
        $completedPayment->update([
            'status' => PaymentStatus::Completed,
            'paid_at' => now()->subDay(),
        ]);
        $completedSubscription->refresh();
        $completedSubscription->update(['status' => SubscriptionStatus::Active]);

        $pendingSubscription = $this->createPendingSubscriptionWithPayment($user, '');

        $this->actingAs($user)
            ->get(route('payment.success', ['scope' => 'subscription']))
            ->assertOk();

        $this->assertDatabaseHas('subscribers', [
            'id' => $pendingSubscription->id,
            'status' => SubscriptionStatus::Pending->value,
        ]);
    }

    private function createLearnerUser(): User
    {
        $user = User::factory()->create(['role' => 'learner']);
        $user->assignRole('learner');

        return $user;
    }

    private function createPendingSubscriptionWithPayment(User $user, string $sessionId): Subscription
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Learner Premium ' . uniqid(),
            'slug' => 'learner-premium-' . uniqid(),
            'description' => 'Learner premium plan',
            'price' => 149,
            'features' => [],
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => SubscriptionStatus::Pending,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price_paid' => 149,
            'auto_renew' => true,
        ]);

        Payment::query()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 149,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'TXN-GUARD-' . strtoupper(uniqid()),
            'payment_details' => [
                'payment_scope' => 'subscription',
                'paymongo_checkout_session_id' => $sessionId,
            ],
        ]);

        return $subscription;
    }
}
