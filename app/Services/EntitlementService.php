<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\User;

class EntitlementService
{
    public function canAccessFeature(User $user, string $featureKey): bool
    {
        $entitlement = $this->resolveEntitlement($user, $featureKey);

        if (!$entitlement || !$entitlement->is_enabled) {
            return false;
        }

        if ($entitlement->is_unlimited) {
            return true;
        }

        $feature = $entitlement->feature;
        if ($feature?->value_type === 'quota') {
            return (int) ($entitlement->quota_value ?? 0) > 0;
        }

        return true;
    }

    public function getFeatureQuota(User $user, string $featureKey): ?int
    {
        $entitlement = $this->resolveEntitlement($user, $featureKey);

        if (!$entitlement || !$entitlement->is_enabled) {
            return null;
        }

        if ($entitlement->is_unlimited) {
            return null;
        }

        if ($entitlement->feature?->value_type !== 'quota') {
            return null;
        }

        return $entitlement->quota_value;
    }

    public function getSubscriptionSummary(User $user): array
    {
        $subscription = $this->getEligibleSubscription($user);

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'status' => null,
                'plan_id' => null,
                'plan_price_id' => null,
                'ends_at' => null,
            ];
        }

        return [
            'has_subscription' => true,
            'status' => (string) $subscription->status->value,
            'plan_id' => $subscription->plan_id,
            'plan_price_id' => $subscription->plan_price_id,
            'ends_at' => $subscription->ends_at ?? $subscription->end_date,
        ];
    }

    private function resolveEntitlement(User $user, string $featureKey): ?PlanFeatureEntitlement
    {
        $subscription = $this->getEligibleSubscription($user);

        if (!$subscription || !$subscription->plan_id) {
            return null;
        }

        $feature = FeatureCatalog::query()
            ->where('key', $featureKey)
            ->where('is_active', true)
            ->first();

        if (!$feature) {
            return null;
        }

        return PlanFeatureEntitlement::query()
            ->with('feature')
            ->where('plan_id', $subscription->plan_id)
            ->where('feature_id', $feature->id)
            ->first();
    }

    private function getEligibleSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active->value, 'grace_period'])
            ->latest('id')
            ->first();
    }
}
