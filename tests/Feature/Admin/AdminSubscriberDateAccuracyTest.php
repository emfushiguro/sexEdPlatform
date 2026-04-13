<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriberDateAccuracyTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_index_prefers_normalized_timestamps_over_legacy_dates(): void
    {
        $admin = $this->createAdmin();
        [$subscription, $legacyStart, $legacyEnd, $normalizedStart, $normalizedEnd] = $this->createSubscriptionWithMismatchedDates();

        $this->actingAs($admin)
            ->get(route('admin.subscribers.index'))
            ->assertOk()
            ->assertSee($normalizedStart->format('M d, Y h:i A'), false)
            ->assertSee($normalizedEnd->format('M d, Y h:i A'), false)
            ->assertDontSee($legacyStart->format('M d, Y h:i A'), false)
            ->assertDontSee($legacyEnd->format('M d, Y h:i A'), false);

        $this->assertNotNull($subscription->id);
    }

    public function test_subscriber_show_prefers_normalized_timestamps_over_legacy_dates(): void
    {
        $admin = $this->createAdmin();
        [$subscription, $legacyStart, $legacyEnd, $normalizedStart, $normalizedEnd] = $this->createSubscriptionWithMismatchedDates();

        $this->actingAs($admin)
            ->get(route('admin.subscribers.show', $subscription))
            ->assertOk()
            ->assertSee($normalizedStart->format('M d, Y h:i A'), false)
            ->assertSee($normalizedEnd->format('M d, Y h:i A'), false)
            ->assertDontSee($legacyStart->format('M d, Y h:i A'), false)
            ->assertDontSee($legacyEnd->format('M d, Y h:i A'), false);
    }

    public function test_subscriber_index_revenue_uses_completed_subscription_payments_including_expired_records(): void
    {
        $admin = $this->createAdmin();

        [$activeSubscription] = $this->createSubscriptionWithMismatchedDates();
        [$expiredSubscription] = $this->createSubscriptionWithMismatchedDates();

        $activeSubscription->update(['status' => 'active']);
        $expiredSubscription->update(['status' => 'expired']);

        Payment::create([
            'user_id' => $activeSubscription->user_id,
            'subscription_id' => $activeSubscription->id,
            'amount' => 199.00,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'STAT-ACTIVE-' . uniqid(),
            'payment_details' => ['payment_scope' => 'subscription'],
            'paid_at' => now()->subDay(),
        ]);

        Payment::create([
            'user_id' => $expiredSubscription->user_id,
            'subscription_id' => $expiredSubscription->id,
            'amount' => 299.00,
            'method' => 'paymongo',
            'status' => 'completed',
            'transaction_id' => 'STAT-EXPIRED-' . uniqid(),
            'payment_details' => ['payment_scope' => 'subscription'],
            'paid_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.subscribers.index'))
            ->assertOk();

        $stats = $response->viewData('subscriptionStats');

        $this->assertSame(498.0, (float) ($stats['total_revenue'] ?? 0));
    }

    /**
     * @return array{0:Subscription,1:\Illuminate\Support\Carbon,2:\Illuminate\Support\Carbon,3:\Illuminate\Support\Carbon,4:\Illuminate\Support\Carbon}
     */
    private function createSubscriptionWithMismatchedDates(): array
    {
        $subscriber = User::factory()->create(['role' => 'learner']);
        $subscriber->assignRole('learner');

        $plan = SubscriptionPlan::create([
            'name' => 'Admin Date Accuracy Plan',
            'slug' => 'admin-date-accuracy-' . uniqid(),
            'description' => 'Plan for normalized date precedence tests.',
            'price' => 199,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $legacyStart = now()->setDate(2024, 1, 10)->setTime(8, 30, 0);
        $legacyEnd = now()->setDate(2024, 2, 15)->setTime(9, 45, 0);
        $normalizedStart = now()->setDate(2026, 5, 20)->setTime(14, 15, 0);
        $normalizedEnd = now()->setDate(2026, 6, 25)->setTime(16, 30, 0);

        $subscription = Subscription::create([
            'user_id' => $subscriber->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => $legacyStart,
            'end_date' => $legacyEnd,
            'starts_at' => $normalizedStart,
            'ends_at' => $normalizedEnd,
            'price_paid' => 199,
            'auto_renew' => true,
        ]);

        return [$subscription, $legacyStart, $legacyEnd, $normalizedStart, $normalizedEnd];
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        return $admin;
    }
}
