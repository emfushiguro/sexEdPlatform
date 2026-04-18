<?php

namespace App\Services\Gamification;

use App\Models\GamificationPolicy;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GamificationPolicyResolver
{
    public const CACHE_KEY = 'gamification.policy.resolved.v1';

    public const LAST_VALID_CACHE_KEY = 'gamification.policy.last_valid.v1';

    public function __construct(
        private readonly GamificationPolicyNormalizer $normalizer,
        private readonly GamificationPolicyValidator $validator,
    ) {
    }

    public function resolve(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            return $this->resolveFresh();
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function resolveFresh(): array
    {
        try {
            $activePolicy = GamificationPolicy::query()
                ->active()
                ->latest('id')
                ->first();

            if (!$activePolicy) {
                $baseline = $this->normalizer->normalize(GamificationPolicyDefaults::baseline());
                Cache::forever(self::LAST_VALID_CACHE_KEY, $baseline);

                return $baseline;
            }

            $payload = is_array($activePolicy->policy_payload)
                ? $activePolicy->policy_payload
                : [];

            $normalized = $this->normalizer->normalize($payload);
            $errors = $this->validator->validate($normalized);

            if (!empty($errors)) {
                return $this->fallbackToLastKnownValid();
            }

            Cache::forever(self::LAST_VALID_CACHE_KEY, $normalized);

            return $normalized;
        } catch (Throwable) {
            return $this->fallbackToLastKnownValid();
        }
    }

    private function fallbackToLastKnownValid(): array
    {
        $lastKnownValid = Cache::get(self::LAST_VALID_CACHE_KEY);

        if (is_array($lastKnownValid)) {
            return $lastKnownValid;
        }

        $baseline = $this->normalizer->normalize(GamificationPolicyDefaults::baseline());
        Cache::forever(self::LAST_VALID_CACHE_KEY, $baseline);

        return $baseline;
    }
}
