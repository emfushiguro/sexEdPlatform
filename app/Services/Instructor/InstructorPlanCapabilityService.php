<?php

namespace App\Services\Instructor;

use App\Models\FeatureCatalog;
use App\Models\Module;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Support\SubscriptionFeatureKeys;

class InstructorPlanCapabilityService
{
	public function __construct(
		private readonly SubscriptionService $subscriptionService,
	) {
	}

	public function rolloutMode(): string
	{
		$mode = strtolower((string) config(
			'subscription_features.instructor_rollout_mode',
			config('billing.subscription.instructor_rollout_mode', 'soft')
		));

		return in_array($mode, ['soft', 'strict'], true) ? $mode : 'soft';
	}

	public function isStrictRolloutMode(): bool
	{
		return $this->rolloutMode() === 'strict';
	}

	public function resolveEffectivePlan(User $instructor): ?SubscriptionPlan
	{
		$eligibleSubscription = $this->subscriptionService->getEligibleSubscriptionForEntitlements($instructor);

		if ($eligibleSubscription && $eligibleSubscription->plan_id) {
			$activePlan = SubscriptionPlan::query()
				->whereKey($eligibleSubscription->plan_id)
				->where('is_active', true)
				->whereNull('archived_at')
				->first();

			if ($activePlan && (string) ($activePlan->plan_audience ?? '') === 'instructor') {
				return $activePlan;
			}
		}

		return $this->resolveBaselinePlan();
	}

	public function resolveBaselinePlan(): ?SubscriptionPlan
	{
		return SubscriptionPlan::query()
			->where('plan_audience', 'instructor')
			->where('is_active', true)
			->whereNull('archived_at')
			->where('price', '<=', 0)
			->orderBy('sort_order')
			->orderBy('id')
			->first();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getBaselineSnapshot(): array
	{
		$baselinePlan = $this->resolveBaselinePlan();

		return [
			'plan' => $baselinePlan ? [
				'id' => $baselinePlan->id,
				'name' => $baselinePlan->name,
				'slug' => $baselinePlan->slug,
				'price' => (float) ($baselinePlan->price ?? 0),
			] : null,
			'published_modules_limit' => $this->resolveBaselineQuotaCapability($baselinePlan, SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT),
			'free_module_learner_cap' => $this->resolveBaselineQuotaCapability($baselinePlan, SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE),
			'paid_module_learner_cap' => $this->resolveBaselineQuotaCapability($baselinePlan, SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE),
			'can_publish_paid_modules' => $this->resolveBaselineBooleanCapability($baselinePlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES),
			'can_receive_paid_enrollments' => $this->resolveBaselineBooleanCapability($baselinePlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS),
			'can_view_earnings' => $this->resolveBaselineBooleanCapability($baselinePlan, SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS),
		];
	}

	public function getPublishedModuleLimit(User $instructor): ?int
	{
		return $this->resolveQuotaCapability($instructor, SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT);
	}

	public function getPublishedModuleCount(User $instructor): int
	{
		return Module::query()
			->where('created_by', $instructor->id)
			->where('content_owner_type', 'instructor')
			->where('is_published', true)
			->count();
	}

	public function canCreateModule(User $instructor): bool
	{
		$publishedLimit = $this->getPublishedModuleLimit($instructor);

		if ($publishedLimit === null) {
			return true;
		}

		return $this->getPublishedModuleCount($instructor) < $publishedLimit;
	}

	public function reachedModuleLimitMessage(User $instructor): string
	{
		$publishedLimit = $this->getPublishedModuleLimit($instructor);

		if ($publishedLimit === null) {
			return 'You have reached your plan limit. Upgrade your instructor subscription to create more modules.';
		}

		return "You have reached your plan limit ({$publishedLimit} modules). Upgrade your instructor subscription to create more modules.";
	}

	public function getLearnerCapForModule(User $instructor, string $moduleAccessType): ?int
	{
		$featureKey = strtolower($moduleAccessType) === 'paid'
			? SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE
			: SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE;

		return $this->resolveQuotaCapability($instructor, $featureKey);
	}

	public function canPublishPaidModules(User $instructor): bool
	{
		return $this->resolveBooleanCapability($instructor, SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES);
	}

	public function canReceivePaidEnrollments(User $instructor): bool
	{
		return $this->resolveBooleanCapability($instructor, SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS);
	}

	public function canViewEarnings(User $instructor): bool
	{
		return $this->resolveBooleanCapability($instructor, SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getUsageSnapshot(User $instructor): array
	{
		$effectivePlan = $this->resolveEffectivePlan($instructor);

		$publishedModulesCount = $this->getPublishedModuleCount($instructor);

		$publishedModulesLimit = $this->getPublishedModuleLimit($instructor);
		$publishedModulesRemaining = $publishedModulesLimit === null
			? null
			: max(0, $publishedModulesLimit - $publishedModulesCount);

		return [
			'rollout_mode' => $this->rolloutMode(),
			'is_strict_rollout_mode' => $this->isStrictRolloutMode(),
			'effective_plan' => $effectivePlan ? [
				'id' => $effectivePlan->id,
				'name' => $effectivePlan->name,
				'slug' => $effectivePlan->slug,
				'price' => (float) ($effectivePlan->price ?? 0),
				'plan_audience' => $effectivePlan->plan_audience,
			] : null,
			'published_modules_count' => $publishedModulesCount,
			'published_modules_limit' => $publishedModulesLimit,
			'published_modules_remaining' => $publishedModulesRemaining,
			'free_module_learner_cap' => $this->getLearnerCapForModule($instructor, 'free'),
			'paid_module_learner_cap' => $this->getLearnerCapForModule($instructor, 'paid'),
			'can_publish_paid_modules' => $this->canPublishPaidModules($instructor),
			'can_receive_paid_enrollments' => $this->canReceivePaidEnrollments($instructor),
			'can_view_earnings' => $this->canViewEarnings($instructor),
		];
	}

	private function resolveBooleanCapability(User $instructor, string $featureKey): bool
	{
		$effectivePlan = $this->resolveEffectivePlan($instructor);

		if (!$effectivePlan) {
			return false;
		}

		$entitlement = $this->resolvePlanEntitlement($effectivePlan, $featureKey);
		if ($entitlement) {
			if (!$entitlement->is_enabled) {
				return false;
			}

			if ($entitlement->is_unlimited) {
				return true;
			}

			if ((string) ($entitlement->feature?->value_type ?? '') === 'quota') {
				return (int) ($entitlement->quota_value ?? 0) > 0;
			}

			return true;
		}

		$legacyResolved = $this->resolveLegacyBooleanCapability($effectivePlan, $featureKey);

		if ($legacyResolved || !$this->isBaselinePlan($effectivePlan)) {
			return $legacyResolved;
		}

		return false;
	}

	private function resolveQuotaCapability(User $instructor, string $featureKey): ?int
	{
		$effectivePlan = $this->resolveEffectivePlan($instructor);
		if (!$effectivePlan) {
			return null;
		}

		$entitlement = $this->resolvePlanEntitlement($effectivePlan, $featureKey);
		if ($entitlement) {
			if (!$entitlement->is_enabled || $entitlement->is_unlimited) {
				return null;
			}

			if ((string) ($entitlement->feature?->value_type ?? '') !== 'quota') {
				return null;
			}

			return max(0, (int) ($entitlement->quota_value ?? 0));
		}

		$legacyResolved = $this->resolveLegacyQuotaCapability($effectivePlan, $featureKey);

		if ($legacyResolved !== null || !$this->isBaselinePlan($effectivePlan)) {
			return $legacyResolved;
		}

		return null;
	}

	private function resolveBaselineQuotaCapability(?SubscriptionPlan $baselinePlan, string $featureKey): ?int
	{
		if (!$baselinePlan) {
			return null;
		}

		$entitlement = $this->resolvePlanEntitlement($baselinePlan, $featureKey);
		if ($entitlement && $entitlement->is_enabled && !$entitlement->is_unlimited && (string) ($entitlement->feature?->value_type ?? '') === 'quota') {
			return max(0, (int) ($entitlement->quota_value ?? 0));
		}

		$legacyResolved = $this->resolveLegacyQuotaCapability($baselinePlan, $featureKey);
 		if ($legacyResolved !== null) {
			return $legacyResolved;
		}

		return null;
	}

	private function resolveBaselineBooleanCapability(?SubscriptionPlan $baselinePlan, string $featureKey): bool
	{
		if (!$baselinePlan) {
			return false;
		}

		$entitlement = $this->resolvePlanEntitlement($baselinePlan, $featureKey);
		if ($entitlement) {
			if (!$entitlement->is_enabled) {
				return false;
			}

			if ($entitlement->is_unlimited) {
				return true;
			}

			if ((string) ($entitlement->feature?->value_type ?? '') === 'quota') {
				return (int) ($entitlement->quota_value ?? 0) > 0;
			}

			return true;
		}

		$legacyResolved = $this->resolveLegacyBooleanCapability($baselinePlan, $featureKey);
		if ($legacyResolved) {
			return true;
		}

		return false;
	}

	private function isBaselinePlan(SubscriptionPlan $plan): bool
	{
		return (string) ($plan->plan_audience ?? '') === 'instructor'
			&& (float) ($plan->price ?? 0) <= 0;
	}

	private function resolvePlanEntitlement(SubscriptionPlan $plan, string $featureKey): ?PlanFeatureEntitlement
	{
		$featureIds = FeatureCatalog::query()
			->whereIn('key', $this->featureAliases($featureKey))
			->where('is_active', true)
			->pluck('id');

		if ($featureIds->isEmpty()) {
			return null;
		}

		return PlanFeatureEntitlement::query()
			->with('feature')
			->where('plan_id', $plan->id)
			->whereIn('feature_id', $featureIds)
			->orderByDesc('is_enabled')
			->orderByDesc('is_unlimited')
			->orderByDesc('quota_value')
			->first();
	}

	private function resolveLegacyBooleanCapability(SubscriptionPlan $plan, string $featureKey): bool
	{
		foreach ($this->featureAliases($featureKey) as $aliasKey) {
			if ($plan->hasFeature($aliasKey)) {
				return true;
			}
		}

		return false;
	}

	private function resolveLegacyQuotaCapability(SubscriptionPlan $plan, string $featureKey): ?int
	{
		foreach ($this->featureAliases($featureKey) as $aliasKey) {
			if (!$plan->hasFeature($aliasKey)) {
				continue;
			}

			$value = $plan->getFeatureValue($aliasKey);

			if ($value === 'unlimited') {
				return null;
			}

			if (is_numeric($value)) {
				return max(0, (int) $value);
			}
		}

		return null;
	}

	/**
	 * @return array<int, string>
	 */
	private function featureAliases(string $featureKey): array
	{
		$aliases = [
			SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT => [
				SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT,
				'max_published_modules',
				'published_modules_limit',
			],
			SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE => [
				SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE,
				'max_learners_per_free_module',
				'free_module_learner_cap',
			],
			SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE => [
				SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE,
				'max_learners_per_paid_module',
				'paid_module_learner_cap',
			],
			SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES => [
				SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES,
				'can_publish_paid_module',
				'publish_paid_modules',
			],
			SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS => [
				SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS,
				'can_receive_paid_enrollments',
				'receive_paid_enrollments',
			],
			SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS => [
				SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS,
				'can_view_earnings',
				'view_earnings',
			],
		];

		return $aliases[$featureKey] ?? [$featureKey];
	}
}
