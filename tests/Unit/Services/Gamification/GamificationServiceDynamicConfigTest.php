<?php

namespace Tests\Unit\Services\Gamification;

use App\Models\GamificationPolicy;
use App\Models\User;
use App\Services\Gamification\GamificationPolicyResolver;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationServiceDynamicConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_calculation_uses_dynamic_hybrid_configuration(): void
    {
        $this->activatePolicy([
            'points_config' => [
                'level_up_bonus_points' => 0,
            ],
            'leveling_config' => [
                'threshold_resolution' => 'explicit_then_formula',
                'explicit_thresholds' => [
                    '1' => 0,
                    '2' => 500,
                ],
                'formula' => [
                    'base_xp_per_level' => 200,
                    'growth_mode' => 'linear',
                    'growth_factor' => 1,
                ],
            ],
        ]);

        $user = $this->userWithGamification();
        $service = app(GamificationService::class);

        $service->awardPoints($user, 'topic_complete', 260);

        $user->gamification->refresh();

        $this->assertSame(2, (int) $user->gamification->level);
    }

    public function test_streak_milestone_bonus_uses_configured_list(): void
    {
        $this->activatePolicy([
            'streak_config' => [
                'milestones' => [
                    ['days' => 5, 'bonus_points' => 40, 'priority' => 1],
                ],
            ],
        ]);

        $user = $this->userWithGamification();
        $user->gamification()->update(['streak_count' => 5]);

        $service = app(GamificationService::class);
        $bonus = $service->checkStreakMilestone($user);

        $this->assertSame(40, $bonus);
    }

    public function test_no_hardcoded_streak_bonus_when_policy_is_provided(): void
    {
        $this->activatePolicy([
            'streak_config' => [
                'milestones' => [
                    ['days' => 4, 'bonus_points' => 10, 'priority' => 1],
                ],
            ],
        ]);

        $user = $this->userWithGamification();
        $user->gamification()->update(['streak_count' => 30]);

        $service = app(GamificationService::class);
        $bonus = $service->checkStreakMilestone($user);

        $this->assertNull($bonus);
    }

    private function activatePolicy(array $payload): void
    {
        GamificationPolicy::query()->update(['is_active' => false]);

        GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => $payload,
            'updated_by' => null,
        ]);

        app(GamificationPolicyResolver::class)->clearCache();
    }

    private function userWithGamification(): User
    {
        $user = User::factory()->create();
        $user->assignRole('learner');

        $user->gamification()->update([
            'level' => 1,
            'score' => 0,
            'total_points' => 0,
            'streak_count' => 0,
            'longest_streak' => 0,
            'streak_savers' => 0,
            'last_act_at' => null,
        ]);

        return $user;
    }
}
