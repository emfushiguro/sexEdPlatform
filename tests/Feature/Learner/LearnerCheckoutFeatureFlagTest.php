<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use Tests\TestCase;

class LearnerCheckoutFeatureFlagTest extends TestCase
{
    public function test_summary_checkout_routes_are_available_for_payment_flow(): void
    {
        $this->assertTrue(Route::has('payment.checkout.summary'));
        $this->assertTrue(Route::has('payment.checkout.proceed'));
    }

    public function test_legacy_payment_create_redirects_to_summary_when_feature_flag_is_enabled(): void
    {
        Config::set('billing.features.learner_checkout_refinement', true);

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
            'plan' => 'premium',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price_paid' => 299,
            'auto_renew' => true,
        ]);

        $this->actingAs($learner)
            ->get(route('payment.create', $subscription))
            ->assertRedirect(route('payment.checkout.summary', $subscription));
    }

    public function test_simulate_success_route_is_available_for_testing_and_staging_but_blocked_for_production_like_env(): void
    {
        $this->assertTrue(Route::has('payment.simulate-success'));
        Queue::fake();

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $subscription = Subscription::query()->create([
            'user_id' => $learner->id,
            'plan' => 'premium',
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price_paid' => 299,
            'auto_renew' => true,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $learner->id,
            'subscription_id' => $subscription->id,
            'amount' => 299,
            'method' => 'paymongo',
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'TEST-SIM-' . strtoupper(uniqid()),
            'payment_details' => [],
        ]);

        $this->mock(SubscriptionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('activate')->atLeast()->once();
        });

        Config::set('app.env', 'testing');
        $this->actingAs($learner)
            ->get(route('payment.simulate-success', $payment))
            ->assertRedirect(route('subscription.index'));

        Config::set('app.env', 'production');
        $this->actingAs($learner)
            ->get(route('payment.simulate-success', $payment))
            ->assertNotFound();

        Config::set('app.env', 'staging');
        $this->actingAs($learner)
            ->get(route('payment.simulate-success', $payment))
            ->assertRedirect(route('subscription.index'));
    }
}
