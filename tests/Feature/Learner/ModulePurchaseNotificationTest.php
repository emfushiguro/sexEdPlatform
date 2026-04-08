<?php

namespace Tests\Feature\Learner;

use App\Enums\PaymentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ModulePurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

class ModulePurchaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_successful_purchase_confirmation_sends_success_notification(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create();

        $subscription = $this->createSubscription($learner);

        $payment = Payment::factory()->pending()->create([
            'user_id' => $learner->id,
            'subscription_id' => $subscription->id,
            'status' => PaymentStatus::Pending,
            'method' => 'card',
            'payment_details' => [
                'payment_scope' => 'module_purchase',
                'module_id' => $module->id,
            ],
        ]);

        $this->mock(ModulePurchaseService::class, function (MockInterface $mock) use ($payment) {
            $mock->shouldReceive('verifyAndCompletePendingPayment')
                ->once()
                ->withArgs(fn (Payment $incoming) => (int) $incoming->id === (int) $payment->id)
                ->andReturn(true);
        });

        $this->actingAs($learner)
            ->get(route('learner.modules.purchase.success', $module))
            ->assertRedirect(route('learner.modules.show', $module));

        $notification = $learner->fresh()->notifications()->latest()->first();

        $this->assertNotNull($notification);
        $this->assertSame('module_purchase_result', data_get($notification->data, 'type'));
        $this->assertSame('success', data_get($notification->data, 'status'));
    }

    public function test_failed_or_cancelled_purchase_sends_failure_notification(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create();

        $this->actingAs($learner)
            ->get(route('learner.modules.purchase.failed', $module))
            ->assertRedirect(route('learner.modules.purchase.form', $module));

        $notification = $learner->fresh()->notifications()->latest()->first();

        $this->assertNotNull($notification);
        $this->assertSame('module_purchase_result', data_get($notification->data, 'type'));
        $this->assertSame('failed', data_get($notification->data, 'status'));
    }

    private function createSubscription(User $learner): Subscription
    {
        $plan = SubscriptionPlan::query()->create([
            'name' => 'Purchase Plan ' . Str::upper(Str::random(4)),
            'slug' => 'purchase-plan-' . Str::lower(Str::random(10)),
            'description' => 'Test plan for module purchase notifications',
            'price' => 199,
            'features' => ['full_course_access' => true],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return Subscription::query()->create([
            'user_id' => $learner->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'auto_renew' => true,
            'price_paid' => 199,
        ]);
    }
}
