<?php

namespace Tests\Feature\Notifications;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Events\PaymentSuccessful;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionDunningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SubscriptionAndPaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_subscription_payment_notifies_learner_and_admins(): void
    {
        Event::fake([PaymentSuccessful::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $subscription = $this->createSubscription($learner, SubscriptionStatus::Pending, now()->addMonth(), true);

        $payment = Payment::factory()->pending()->create([
            'user_id' => $learner->id,
            'subscription_id' => $subscription->id,
            'payment_details' => ['payment_scope' => 'subscription'],
        ]);

        $payment->update(['status' => PaymentStatus::Completed]);

        $learnerNotification = $learner->fresh()->notifications()->latest()->first();

        $this->assertNotNull($learnerNotification);
        $this->assertSame('subscription_result', data_get($learnerNotification->data, 'type'));
        $this->assertSame('completed', data_get($learnerNotification->data, 'status'));

        $adminTypes = $admin->fresh()->notifications()->pluck('data')->map(fn (array $data) => $data['type'] ?? null)->filter()->values()->all();

        $this->assertContains('new_subscription_purchase', $adminTypes);
        $this->assertContains('new_payment_transaction', $adminTypes);
    }

    public function test_failed_subscription_payment_notifies_learner_and_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $subscription = $this->createSubscription($learner, SubscriptionStatus::Pending, now()->addMonth(), true);

        $payment = Payment::factory()->pending()->create([
            'user_id' => $learner->id,
            'subscription_id' => $subscription->id,
            'payment_details' => ['payment_scope' => 'subscription'],
        ]);

        $payment->update(['status' => PaymentStatus::Failed]);

        $learnerNotification = $learner->fresh()->notifications()->latest()->first();

        $this->assertNotNull($learnerNotification);
        $this->assertSame('subscription_result', data_get($learnerNotification->data, 'type'));
        $this->assertSame('failed', data_get($learnerNotification->data, 'status'));

        $adminTypes = $admin->fresh()->notifications()->pluck('data')->map(fn (array $data) => $data['type'] ?? null)->filter()->values()->all();
        $this->assertContains('new_payment_transaction', $adminTypes);
    }

    public function test_expiring_subscription_paths_create_learner_notifications(): void
    {
        Mail::fake();

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $expiringSoon = $this->createSubscription($learner, SubscriptionStatus::Active, now()->addDays(7)->addHour(), true);

        app(SubscriptionDunningService::class)->handleExpiringSubscriptions();

        $reminder = $learner->fresh()->notifications()->latest()->first();

        $this->assertNotNull($reminder);
        $this->assertSame('subscription_expiration_reminder', data_get($reminder->data, 'type'));

        $expired = $this->createSubscription($learner, SubscriptionStatus::Active, now()->subDay(), true);

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertDatabaseHas('subscribers', [
            'id' => $expired->id,
            'status' => SubscriptionStatus::Expired->value,
        ]);

        $allTypes = $learner->fresh()->notifications()->pluck('data')->map(fn (array $data) => $data['type'] ?? null)->filter()->values()->all();
        $this->assertContains('subscription_result', $allTypes);
    }

    private function createSubscription(User $user, SubscriptionStatus $status, \Carbon\CarbonInterface $endDate, bool $autoRenew): Subscription
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Plan ' . Str::upper(Str::random(5)),
            'slug' => 'plan-' . Str::lower(Str::random(10)),
            'description' => 'Test plan',
            'price' => 199,
            'features' => ['full_course_access' => true],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => $status,
            'start_date' => now(),
            'end_date' => $endDate,
            'auto_renew' => $autoRenew,
            'price_paid' => 199,
        ]);
    }
}
