<?php

namespace Tests\Feature\Admin;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlanManagementFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdminUser(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_plans_index_uses_modal_first_plan_creation_flow(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.subscription-plans.index'))
            ->assertOk()
            ->assertSee('data-testid="create-plan-fullscreen-modal"', false)
            ->assertSee('data-testid="open-create-plan-modal"', false)
            ->assertSee('data-sidebar-lock-hook="create-plan-modal"', false)
            ->assertSee(route('admin.subscribers.store-plan'), false)
            ->assertDontSee(route('admin.subscription-plans.create'), false);
    }

    public function test_admin_can_create_plan_with_multiple_prices_and_entitlements(): void
    {
        $admin = $this->createAdminUser();

        $payload = [
            'name' => 'Premium Learner',
            'description' => 'Best plan for learners',
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
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

    public function test_admin_can_create_monthly_plan_with_preview_dates_and_learner_audience(): void
    {
        $admin = $this->createAdminUser();

        $payload = [
            'name' => 'Monthly Learner Plus',
            'description' => 'Monthly learner plan',
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'price' => '299.99',
            'is_active' => true,
        ];

        $this->actingAs($admin)
            ->post(route('admin.subscribers.store-plan'), $payload)
            ->assertRedirect(route('admin.subscription-plans.index'));

        $plan = SubscriptionPlan::query()->where('slug', 'monthly-learner-plus')->first();
        $this->assertNotNull($plan);

        $this->assertSame('learner', $plan->plan_audience);
        $this->assertSame('monthly', $plan->billing_mode);
        $this->assertNotNull($plan->admin_preview_starts_on);
        $this->assertNotNull($plan->admin_preview_ends_on);
        $this->assertNull($plan->availability_starts_on);
        $this->assertNull($plan->availability_ends_on);

        $this->assertDatabaseHas('plan_prices', [
            'plan_id' => $plan->id,
            'duration_label' => 'Monthly',
            'amount_minor' => 29999,
            'currency' => 'PHP',
            'is_default' => 1,
        ]);
    }

    public function test_custom_period_requires_valid_start_and_end_dates(): void
    {
        $admin = $this->createAdminUser();

        $basePayload = [
            'name' => 'Custom Learner Plan',
            'description' => 'Custom schedule',
            'plan_audience' => 'learner',
            'billing_mode' => 'custom',
            'price' => '199.00',
            'is_active' => true,
        ];

        $this->actingAs($admin)
            ->from(route('admin.subscription-plans.index'))
            ->post(route('admin.subscribers.store-plan'), $basePayload)
            ->assertRedirect(route('admin.subscription-plans.index'))
            ->assertSessionHasErrors(['start_date', 'end_date']);

        $payloadWithInvalidRange = array_merge($basePayload, [
            'start_date' => now()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.subscription-plans.index'))
            ->post(route('admin.subscribers.store-plan'), $payloadWithInvalidRange)
            ->assertRedirect(route('admin.subscription-plans.index'))
            ->assertSessionHasErrors(['end_date']);
    }

    public function test_plan_audience_is_limited_to_learner_for_now(): void
    {
        $admin = $this->createAdminUser();

        $payload = [
            'name' => 'Instructor Future Plan',
            'description' => 'Future audience',
            'plan_audience' => 'instructor',
            'billing_mode' => 'annual',
            'price' => '499.00',
            'is_active' => true,
        ];

        $this->actingAs($admin)
            ->from(route('admin.subscription-plans.index'))
            ->post(route('admin.subscribers.store-plan'), $payload)
            ->assertRedirect(route('admin.subscription-plans.index'))
            ->assertSessionHasErrors(['plan_audience']);
    }

    public function test_price_must_be_non_negative_with_max_two_decimals(): void
    {
        $admin = $this->createAdminUser();

        $negativePayload = [
            'name' => 'Invalid Negative Price Plan',
            'description' => 'Invalid',
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'price' => '-1.00',
            'is_active' => true,
        ];

        $this->actingAs($admin)
            ->from(route('admin.subscription-plans.index'))
            ->post(route('admin.subscribers.store-plan'), $negativePayload)
            ->assertRedirect(route('admin.subscription-plans.index'))
            ->assertSessionHasErrors(['price']);

        $precisionPayload = [
            'name' => 'Invalid Precision Plan',
            'description' => 'Invalid',
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'price' => '1.999',
            'is_active' => true,
        ];

        $this->actingAs($admin)
            ->from(route('admin.subscription-plans.index'))
            ->post(route('admin.subscribers.store-plan'), $precisionPayload)
            ->assertRedirect(route('admin.subscription-plans.index'))
            ->assertSessionHasErrors(['price']);
    }

    public function test_admin_can_create_plan_with_simplified_learner_entitlement_payload(): void
    {
        $admin = $this->createAdminUser();

        $payload = [
            'name' => 'Learner Perks Plan',
            'description' => 'Simplified entitlement payload',
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'price' => '149.00',
            'is_active' => true,
            'entitlement_enabled' => [
                'unlimited_username_changes' => '1',
                'certificate_pdf_download' => '1',
                'unlimited_quiz_retaking' => '1',
                'monthly_streak_savers' => '1',
            ],
            'entitlement_unlimited' => [
                'unlimited_username_changes' => '1',
                'unlimited_quiz_retaking' => '1',
            ],
            'entitlement_limits' => [
                'monthly_streak_savers' => 5,
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.subscribers.store-plan'), $payload)
            ->assertRedirect(route('admin.subscription-plans.index'));

        $plan = SubscriptionPlan::query()->where('slug', 'learner-perks-plan')->first();
        $this->assertNotNull($plan);

        $this->assertDatabaseHas('feature_catalog', [
            'key' => 'unlimited_username_changes',
            'category' => 'account_profile',
        ]);

        $this->assertDatabaseHas('feature_catalog', [
            'key' => 'certificate_pdf_download',
            'category' => 'learning_access',
        ]);

        $this->assertDatabaseHas('plan_feature_entitlements', [
            'plan_id' => $plan->id,
            'is_enabled' => 1,
            'is_unlimited' => 1,
        ]);

        $this->assertDatabaseHas('plan_feature_entitlements', [
            'plan_id' => $plan->id,
            'quota_value' => 5,
            'is_unlimited' => 0,
        ]);
    }

    public function test_admin_create_plan_persists_phase1_boolean_entitlement_flags(): void
    {
        $admin = $this->createAdminUser();

        $payload = [
            'name' => 'Phase 1 Learner Access',
            'description' => 'Phase 1 booleans',
            'plan_audience' => 'learner',
            'billing_mode' => 'monthly',
            'price' => '199.00',
            'is_active' => true,
            'entitlement_enabled' => [
                'unlimited_shields' => '1',
                'certificate_pdf_download_access' => '1',
            ],
            'entitlement_unlimited' => [
                'unlimited_shields' => '1',
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.subscribers.store-plan'), $payload)
            ->assertRedirect(route('admin.subscription-plans.index'));

        $plan = SubscriptionPlan::query()->where('slug', 'phase-1-learner-access')->first();
        $this->assertNotNull($plan);

        $this->assertContains('unlimited_shields', $plan->features ?? []);
        $this->assertContains('certificate_pdf_download_access', $plan->features ?? []);
    }

    public function test_plans_index_exposes_shared_three_step_wizard_markers_for_create_and_edit(): void
    {
        $admin = $this->createAdminUser();

        SubscriptionPlan::create([
            'name' => 'Wizard Marker Plan',
            'slug' => 'wizard-marker-plan',
            'description' => 'Wizard marker',
            'price' => 149,
            'features' => ['certificate_pdf_download_access'],
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.subscription-plans.index'))
            ->assertOk()
            ->assertSee('data-testid="plan-wizard-step-1"', false)
            ->assertSee('data-testid="plan-wizard-step-2"', false)
            ->assertSee('data-testid="plan-wizard-step-3"', false)
                ->assertSee('data-testid="plan-wizard-billing-preview"', false)
            ->assertSee('data-testid="open-edit-plan-modal"', false)
            ->assertSee('data-testid="plan-wizard-mode"', false);
    }

    public function test_admin_can_archive_and_restore_plan_with_inactive_restore_default(): void
    {
        $admin = $this->createAdminUser();

        $plan = SubscriptionPlan::create([
            'name' => 'Lifecycle Plan',
            'slug' => 'lifecycle-plan',
            'description' => 'Lifecycle testing',
            'price' => 129,
            'features' => ['unlimited_shields'],
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.subscription-plans.archive', $plan))
            ->assertRedirect();

        $this->assertNotNull($plan->fresh()->archived_at);

        $this->actingAs($admin)
            ->post(route('admin.subscription-plans.restore', $plan->fresh()))
            ->assertRedirect();

        $restored = $plan->fresh();
        $this->assertNull($restored->archived_at);
        $this->assertFalse((bool) $restored->is_active);
    }

    public function test_plan_lifecycle_impact_endpoint_returns_subscriber_counts(): void
    {
        $admin = $this->createAdminUser();

        $plan = SubscriptionPlan::create([
            'name' => 'Impact Plan',
            'slug' => 'impact-plan',
            'description' => 'Impact testing',
            'price' => 99,
            'features' => ['certificate_pdf_download_access'],
            'is_active' => true,
        ]);

        $subscriber = User::factory()->create();
        
        \App\Models\Subscription::create([
            'user_id' => $subscriber->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'price_paid' => 99,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.subscription-plans.impact', $plan))
            ->assertOk()
            ->assertJsonPath('data.total_subscribers', 1)
            ->assertJsonPath('data.active_subscribers', 1);
    }

    public function test_plans_index_renders_lifecycle_impact_confirmation_modal_note(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.subscription-plans.index'))
            ->assertOk()
            ->assertSee('data-testid="plan-impact-modal"', false)
            ->assertSee('Existing subscribers keep their current entitlement until renewal or expiry.', false);
    }
}
