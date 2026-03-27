<?php

namespace Tests\Feature\Admin;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_mutation_creates_activity_log_record(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        /** @var User $learner */
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Audit Plan',
            'slug' => 'audit-plan',
            'description' => 'Audit testing plan',
            'price' => 199,
            'features' => ['analytics'],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $subscription = Subscription::create([
            'user_id' => $learner->id,
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'price_paid' => $plan->price,
            'auto_renew' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.subscribers.quick-action'), [
                'action' => 'cancel_subscription',
                'subscription_id' => $subscription->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'subscribers.cancel',
            'entity_type' => Subscription::class,
        ]);
    }
}
