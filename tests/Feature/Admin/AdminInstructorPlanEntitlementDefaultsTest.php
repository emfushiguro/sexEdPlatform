<?php

namespace Tests\Feature\Admin;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Tests\TestCase;

class AdminInstructorPlanEntitlementDefaultsTest extends TestCase
{
	public function test_admin_features_api_returns_canonical_instructor_entitlement_keys(): void
	{
		$admin = $this->createAdminUser();

		$response = $this->actingAs($admin)
			->getJson(route('admin.api.features', ['audience' => 'instructor']));

		$response->assertOk();

		$featureKeys = collect($response->json('features'))->pluck('key');

		$this->assertTrue($featureKeys->contains('instructor_published_modules_limit'));
		$this->assertTrue($featureKeys->contains('instructor_max_learners_per_free_module'));
		$this->assertTrue($featureKeys->contains('instructor_max_learners_per_paid_module'));
		$this->assertTrue($featureKeys->contains('instructor_can_publish_paid_modules'));
		$this->assertTrue($featureKeys->contains('instructor_can_receive_paid_enrollments'));
		$this->assertTrue($featureKeys->contains('instructor_can_view_earnings'));
	}

	public function test_admin_subscription_index_shows_instructor_baseline_defaults(): void
	{
		$admin = $this->createAdminUser();
		$this->createInstructorBaselinePlan();

		$this->actingAs($admin)
			->get(route('admin.subscription-plans.index'))
			->assertOk()
			->assertSee('data-testid="instructor-baseline-plan-banner"', false)
			->assertSeeText('Free instructor plan: Instructor Baseline Free')
			->assertSeeText('Published modules')
			->assertSeeText('Paid enrollments')
			->assertSeeText('Earnings visibility');
	}

	private function createAdminUser(): User
	{
		$admin = User::factory()->create([
			'role' => 'admin',
		]);
		$admin->assignRole('admin');

		return $admin;
	}

	private function createInstructorBaselinePlan(): SubscriptionPlan
	{
		$plan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Baseline Free',
			'slug' => 'instructor-baseline-free-' . uniqid(),
			'description' => 'Default instructor baseline',
			'price' => 0,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 1,
		]);

		$this->attachEntitlement($plan, 'instructor_published_modules_limit', 'Published Modules Limit', 'quota', 2);
		$this->attachEntitlement($plan, 'instructor_max_learners_per_free_module', 'Free Module Learner Cap', 'quota', 30);
		$this->attachEntitlement($plan, 'instructor_max_learners_per_paid_module', 'Paid Module Learner Cap', 'quota', 10);
		$this->attachEntitlement($plan, 'instructor_can_publish_paid_modules', 'Can Publish Paid Modules', 'boolean', null, true);
		$this->attachEntitlement($plan, 'instructor_can_receive_paid_enrollments', 'Can Receive Paid Enrollments', 'boolean', null, true);
		$this->attachEntitlement($plan, 'instructor_can_view_earnings', 'Can View Earnings', 'boolean', null, true);

		return $plan;
	}

	private function attachEntitlement(
		SubscriptionPlan $plan,
		string $key,
		string $name,
		string $valueType,
		?int $quotaValue = null,
		bool $enabled = true,
	): void {
		$feature = FeatureCatalog::query()->updateOrCreate(
			['key' => $key],
			[
				'name' => $name,
				'description' => $name,
				'value_type' => $valueType,
				'unit_label' => $valueType === 'quota' ? 'count' : null,
				'category' => 'instructor',
				'is_active' => true,
			]
		);

		PlanFeatureEntitlement::query()->updateOrCreate(
			[
				'plan_id' => $plan->id,
				'feature_id' => $feature->id,
			],
			[
				'is_enabled' => $enabled,
				'quota_value' => $quotaValue,
				'is_unlimited' => false,
			]
		);
	}
}
