<?php

namespace Tests\Unit\Services;

use App\Enums\SubscriptionStatus;
use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Instructor\InstructorPlanCapabilityService;
use Tests\TestCase;

class InstructorPlanCapabilityServiceTest extends TestCase
{
	public function test_resolve_effective_plan_falls_back_to_free_instructor_baseline(): void
	{
		$instructor = $this->createInstructor();

		$baseline = SubscriptionPlan::query()->create([
			'name' => 'Instructor Baseline',
			'slug' => 'instructor-baseline-' . uniqid(),
			'description' => 'Free baseline',
			'price' => 0,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 1,
		]);

		SubscriptionPlan::query()->create([
			'name' => 'Instructor Pro',
			'slug' => 'instructor-pro-' . uniqid(),
			'description' => 'Paid tier',
			'price' => 599,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 2,
		]);

		$service = app(InstructorPlanCapabilityService::class);

		$resolved = $service->resolveEffectivePlan($instructor);

		$this->assertNotNull($resolved);
		$this->assertSame($baseline->id, $resolved->id);
	}

	public function test_capabilities_are_resolved_from_active_instructor_subscription_plan(): void
	{
		$instructor = $this->createInstructor();

		SubscriptionPlan::query()->create([
			'name' => 'Instructor Baseline',
			'slug' => 'instructor-baseline-' . uniqid(),
			'description' => 'Free baseline',
			'price' => 0,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 1,
		]);

		$proPlan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Pro',
			'slug' => 'instructor-pro-' . uniqid(),
			'description' => 'Paid tier',
			'price' => 799,
			'features' => [],
			'plan_audience' => 'instructor',
			'billing_mode' => 'monthly',
			'trial_days' => 0,
			'is_active' => true,
			'sort_order' => 2,
		]);

		$this->attachEntitlement($proPlan, 'instructor_published_modules_limit', 'quota', 8);
		$this->attachEntitlement($proPlan, 'instructor_can_publish_paid_modules', 'boolean', null, true);

		Subscription::query()->create([
			'user_id' => $instructor->id,
			'plan_id' => $proPlan->id,
			'status' => SubscriptionStatus::Active->value,
			'starts_at' => now()->subDay(),
			'ends_at' => now()->addMonth(),
			'price_paid' => 799,
		]);

		$service = app(InstructorPlanCapabilityService::class);

		$this->assertSame(8, $service->getPublishedModuleLimit($instructor));
		$this->assertTrue($service->canPublishPaidModules($instructor));
	}

	public function test_rollout_mode_uses_subscription_features_config_key(): void
	{
		config()->set('subscription_features.instructor_rollout_mode', 'strict');

		$service = app(InstructorPlanCapabilityService::class);

		$this->assertSame('strict', $service->rolloutMode());
		$this->assertTrue($service->isStrictRolloutMode());

		config()->set('subscription_features.instructor_rollout_mode', 'invalid-mode');

		$this->assertSame('soft', $service->rolloutMode());
		$this->assertFalse($service->isStrictRolloutMode());
	}

	private function createInstructor(): User
	{
		$instructor = User::factory()->create([
			'role' => 'instructor',
		]);
		$instructor->assignRole('instructor');

		return $instructor;
	}

	private function attachEntitlement(
		SubscriptionPlan $plan,
		string $featureKey,
		string $valueType,
		?int $quotaValue = null,
		bool $enabled = true,
	): void {
		$feature = FeatureCatalog::query()->updateOrCreate(
			['key' => $featureKey],
			[
				'name' => str($featureKey)->headline()->toString(),
				'description' => str($featureKey)->headline()->toString(),
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
