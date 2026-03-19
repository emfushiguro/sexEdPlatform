<?php

namespace Database\Seeders;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionNormalizationSeeder extends Seeder
{
    public function run(): void
    {
        $featureLabels = config('subscription_features.labels', []);
        $this->seedFeatureCatalog($featureLabels);
        $this->seedPlanPricesAndEntitlements($featureLabels);
        $this->backfillSubscriptions();
    }

    private function seedFeatureCatalog(array $featureLabels): void
    {
        foreach ($featureLabels as $key => $label) {
            FeatureCatalog::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $label,
                    'description' => null,
                    'value_type' => 'boolean',
                    'unit_label' => null,
                    'category' => 'general',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedPlanPricesAndEntitlements(array $featureLabels): void
    {
        $featureKeys = array_keys($featureLabels);

        SubscriptionPlan::query()
            ->with('planPrices')
            ->chunkById(50, function ($plans) use ($featureKeys) {
                foreach ($plans as $plan) {
                    if ($plan->planPrices->isEmpty()) {
                        $this->createDefaultPlanPrice($plan);
                    }

                    $featureKeysForPlan = $this->extractFeatureKeys((array) $plan->features);
                    foreach ($featureKeysForPlan as $featureKey) {
                        if (!in_array($featureKey, $featureKeys, true)) {
                            continue;
                        }

                        $feature = FeatureCatalog::query()->where('key', $featureKey)->first();
                        if (!$feature) {
                            continue;
                        }

                        PlanFeatureEntitlement::firstOrCreate(
                            ['plan_id' => $plan->id, 'feature_id' => $feature->id],
                            [
                                'is_enabled' => true,
                                'quota_value' => $this->resolveQuotaValue($plan->features, $featureKey),
                                'is_unlimited' => $this->resolveUnlimitedFlag($plan->features, $featureKey),
                            ]
                        );
                    }
                }
            });
    }

    private function createDefaultPlanPrice(SubscriptionPlan $plan): void
    {
        $name = strtolower((string) $plan->name);
        $slug = strtolower((string) $plan->slug);
        $isAnnual = str_contains($name, 'annual') || str_contains($name, 'year') || str_contains($slug, 'annual');
        $isMonthly = str_contains($name, 'month') || str_contains($slug, 'monthly');

        $durationUnit = $isAnnual ? 'year' : 'month';
        $durationLabel = $isAnnual ? 'Yearly' : 'Monthly';

        if (!$isAnnual && !$isMonthly) {
            $durationUnit = 'month';
            $durationLabel = 'Monthly';
        }

        PlanPrice::create([
            'plan_id' => $plan->id,
            'duration_mode' => 'preset',
            'duration_unit' => $durationUnit,
            'duration_count' => 1,
            'duration_label' => $durationLabel,
            'amount_minor' => (int) round(((float) $plan->price) * 100),
            'currency' => 'PHP',
            'compare_at_minor' => null,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function backfillSubscriptions(): void
    {
        Subscription::query()
            ->whereNull('plan_price_id')
            ->chunkById(50, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    $plan = $subscription->plan()->with('planPrices')->first();
                    $planPrice = $plan?->planPrices
                        ?->firstWhere('is_default', true)
                        ?? $plan?->planPrices?->first();

                    $updates = [];

                    if ($planPrice) {
                        $updates['plan_price_id'] = $planPrice->id;
                    }

                    if (!$subscription->starts_at && $subscription->start_date) {
                        $updates['starts_at'] = $subscription->start_date;
                    }

                    if (!$subscription->ends_at && $subscription->end_date) {
                        $updates['ends_at'] = $subscription->end_date;
                    }

                    if (!$subscription->grace_ends_at && $subscription->grace_period_ends) {
                        $updates['grace_ends_at'] = $subscription->grace_period_ends;
                    }

                    if (!$subscription->canceled_at && $subscription->cancelled_at) {
                        $updates['canceled_at'] = $subscription->cancelled_at;
                    }

                    if (!empty($updates)) {
                        $subscription->forceFill($updates)->save();
                    }
                }
            });
    }

    private function extractFeatureKeys(array $features): array
    {
        $keys = [];

        foreach ($features as $group => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $childValue) {
                    if ($this->isFeatureEnabledValue($childValue)) {
                        $keys[] = $key;
                    }
                }
                continue;
            }

            if (is_int($group) && is_string($value)) {
                $keys[] = $value;
                continue;
            }

            if (is_string($group) && $this->isFeatureEnabledValue($value)) {
                $keys[] = $group;
            }
        }

        return array_values(array_unique($keys));
    }

    private function isFeatureEnabledValue(mixed $value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }

        if (is_numeric($value) && (int) $value > 0) {
            return true;
        }

        if (is_string($value)) {
            return in_array($value, ['unlimited', 'full', 'advanced', 'priority', 'limited'], true);
        }

        return false;
    }

    private function resolveQuotaValue(mixed $features, string $featureKey): ?int
    {
        $value = $this->findFeatureValue($features, $featureKey);

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function resolveUnlimitedFlag(mixed $features, string $featureKey): bool
    {
        $value = $this->findFeatureValue($features, $featureKey);

        if (is_string($value)) {
            return in_array($value, ['unlimited', 'full'], true);
        }

        return false;
    }

    private function findFeatureValue(mixed $features, string $featureKey): mixed
    {
        if (!is_array($features)) {
            return null;
        }

        if (array_key_exists($featureKey, $features)) {
            return $features[$featureKey];
        }

        foreach ($features as $value) {
            if (is_array($value)) {
                $found = $this->findFeatureValue($value, $featureKey);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
