<?php

namespace Tests\Unit\Services\Gamification;

use App\Models\GamificationPolicy;
use App\Services\Gamification\GamificationPolicyDefaults;
use App\Services\Gamification\GamificationPolicyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GamificationPolicyResolverTest extends TestCase
{
    use RefreshDatabase;

    private GamificationPolicyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->resolver = app(GamificationPolicyResolver::class);
    }

    public function test_resolver_returns_active_merged_config(): void
    {
        GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => [
                'points_config' => [
                    'topic_complete_points' => 17,
                ],
            ],
        ]);

        $resolved = $this->resolver->resolve();

        $this->assertSame(17, $resolved['points_config']['topic_complete_points']);
        $this->assertArrayHasKey('shield_config', $resolved);
    }

    public function test_resolver_uses_cache_until_cleared(): void
    {
        $policy = GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => [
                'points_config' => [
                    'topic_complete_points' => 10,
                ],
            ],
        ]);

        $first = $this->resolver->resolve();
        $this->assertSame(10, $first['points_config']['topic_complete_points']);

        $policy->update([
            'policy_payload' => [
                'points_config' => [
                    'topic_complete_points' => 22,
                ],
            ],
        ]);

        $cached = $this->resolver->resolve();
        $this->assertSame(10, $cached['points_config']['topic_complete_points']);
    }

    public function test_clear_cache_forces_resolve_refresh(): void
    {
        $policy = GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => [
                'points_config' => [
                    'topic_complete_points' => 10,
                ],
            ],
        ]);

        $this->resolver->resolve();

        $policy->update([
            'policy_payload' => [
                'points_config' => [
                    'topic_complete_points' => 33,
                ],
            ],
        ]);

        $this->resolver->clearCache();

        $refreshed = $this->resolver->resolve();
        $this->assertSame(33, $refreshed['points_config']['topic_complete_points']);
    }

    public function test_invalid_active_policy_falls_back_to_last_known_valid(): void
    {
        $valid = GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => [
                'points_config' => [
                    'topic_complete_points' => 14,
                ],
            ],
        ]);

        $this->assertSame(14, $this->resolver->resolve()['points_config']['topic_complete_points']);

        $valid->update(['is_active' => false]);

        GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => [
                'points_config' => [
                    'topic_complete_points' => -999,
                ],
            ],
        ]);

        $this->resolver->clearCache();

        $fallback = $this->resolver->resolve();
        $this->assertSame(14, $fallback['points_config']['topic_complete_points']);

        $this->assertNotSame(
            GamificationPolicyDefaults::baseline()['points_config']['topic_complete_points'],
            $fallback['points_config']['topic_complete_points']
        );
    }
}
