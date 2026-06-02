<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\SubscriptionPlan;

class ConnectorEntitlementService
{
    public function activePlan(Connector $connector): ?SubscriptionPlan
    {
        $subscription = $connector->activeSubscription()
            ->with('plan.featureEntitlements.feature')
            ->first();

        return $subscription?->plan()->with('featureEntitlements.feature')->first();
    }

    public function hasEntitlement(Connector $connector, string $featureKey): bool
    {
        $plan = $this->activePlan($connector);

        if (! $plan) {
            return false;
        }

        if ($plan->hasFeature($featureKey)) {
            return true;
        }

        return $plan->featureEntitlements()
            ->where('is_enabled', true)
            ->whereHas('feature', fn ($query) => $query->where('key', $featureKey))
            ->exists();
    }

    public function enabledKeys(Connector $connector): array
    {
        $plan = $this->activePlan($connector);

        if (! $plan) {
            return [];
        }

        $legacy = is_array($plan->features) ? $plan->features : [];
        $normalized = $plan->featureEntitlements()
            ->where('is_enabled', true)
            ->with('feature')
            ->get()
            ->pluck('feature.key')
            ->filter()
            ->all();

        return array_values(array_unique(array_merge($legacy, $normalized)));
    }
}
