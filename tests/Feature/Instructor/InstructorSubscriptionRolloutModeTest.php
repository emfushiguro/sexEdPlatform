<?php

namespace Tests\Feature\Instructor;

use App\Models\FeatureCatalog;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Tests\TestCase;

class InstructorSubscriptionRolloutModeTest extends TestCase
{
	public function test_strict_rollout_blocks_review_approval_when_publish_quota_is_exhausted(): void
	{
		config()->set('subscription_features.instructor_rollout_mode', 'strict');

		[$admin, $reviewRequest, $candidateModule] = $this->createQuotaExhaustedReviewRequest();

		$this->actingAs($admin)
			->post(route('admin.content-reviews.approve', $reviewRequest), [
				'moderation_notes' => 'Strict rollout enforcement check.',
			])
			->assertRedirect(route('admin.content-reviews.show', $reviewRequest))
			->assertSessionHas('warning');

		$this->assertDatabaseHas('modules', [
			'id' => $candidateModule->id,
			'is_published' => 0,
		]);
	}

	public function test_soft_rollout_allows_review_approval_when_publish_quota_is_exhausted(): void
	{
		config()->set('subscription_features.instructor_rollout_mode', 'soft');

		[$admin, $reviewRequest, $candidateModule] = $this->createQuotaExhaustedReviewRequest();

		$this->actingAs($admin)
			->post(route('admin.content-reviews.approve', $reviewRequest), [
				'moderation_notes' => 'Soft rollout allows approval.',
			])
			->assertRedirect(route('admin.content-reviews.show', $reviewRequest))
			->assertSessionHas('success');

		$this->assertDatabaseHas('modules', [
			'id' => $candidateModule->id,
			'is_published' => 1,
			'current_review_status' => 'approved',
		]);
	}

	/**
	 * @return array{0:User,1:ModuleReviewRequest,2:Module}
	 */
	private function createQuotaExhaustedReviewRequest(): array
	{
		$admin = User::factory()->create(['role' => 'admin']);
		$admin->assignRole('admin');

		$instructor = User::factory()->create(['role' => 'instructor']);
		$instructor->assignRole('instructor');

		$this->createInstructorBaselineWithPublishedLimit(1);

		Module::factory()->create([
			'title' => 'Already Published',
			'created_by' => $instructor->id,
			'content_owner_type' => 'instructor',
			'is_published' => true,
			'current_review_status' => 'approved',
			'access_type' => 'free',
			'price_amount' => null,
		]);

		$candidateModule = Module::factory()->create([
			'title' => 'Candidate Module',
			'created_by' => $instructor->id,
			'content_owner_type' => 'instructor',
			'is_published' => false,
			'current_review_status' => 'in_review',
			'access_type' => 'free',
			'price_amount' => null,
		]);

		$revision = ModuleRevision::query()->create([
			'module_id' => $candidateModule->id,
			'revision_number' => 1,
			'snapshot_payload' => [
				'module' => [
					'title' => $candidateModule->title,
				],
			],
			'submitted_by' => $instructor->id,
			'status' => 'in_review',
			'submitted_at' => now(),
		]);

		$reviewRequest = ModuleReviewRequest::query()->create([
			'module_id' => $candidateModule->id,
			'module_revision_id' => $revision->id,
			'status' => 'in_review',
			'submitted_by' => $instructor->id,
			'submitted_at' => now(),
		]);

		return [$admin, $reviewRequest, $candidateModule];
	}

	private function createInstructorBaselineWithPublishedLimit(int $limit): SubscriptionPlan
	{
		$plan = SubscriptionPlan::query()->create([
			'name' => 'Instructor Baseline',
			'slug' => 'instructor-baseline-' . uniqid(),
			'description' => 'Baseline for rollout tests',
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

		PlanFeatureEntitlement::query()->create([
			'plan_id' => $plan->id,
			'feature_id' => $feature->id,
			'is_enabled' => true,
			'quota_value' => $limit,
			'is_unlimited' => false,
		]);

		return $plan;
	}
}
