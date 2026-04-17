<?php

namespace Tests\Feature\Instructor;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Module;
use Tests\TestCase;

class InstructorModulePlanEnforcementTest extends TestCase
{
	public function test_strict_mode_blocks_paid_module_save_without_paid_publish_entitlement(): void
	{
		config()->set('subscription_features.instructor_rollout_mode', 'strict');

		$instructor = $this->createInstructorUser();
		$this->createInstructorBaselineWithoutPaidPublishing();

		$this->actingAs($instructor)
			->from(route('instructor.modules.index'))
			->post(route('instructor.modules.store'), [
				'title' => 'Strict paid module',
				'description' => 'Blocked in strict mode',
				'age_bracket' => 'adults',
				'enrollment_mode' => 'manual',
				'access_type' => 'paid',
				'price_amount' => 129.00,
				'price_currency' => 'PHP',
			])
			->assertRedirect(route('instructor.modules.index'))
			->assertSessionHasErrors(['access_type']);

		$this->assertDatabaseMissing('modules', [
			'title' => 'Strict paid module',
		]);
	}

	public function test_strict_mode_blocks_earnings_page_without_earnings_entitlement(): void
	{
		config()->set('subscription_features.instructor_rollout_mode', 'strict');

		$instructor = $this->createInstructorUser();
		$this->createInstructorBaselineWithoutPaidPublishing();

		$this->actingAs($instructor)
			->get(route('instructor.earnings.index'))
			->assertForbidden();
	}

	public function test_module_creation_is_blocked_when_plan_limit_is_reached(): void
	{
		config()->set('subscription_features.instructor_rollout_mode', 'soft');

		$instructor = $this->createInstructorUser();
		$this->createInstructorBaselineWithPublishedLimit(1);

		Module::factory()->create([
			'title' => 'Existing Published Module',
			'created_by' => $instructor->id,
			'content_owner_type' => 'instructor',
			'is_published' => true,
			'current_review_status' => 'approved',
			'access_type' => 'free',
			'price_amount' => null,
		]);

		$this->actingAs($instructor)
			->from(route('instructor.modules.index'))
			->post(route('instructor.modules.store'), [
				'title' => 'Blocked Module',
				'description' => 'Should be blocked after limit is reached',
				'age_bracket' => 'adults',
				'enrollment_mode' => 'manual',
				'access_type' => 'free',
			])
			->assertRedirect(route('instructor.modules.index'))
			->assertSessionHasErrors(['title']);

		$this->assertDatabaseMissing('modules', [
			'title' => 'Blocked Module',
		]);
	}

	public function test_existing_module_can_be_edited_even_when_plan_limit_is_reached(): void
	{
		config()->set('subscription_features.instructor_rollout_mode', 'soft');

		$instructor = $this->createInstructorUser();
		$this->createInstructorBaselineWithPublishedLimit(1);

		$module = Module::factory()->create([
			'title' => 'Existing Published Module',
			'description' => 'Original description',
			'created_by' => $instructor->id,
			'content_owner_type' => 'instructor',
			'is_published' => true,
			'current_review_status' => 'approved',
			'access_type' => 'free',
			'price_amount' => null,
		]);

		$this->actingAs($instructor)
			->put(route('instructor.modules.update', $module), [
				'title' => 'Existing Published Module',
				'description' => 'Updated description while at limit',
				'age_bracket' => 'adults',
				'enrollment_mode' => 'manual',
				'access_type' => 'free',
			])
			->assertRedirect(route('instructor.modules.index'))
			->assertSessionHasNoErrors();

		$this->assertDatabaseHas('modules', [
			'id' => $module->id,
			'description' => 'Updated description while at limit',
		]);
	}

	private function createInstructorUser(): User
	{
		$instructor = User::factory()->create([
			'role' => 'instructor',
		]);
		$instructor->assignRole('instructor');

		return $instructor;
	}

	private function createInstructorBaselineWithoutPaidPublishing(): SubscriptionPlan
	{
		$plan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Free Baseline',
			'slug' => 'instructor-baseline-' . uniqid(),
			'description' => 'Strict baseline for tests',
			'price' => 0,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 1,
		]);

		$feature = FeatureCatalog::query()->updateOrCreate(
			['key' => 'instructor_can_publish_paid_modules'],
			[
				'name' => 'Can Publish Paid Modules',
				'description' => 'Can Publish Paid Modules',
				'value_type' => 'boolean',
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
				'is_enabled' => false,
				'quota_value' => null,
				'is_unlimited' => false,
			]
		);

		return $plan;
	}

	private function createInstructorBaselineWithPublishedLimit(int $limit): SubscriptionPlan
	{
		$plan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Free Baseline Limit',
			'slug' => 'instructor-baseline-limit-' . uniqid(),
			'description' => 'Baseline with limit',
			'price' => 0,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 1,
		]);

		$feature = FeatureCatalog::query()->updateOrCreate(
			['key' => 'instructor_published_modules_limit'],
			[
				'name' => 'Published Modules Limit',
				'description' => 'Published Modules Limit',
				'value_type' => 'quota',
				'unit_label' => 'modules',
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
				'is_enabled' => true,
				'quota_value' => $limit,
				'is_unlimited' => false,
			]
		);

		return $plan;
	}
}
