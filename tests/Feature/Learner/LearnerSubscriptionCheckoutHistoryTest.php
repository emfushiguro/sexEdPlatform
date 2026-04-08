<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Tests\TestCase;

class LearnerSubscriptionCheckoutHistoryTest extends TestCase
{
    public function test_payment_history_shows_subscription_checkout_entries_after_refactor(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'price_paid' => 299,
            'auto_renew' => true,
        ]);

        Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => $subscription->id,
            'amount' => 299,
            'method' => 'card',
            'status' => PaymentStatus::Completed,
            'transaction_id' => 'SUB-HISTORY-1',
            'payment_details' => [
                'payment_scope' => 'subscription',
                'payment_method' => 'card',
                'billing' => [
                    'name' => 'History Learner',
                    'email' => 'history.learner@example.test',
                    'phone' => '09171112222',
                ],
            ],
            'paid_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('payment.history'))
            ->assertOk()
            ->assertSee('Subscription')
            ->assertSee('Premium Plan')
            ->assertSee('Card')
            ->assertSee('SUB-HISTORY-1');
    }
}
