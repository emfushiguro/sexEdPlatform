<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;

class EntitlementService
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function canAccessFeature(User $user, string $featureKey): bool
    {
        return $this->subscriptionService->hasFeature($user, $featureKey);
    }

    public function getFeatureQuota(User $user, string $featureKey): ?int
    {
        return $this->subscriptionService->getFeatureQuota($user, $featureKey);
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

    private function getEligibleSubscription(User $user): ?Subscription
    {
        return $this->subscriptionService->getEligibleSubscriptionForEntitlements($user);
    }
}
