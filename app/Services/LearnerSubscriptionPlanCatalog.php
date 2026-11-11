<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Support\SubscriptionFeatureKeys;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class LearnerSubscriptionPlanCatalog
{
    public function activeLearnerPlans(): Collection
    {
        return SubscriptionPlan::active()
            ->where('plan_audience', 'learner')
            ->ordered()
            ->with([
                'planPrices' => fn ($query) => $query
                    ->orderByDesc('is_default')->orderBy('duration_count'),
                'featureEntitlements.feature',
            ])
            ->get();
    }

    public function priceOptions(SubscriptionPlan $plan): array
    {
        $prices = ($plan->relationLoaded('planPrices')
            ? collect($plan->getRelation('planPrices'))
            : $plan->planPrices()->where('is_active', true)
                ->orderByDesc('is_default')->orderBy('duration_count')->get())
            ->filter(fn ($price) => (bool) $price->is_active)
            ->sortBy([
                ['is_default', 'desc'],
                ['duration_count', 'asc'],
            ])
            ->values()
            ->map(function ($price) {
                $amountMinor = (int) $price->amount_minor;

                return [
                    'id' => $price->id,
                    'label' => $price->duration_label ?: ucfirst((string) ($price->duration_unit ?? 'Plan')),
                    'duration_unit' => $price->duration_unit,
                    'duration_count' => (int) ($price->duration_count ?? 1),
                    'amount_minor' => $amountMinor,
                    'amount_display' => number_format($amountMinor / 100, 2),
                    'currency' => strtoupper((string) ($price->currency ?? 'PHP')),
                    'is_default' => (bool) $price->is_default,
                ];
            })->all();

        if ($prices !== [] || $plan->planPrices()->exists() || (float) $plan->price <= 0) {
            return $prices;
        }

        [$durationUnit, $durationCount, $durationLabel] = $this->legacyDuration($plan);

        return [[
            'id' => 'legacy-plan-'.$plan->id,
            'label' => $durationLabel,
            'duration_unit' => $durationUnit,
            'duration_count' => $durationCount,
            'amount_minor' => (int) round(((float) $plan->price) * 100),
            'amount_display' => number_format((float) $plan->price, 2),
            'currency' => 'PHP',
            'is_default' => true,
        ]];
    }

    public function effectivePrice(SubscriptionPlan $plan): ?array
    {
        $price = collect($this->priceOptions($plan))->firstWhere('is_default', true)
            ?? $this->priceOptions($plan)[0] ?? null;

        if (! $price || (int) $price['amount_minor'] <= 0 || $price['currency'] !== 'PHP'
            || (int) $price['duration_count'] < 1) {
            return null;
        }

        return $price;
    }

    public function isAvailable(SubscriptionPlan $plan, ?CarbonInterface $date = null): bool
    {
        $date ??= now();
        $day = $date->copy()->startOfDay();

        return (! $plan->availability_starts_on || ! $day->lt($plan->availability_starts_on->copy()->startOfDay()))
            && (! $plan->availability_ends_on || ! $day->gt($plan->availability_ends_on->copy()->endOfDay()));
    }

    public function featureLabels(SubscriptionPlan $plan): array
    {
        $labelMap = config('subscription_features.labels', []);
        $hiddenKeys = config('subscription_features.hidden', ['test_mode', 'duration_minutes']);
        $labels = [];

        foreach (($plan->featureEntitlements ?? []) as $entitlement) {
            if (! (bool) ($entitlement->is_enabled ?? false)) {
                continue;
            }

            $feature = $entitlement->feature;
            $key = (string) ($feature?->key ?? '');
            if ($key === '' || in_array($key, $hiddenKeys, true)) {
                continue;
            }

            $labels[$key] = $labelMap[$key] ?? ($feature?->name ?: $this->humanize($key));
        }

        if ($labels !== []) {
            return $labels;
        }

        foreach ((array) ($plan->features ?? []) as $groupOrIndex => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $innerValue) {
                    if ($this->featureIsEnabled($innerValue) && ! in_array($key, $hiddenKeys, true)) {
                        $labels[$key] = $labelMap[$key] ?? $this->humanize($key);
                    }
                }
            } elseif (is_string($groupOrIndex) && $this->featureIsEnabled($value)
                && ! in_array($groupOrIndex, $hiddenKeys, true)) {
                $labels[$groupOrIndex] = $labelMap[$groupOrIndex] ?? $this->humanize($groupOrIndex);
            } elseif (is_int($groupOrIndex) && is_string($value) && ! in_array($value, $hiddenKeys, true)) {
                $labels[$value] = $labelMap[$value] ?? $this->humanize($value);
            }
        }

        return $labels;
    }

    public function freeFeatureLabels(): array
    {
        return [
            'free_core_learning' => 'Access to core age-appropriate learning modules',
            'free_quiz_shields' => '3 quiz shields per day',
            'free_username_changes' => 'Username change every 7 days',
            'free_certificate_downloads' => 'View and download completion certificates',
            'free_progress_tracking' => 'Track module progress and quiz completion history',
            'free_upgrade_anytime' => 'Upgrade any time for premium controls',
        ];
    }

    public function prependFreeBaselineCard(array $planCards, ?Subscription $currentSubscription): array
    {
        if (collect($planCards)->contains(fn ($card) => (bool) ($card['is_free'] ?? false))) {
            return $planCards;
        }

        $hasPremiumSubscription = $currentSubscription && $currentSubscription->status === SubscriptionStatus::Active;
        $freeFeatureLabels = $this->freeFeatureLabels();
        $baselineCard = [
            'id' => -1,
            'name' => 'Free Plan',
            'slug' => 'free-baseline',
            'description' => 'Baseline learner access available to all accounts with no checkout required.',
            'is_free' => true,
            'is_current' => ! $hasPremiumSubscription,
            'is_eligible' => false,
            'ineligible_reason' => ! $hasPremiumSubscription ? 'Current baseline access' : 'Premium plan currently active',
            'prices' => [],
            'default_price' => null,
            'feature_labels' => $freeFeatureLabels,
            'feature_keys' => array_keys($freeFeatureLabels),
            'comparison_feature_labels' => $freeFeatureLabels,
            'includes_free_features' => false,
            'visible_feature_count' => count($freeFeatureLabels),
            'is_recommended' => false,
            'is_baseline' => true,
        ];

        return array_values(array_merge([$baselineCard], $planCards));
    }

    public function mergeFeatureLabels(array $primaryLabels, array $secondaryLabels): array
    {
        $normalize = static function ($label): string {
            $label = strtolower(trim((string) $label));

            return preg_replace('/[^a-z0-9]+/', '', $label) ?? $label;
        };
        $output = $primaryLabels;
        $existing = array_fill_keys(array_map($normalize, array_values($primaryLabels)), true);

        foreach ($secondaryLabels as $key => $label) {
            $normalized = $normalize($label);
            if (isset($existing[$normalized])) {
                continue;
            }
            $output[$key] = $label;
            $existing[$normalized] = true;
        }

        return $output;
    }

    /** @return array<int, array<string, mixed>> */
    public function giftCards(?Subscription $currentSubscription = null): array
    {
        $freeFeatureLabels = $this->freeFeatureLabels();
        $cards = collect($this->activeLearnerPlans())->map(function (SubscriptionPlan $plan) use ($currentSubscription, $freeFeatureLabels) {
            $price = $this->effectivePrice($plan);
            $isFree = (float) $plan->price <= 0 && $price === null;
            $isCurrent = $currentSubscription?->plan_id === $plan->id;
            $available = $this->isAvailable($plan);
            $featureLabels = $this->featureLabels($plan);
            $comparisonLabels = $isFree ? $freeFeatureLabels : $this->mergeFeatureLabels($freeFeatureLabels, $featureLabels);

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'is_free' => $isFree,
                'is_current' => $isCurrent,
                'is_eligible' => $currentSubscription === null && ! $isFree && $available && $price !== null,
                'ineligible_reason' => $isCurrent ? 'Current plan' : ($currentSubscription
                    ? 'Available after the current plan expires'
                    : ($isFree ? 'Included by default' : (! $available ? 'Currently unavailable' : ($price ? null : 'No active price configured')))),
                'prices' => $price ? [$price] : [],
                'default_price' => $price,
                'feature_labels' => $featureLabels,
                'feature_keys' => array_keys($comparisonLabels),
                'comparison_feature_labels' => $comparisonLabels,
                'includes_free_features' => false,
                'visible_feature_count' => count($comparisonLabels),
                'is_baseline' => false,
            ];
        })->values();

        $recommendedPlanId = $cards->first(fn ($card) => $card['is_eligible'])['id'] ?? null;

        return $cards->map(function (array $card) use ($recommendedPlanId) {
            $card['is_recommended'] = $recommendedPlanId !== null && $card['id'] === $recommendedPlanId;

            return $card;
        })->all();
    }

    /** @param array<int, array<string, mixed>> $cards */
    public function comparisonFeatures(array $cards): array
    {
        $features = [];
        foreach ($cards as $card) {
            foreach (($card['comparison_feature_labels'] ?? []) as $key => $label) {
                $features[$key] ??= ['key' => $key, 'label' => $label];
            }
        }

        $priority = [
            SubscriptionFeatureKeys::UNLIMITED_USERNAME_CHANGE => 'Unlimited Username Changes',
            SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS => 'Unlimited Quiz Shields',
            SubscriptionFeatureKeys::TEXT_TRANSLATOR => 'Text Translator',
            SubscriptionFeatureKeys::VOICE_SPEECH_TRANSLATOR => 'Voice Speech Translator',
        ];
        foreach ($priority as $key => $label) {
            if (collect($cards)->contains(fn ($card) => in_array($key, $card['feature_keys'] ?? [], true))) {
                $features[$key] ??= ['key' => $key, 'label' => $label];
            }
        }

        return array_values($features);
    }

    private function legacyDuration(SubscriptionPlan $plan): array
    {
        return match (strtolower((string) ($plan->billing_mode ?? 'monthly'))) {
            'annual', 'yearly' => ['year', 1, 'Yearly'],
            default => ['month', 1, 'Monthly'],
        };
    }

    private function featureIsEnabled(mixed $value): bool
    {
        return $value === true || $value === 1 || (is_numeric($value) && (int) $value > 0)
            || (is_string($value) && ! in_array(strtolower($value), ['false', '0', 'off', ''], true));
    }

    private function humanize(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', trim($key)));
    }
}
