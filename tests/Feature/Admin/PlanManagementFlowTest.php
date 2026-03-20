<?php

namespace Tests\Feature\Admin;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlanManagementFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_plans_index_uses_modal_first_plan_creation_flow(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.subscription-plans.index'))
            ->assertOk()
            ->assertSee('data-testid="open-create-plan-modal"', false)
            ->assertSee(route('admin.subscribers.store-plan'), false)
            ->assertDontSee(route('admin.subscription-plans.create'), false);
    }

    public function test_admin_can_create_plan_with_multiple_prices_and_entitlements(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $payload = [
            'name' => 'Premium Learner',
            'description' => 'Best plan for learners',
            'is_active' => true,
            'sort_order' => 10,
            'prices' => [
                [
                    'duration_mode' => 'preset',
                    'duration_unit' => 'month',
                    'duration_count' => 1,
                    'duration_label' => 'Monthly',
                    'amount_minor' => 49900,
                    'currency' => 'PHP',
                    'is_default' => true,
                    'is_active' => true,
                ],
                [
                    'duration_mode' => 'preset',
                    'duration_unit' => 'year',
                    'duration_count' => 1,
                    'duration_label' => 'Yearly',
                    'amount_minor' => 499900,
                    'currency' => 'PHP',
                    'is_default' => false,
                    'is_active' => true,
                ],
            ],
            'entitlements' => [
                [
                    'feature_key' => 'unlimited_shields',
                    'feature_name' => 'Unlimited Shields',
                    'value_type' => 'boolean',
                    'is_enabled' => true,
                    'is_unlimited' => true,
                ],
                [
                    'feature_key' => 'monthly_streak_savers_quota',
                    'feature_name' => 'Monthly Streak Savers',
                    'value_type' => 'quota',
                    'is_enabled' => true,
                    'quota_value' => 3,
                    'is_unlimited' => false,
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.subscribers.store-plan'), $payload)
            ->assertRedirect(route('admin.subscription-plans.index'));

        $plan = SubscriptionPlan::query()->where('slug', 'premium-learner')->first();

        $this->assertNotNull($plan);

        $this->assertDatabaseHas('plan_prices', [
            'plan_id' => $plan->id,
            'duration_label' => 'Monthly',
            'amount_minor' => 49900,
            'is_default' => 1,
        ]);

        $this->assertDatabaseHas('plan_prices', [
            'plan_id' => $plan->id,
            'duration_label' => 'Yearly',
            'amount_minor' => 499900,
            'is_default' => 0,
        ]);

        $this->assertDatabaseHas('feature_catalog', [
            'key' => 'unlimited_shields',
            'name' => 'Unlimited Shields',
        ]);

        $this->assertDatabaseHas('feature_catalog', [
            'key' => 'monthly_streak_savers_quota',
            'name' => 'Monthly Streak Savers',
        ]);

        $this->assertDatabaseCount('plan_feature_entitlements', 2);
    }
}
