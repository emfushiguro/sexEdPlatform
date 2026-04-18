<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\GamificationPolicy;
use App\Models\LearnerProfile;
use App\Models\User;
use App\Models\UserDailyShield;
use App\Services\Gamification\GamificationPolicyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerGamificationDashboardDynamicViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_dashboard_renders_dynamic_xp_shield_and_refill_cost_values(): void
    {
        $this->activatePolicy($this->customPolicyPayload());

        $learner = $this->createLearner(score: 150, level: 1, streakSavers: 1);
        UserDailyShield::todayForUser($learner)->update(['shields_remaining' => 0]);

        $response = $this->actingAs($learner)
            ->get(route('learner.dashboard'));

        $response->assertOk();
        $response->assertSee('150/300 XP', false);
        $response->assertSee('0/5', false);
        $response->assertSee('Buy Saver — ⭐ 42', false);
        $response->assertSee('⭐ 21 pts', false);
        $response->assertSee('⭐ 84 pts', false);
        $response->assertSee('Full Refill (5 Shields)', false);
    }

    public function test_streak_saver_button_uses_dynamic_threshold_copy_when_points_are_insufficient(): void
    {
        $this->activatePolicy($this->customPolicyPayload());

        $learner = $this->createLearner(score: 30, level: 1, streakSavers: 1);

        $response = $this->actingAs($learner)
            ->get(route('learner.dashboard'));

        $response->assertOk();
        $response->assertSee('1/4', false);
        $response->assertSee('Not enough points (need 42)', false);
    }

    private function customPolicyPayload(): array
    {
        return [
            'points_config' => [
                'quiz_bands' => [
                    'perfect_score_points' => 30,
                    'pass_score_points' => 25,
                    'fail_attempt_points' => 5,
                ],
            ],
            'streak_config' => [
                'max_savers_held' => 4,
                'saver_purchase_cost_points' => 42,
                'milestones' => [
                    ['days' => 7, 'bonus_points' => 50, 'priority' => 20],
                    ['days' => 30, 'bonus_points' => 200, 'priority' => 10],
                ],
            ],
            'leveling_config' => [
                'threshold_resolution' => 'explicit_then_formula',
                'explicit_thresholds' => [
                    '1' => 0,
                    '2' => 300,
                ],
                'formula' => [
                    'base_xp_per_level' => 100,
                    'growth_mode' => 'linear',
                    'growth_factor' => 1,
                ],
            ],
            'shield_config' => [
                'daily_shields_default' => 5,
                'max_shields_per_day_cap' => 5,
                'refill_single_cost_points' => 21,
                'refill_full_cost_points' => 84,
                'refill_full_target_shields' => 5,
            ],
        ];
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

    private function createLearner(int $score, int $level, int $streakSavers): User
    {
        /** @var User $learner */
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'dynamic_' . $learner->id,
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);

        $learner->gamification()->update([
            'score' => $score,
            'total_points' => $score,
            'level' => $level,
            'streak_savers' => $streakSavers,
        ]);

        return $learner;
    }
}
