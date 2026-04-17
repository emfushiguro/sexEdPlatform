<?php

namespace App\Services\Instructor;

use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Support\SubscriptionFeatureKeys;
use Illuminate\Support\Collection;

class InstructorSubscriptionPresentationService
{
    /**
     * @var array<string, array<string, string>>
     */
    private const CANONICAL_FEATURES = [
        SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT => [
            'type' => 'quota',
            'comparison_label' => 'Module publishing limit',
            'card_label_singular' => 'Publish up to :value module',
            'card_label_plural' => 'Publish up to :value modules',
            'card_label_unlimited' => 'Publish unlimited modules',
            'cell_label_singular' => ':value module',
            'cell_label_plural' => ':value modules',
            'cell_label_unlimited' => 'Unlimited',
        ],
        SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE => [
            'type' => 'quota',
            'comparison_label' => 'Learner cap per free module',
            'card_label_singular' => 'Accept up to :value learner per free module',
            'card_label_plural' => 'Accept up to :value learners per free module',
            'card_label_unlimited' => 'Unlimited learners per free module',
            'cell_label_singular' => ':value learner',
            'cell_label_plural' => ':value learners',
            'cell_label_unlimited' => 'Unlimited',
        ],
        SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE => [
            'type' => 'quota',
            'comparison_label' => 'Learner cap per paid module',
            'card_label_singular' => 'Accept up to :value learner per paid module',
            'card_label_plural' => 'Accept up to :value learners per paid module',
            'card_label_unlimited' => 'Unlimited learners per paid module',
            'cell_label_singular' => ':value learner',
            'cell_label_plural' => ':value learners',
            'cell_label_unlimited' => 'Unlimited',
        ],
        SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES => [
            'type' => 'boolean',
            'comparison_label' => 'Publish paid modules',
            'card_label' => 'Publish paid modules',
        ],
        SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS => [
            'type' => 'boolean',
            'comparison_label' => 'Receive paid enrollments',
            'card_label' => 'Receive paid enrollments',
        ],
        SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS => [
            'type' => 'boolean',
            'comparison_label' => 'View earnings dashboard',
            'card_label' => 'View earnings dashboard',
        ],
    ];

    /**
     * @param  Collection<int, SubscriptionPlan>  $plans
     * @param  array<string, mixed>  $baselineSnapshot
     * @return array<string, mixed>
     */
    public function buildPagePayload(Collection $plans, array $baselineSnapshot, ?Subscription $currentSubscription): array
    {
        $currentStatus = strtolower((string) data_get($currentSubscription, 'status.value', data_get($currentSubscription, 'status', '')));
        $activeWindowStatuses = ['active', 'grace_period', 'scheduled_cancel'];

        $currentPlanId = in_array($currentStatus, $activeWindowStatuses, true)
            ? (int) ($currentSubscription?->plan_id ?? 0)
            : 0;

        $pendingPlanId = $currentStatus === 'pending'
            ? (int) ($currentSubscription?->plan_id ?? 0)
            : 0;

        $baselinePlan = $plans
            ->first(fn (SubscriptionPlan $plan) => !$this->isPaidPlan($plan));

        $paidPlans = $plans
            ->filter(fn (SubscriptionPlan $plan) => $this->isPaidPlan($plan))
            ->values();

        $baselinePlanCard = $baselinePlan
            ? $this->buildPlanCard($baselinePlan, true, $currentPlanId, $pendingPlanId)
            : $this->buildBaselineSnapshotCard($baselineSnapshot, $currentPlanId === 0);

        $paidPlanCards = $paidPlans
            ->map(fn (SubscriptionPlan $plan) => $this->buildPlanCard($plan, false, $currentPlanId, $pendingPlanId))
            ->values()
            ->all();

        $comparisonRows = $this->buildComparisonRows(array_merge([$baselinePlanCard], $paidPlanCards));

        return [
            'baseline_plan_card' => $baselinePlanCard,
            'paid_plan_cards' => $paidPlanCards,
            'comparison_rows' => $comparisonRows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPlanCard(SubscriptionPlan $plan, bool $isBaseline, int $currentPlanId, int $pendingPlanId): array
    {
        $entitlements = $this->mapEntitlementsByKey($plan);
        $featureKeys = $this->orderedFeatureKeys(array_keys($entitlements));

        $features = [];
        $featureLookup = [];

        foreach ($featureKeys as $featureKey) {
            $featureRow = $this->buildFeatureRow($featureKey, $entitlements[$featureKey] ?? null);
            $features[] = $featureRow;
            $featureLookup[$featureKey] = [
                'label' => $featureRow['comparison_label'],
                'cell_label' => $featureRow['cell_label'],
                'included' => $featureRow['included'],
            ];
        }

        return [
            'id' => (int) $plan->id,
            'name' => (string) $plan->name,
            'description' => (string) ($plan->description ?? ''),
            'price_display' => number_format($this->resolvePlanDisplayAmount($plan), 2),
            'is_baseline' => $isBaseline,
            'is_current' => $currentPlanId > 0 && $currentPlanId === (int) $plan->id,
            'is_pending_selection' => $pendingPlanId > 0 && $pendingPlanId === (int) $plan->id,
            'is_checkout_eligible' => !$isBaseline,
            'features' => $features,
            'feature_lookup' => $featureLookup,
        ];
    }

    /**
     * @param  array<string, mixed>  $baselineSnapshot
     * @return array<string, mixed>
     */
    private function buildBaselineSnapshotCard(array $baselineSnapshot, bool $isCurrent): array
    {
        $entitlements = [
            SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT => [
                'is_enabled' => true,
                'is_unlimited' => data_get($baselineSnapshot, 'published_modules_limit') === null,
                'quota_value' => data_get($baselineSnapshot, 'published_modules_limit'),
                'value_type' => 'quota',
                'feature_name' => 'Published Modules Limit',
            ],
            SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE => [
                'is_enabled' => true,
                'is_unlimited' => data_get($baselineSnapshot, 'free_module_learner_cap') === null,
                'quota_value' => data_get($baselineSnapshot, 'free_module_learner_cap'),
                'value_type' => 'quota',
                'feature_name' => 'Free Module Learner Cap',
            ],
            SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE => [
                'is_enabled' => true,
                'is_unlimited' => data_get($baselineSnapshot, 'paid_module_learner_cap') === null,
                'quota_value' => data_get($baselineSnapshot, 'paid_module_learner_cap'),
                'value_type' => 'quota',
                'feature_name' => 'Paid Module Learner Cap',
            ],
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES => [
                'is_enabled' => (bool) data_get($baselineSnapshot, 'can_publish_paid_modules', false),
                'is_unlimited' => false,
                'quota_value' => null,
                'value_type' => 'boolean',
                'feature_name' => 'Can Publish Paid Modules',
            ],
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS => [
                'is_enabled' => (bool) data_get($baselineSnapshot, 'can_receive_paid_enrollments', false),
                'is_unlimited' => false,
                'quota_value' => null,
                'value_type' => 'boolean',
                'feature_name' => 'Can Receive Paid Enrollments',
            ],
            SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS => [
                'is_enabled' => (bool) data_get($baselineSnapshot, 'can_view_earnings', false),
                'is_unlimited' => false,
                'quota_value' => null,
                'value_type' => 'boolean',
                'feature_name' => 'Can View Earnings',
            ],
        ];

        $features = [];
        $featureLookup = [];

        foreach ($this->orderedFeatureKeys(array_keys($entitlements)) as $featureKey) {
            $featureRow = $this->buildFeatureRow($featureKey, $entitlements[$featureKey] ?? null);
            $features[] = $featureRow;
            $featureLookup[$featureKey] = [
                'label' => $featureRow['comparison_label'],
                'cell_label' => $featureRow['cell_label'],
                'included' => $featureRow['included'],
            ];
        }

        return [
            'id' => (int) data_get($baselineSnapshot, 'plan.id', 0),
            'name' => (string) data_get($baselineSnapshot, 'plan.name', 'Free Plan'),
            'description' => 'Default instructor access without premium checkout.',
            'price_display' => '0.00',
            'is_baseline' => true,
            'is_current' => $isCurrent,
            'is_pending_selection' => false,
            'is_checkout_eligible' => false,
            'features' => $features,
            'feature_lookup' => $featureLookup,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function mapEntitlementsByKey(SubscriptionPlan $plan): array
    {
        $mapped = [];

        foreach (($plan->featureEntitlements ?? []) as $entitlement) {
            if (!$entitlement instanceof PlanFeatureEntitlement) {
                continue;
            }

            $feature = $entitlement->feature;
            $featureKey = trim((string) ($feature?->key ?? ''));

            if ($featureKey === '') {
                continue;
            }

            $mapped[$featureKey] = [
                'is_enabled' => (bool) ($entitlement->is_enabled ?? false),
                'is_unlimited' => (bool) ($entitlement->is_unlimited ?? false),
                'quota_value' => $entitlement->quota_value,
                'value_type' => (string) ($feature?->value_type ?? 'boolean'),
                'feature_name' => (string) ($feature?->name ?? $this->humanizeFeatureKey($featureKey)),
                'unit_label' => (string) ($feature?->unit_label ?? ''),
            ];
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>|null  $entitlement
     * @return array<string, mixed>
     */
    private function buildFeatureRow(string $featureKey, ?array $entitlement): array
    {
        $meta = self::CANONICAL_FEATURES[$featureKey] ?? null;

        if ($meta && ($meta['type'] ?? null) === 'quota') {
            return $this->buildQuotaFeatureRow($featureKey, $meta, $entitlement);
        }

        if ($meta && ($meta['type'] ?? null) === 'boolean') {
            return $this->buildBooleanFeatureRow($featureKey, $meta, $entitlement);
        }

        return $this->buildGenericFeatureRow($featureKey, $entitlement);
    }

    /**
     * @param  array<string, string>  $meta
     * @param  array<string, mixed>|null  $entitlement
     * @return array<string, mixed>
     */
    private function buildQuotaFeatureRow(string $featureKey, array $meta, ?array $entitlement): array
    {
        $isEnabled = (bool) ($entitlement['is_enabled'] ?? false);
        $isUnlimited = $isEnabled && (bool) ($entitlement['is_unlimited'] ?? false);
        $quotaValue = $isEnabled && !$isUnlimited ? (int) ($entitlement['quota_value'] ?? 0) : null;

        if ($isUnlimited) {
            return [
                'key' => $featureKey,
                'label' => (string) ($meta['card_label_unlimited'] ?? $meta['comparison_label']),
                'comparison_label' => (string) ($meta['comparison_label'] ?? $this->humanizeFeatureKey($featureKey)),
                'cell_label' => (string) ($meta['cell_label_unlimited'] ?? 'Unlimited'),
                'included' => true,
            ];
        }

        if ($quotaValue !== null && $quotaValue > 0) {
            $isSingular = $quotaValue === 1;
            $cardTemplate = $isSingular
                ? (string) ($meta['card_label_singular'] ?? $meta['comparison_label'])
                : (string) ($meta['card_label_plural'] ?? $meta['comparison_label']);
            $cellTemplate = $isSingular
                ? (string) ($meta['cell_label_singular'] ?? ':value')
                : (string) ($meta['cell_label_plural'] ?? ':value');

            return [
                'key' => $featureKey,
                'label' => str_replace(':value', (string) $quotaValue, $cardTemplate),
                'comparison_label' => (string) ($meta['comparison_label'] ?? $this->humanizeFeatureKey($featureKey)),
                'cell_label' => str_replace(':value', (string) $quotaValue, $cellTemplate),
                'included' => true,
            ];
        }

        return [
            'key' => $featureKey,
            'label' => (string) ($meta['comparison_label'] ?? $this->humanizeFeatureKey($featureKey)),
            'comparison_label' => (string) ($meta['comparison_label'] ?? $this->humanizeFeatureKey($featureKey)),
            'cell_label' => 'Not included',
            'included' => false,
        ];
    }

    /**
     * @param  array<string, string>  $meta
     * @param  array<string, mixed>|null  $entitlement
     * @return array<string, mixed>
     */
    private function buildBooleanFeatureRow(string $featureKey, array $meta, ?array $entitlement): array
    {
        $isEnabled = (bool) ($entitlement['is_enabled'] ?? false);
        $label = (string) ($meta['card_label'] ?? $meta['comparison_label'] ?? $this->humanizeFeatureKey($featureKey));

        return [
            'key' => $featureKey,
            'label' => $label,
            'comparison_label' => (string) ($meta['comparison_label'] ?? $label),
            'cell_label' => $isEnabled ? 'Included' : 'Not included',
            'included' => $isEnabled,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $entitlement
     * @return array<string, mixed>
     */
    private function buildGenericFeatureRow(string $featureKey, ?array $entitlement): array
    {
        $featureName = (string) ($entitlement['feature_name'] ?? $this->humanizeFeatureKey($featureKey));
        $isEnabled = (bool) ($entitlement['is_enabled'] ?? false);
        $isUnlimited = $isEnabled && (bool) ($entitlement['is_unlimited'] ?? false);
        $valueType = (string) ($entitlement['value_type'] ?? 'boolean');

        if ($isUnlimited) {
            return [
                'key' => $featureKey,
                'label' => $featureName . ' (Unlimited)',
                'comparison_label' => $featureName,
                'cell_label' => 'Unlimited',
                'included' => true,
            ];
        }

        if ($isEnabled && $valueType === 'quota') {
            $quotaValue = max(0, (int) ($entitlement['quota_value'] ?? 0));
            $unit = trim((string) ($entitlement['unit_label'] ?? ''));
            $suffix = $unit !== '' ? (' ' . $unit) : '';

            return [
                'key' => $featureKey,
                'label' => $featureName . ': up to ' . $quotaValue . $suffix,
                'comparison_label' => $featureName,
                'cell_label' => $quotaValue . $suffix,
                'included' => $quotaValue > 0,
            ];
        }

        return [
            'key' => $featureKey,
            'label' => $featureName,
            'comparison_label' => $featureName,
            'cell_label' => $isEnabled ? 'Included' : 'Not included',
            'included' => $isEnabled,
        ];
    }

    /**
     * @param  array<int, string>  $planFeatureKeys
     * @return array<int, string>
     */
    private function orderedFeatureKeys(array $planFeatureKeys): array
    {
        $canonicalOrder = array_keys(self::CANONICAL_FEATURES);
        $canonicalSet = array_flip($canonicalOrder);

        $extraKeys = array_values(array_filter($planFeatureKeys, fn (string $key) => !isset($canonicalSet[$key])));
        sort($extraKeys);

        return array_values(array_unique(array_merge($canonicalOrder, $extraKeys)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, array<string, string>>
     */
    private function buildComparisonRows(array $cards): array
    {
        $rows = [];

        foreach ($cards as $card) {
            foreach ((array) ($card['feature_lookup'] ?? []) as $featureKey => $featureLookup) {
                if (!isset($rows[$featureKey])) {
                    $rows[$featureKey] = [
                        'key' => (string) $featureKey,
                        'label' => (string) ($featureLookup['label'] ?? $this->humanizeFeatureKey((string) $featureKey)),
                    ];
                }
            }
        }

        $ordered = [];

        foreach (array_keys(self::CANONICAL_FEATURES) as $canonicalKey) {
            if (isset($rows[$canonicalKey])) {
                $ordered[$canonicalKey] = $rows[$canonicalKey];
                unset($rows[$canonicalKey]);
            }
        }

        if (!empty($rows)) {
            uasort($rows, function (array $a, array $b) {
                return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
            });

            foreach ($rows as $key => $row) {
                $ordered[$key] = $row;
            }
        }

        return array_values($ordered);
    }

    private function isPaidPlan(SubscriptionPlan $plan): bool
    {
        return $this->resolvePlanDisplayAmount($plan) > 0;
    }

    private function resolvePlanDisplayAmount(SubscriptionPlan $plan): float
    {
        $plan->loadMissing('planPrices');

        $defaultPrice = $plan->planPrices
            ->first(fn ($price) => (bool) ($price->is_active ?? false) && (bool) ($price->is_default ?? false));

        if ($defaultPrice) {
            return round(((int) ($defaultPrice->amount_minor ?? 0)) / 100, 2);
        }

        $firstPaidActivePrice = $plan->planPrices
            ->first(fn ($price) => (bool) ($price->is_active ?? false) && (int) ($price->amount_minor ?? 0) > 0);

        if ($firstPaidActivePrice) {
            return round(((int) ($firstPaidActivePrice->amount_minor ?? 0)) / 100, 2);
        }

        return round((float) ($plan->price ?? 0), 2);
    }

    private function humanizeFeatureKey(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', trim($key)));
    }
}
