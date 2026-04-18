<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Tests\TestCase;

class CheckoutRouteAudienceRedirectTest extends TestCase
{
    public function test_instructor_checkout_rejects_learner_audience_subscription_within_instructor_flow(): void
    {
        $user = $this->createInstructorUser();

        $learnerPlan = SubscriptionPlan::query()->create([
            'name' => 'Learner Redirect Plan',
            'slug' => 'learner-redirect-' . uniqid(),
            'description' => 'Learner audience plan',
            'price' => 99.99,
            'features' => [],
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $learnerPlan->id,
            'plan' => $learnerPlan->slug,
            'status' => SubscriptionStatus::Pending,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price_paid' => 99.99,
            'auto_renew' => true,
        ]);

        $this->actingAs($user)
            ->get(route('instructor.payments.checkout.summary', $subscription))
            ->assertRedirect(route('instructor.subscriptions.index'))
            ->assertSessionHas('error');
    }

    public function test_instructor_checkout_allows_legacy_subscription_when_payment_role_context_is_instructor(): void
    {
        $user = $this->createInstructorUser();

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => null,
            'plan' => 'legacy-instructor-plan',
            'status' => SubscriptionStatus::Pending,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'price_paid' => 149.00,
            'auto_renew' => true,
        ]);

        Payment::query()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 149.00,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'TXN-LEGACY-' . strtoupper(uniqid()),
            'payment_details' => [
                'payment_scope' => 'subscription',
                'role_context' => 'instructor',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('instructor.payments.checkout.summary', $subscription))
            ->assertOk();
    }

    private function createInstructorUser(): User
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
        ]);
        $instructor->assignRole('instructor');

        return $instructor;
    }
}
