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

class GamificationRulesDynamicDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_rules_page_renders_points_costs_caps_and_milestones_from_active_policy(): void
    {
        $this->activatePolicy([
            'points_config' => [
                'topic_complete_points' => 11,
                'lesson_complete_points' => 17,
                'module_complete_points' => 130,
                'certificate_earned_points' => 70,
                'quiz_bands' => [
                    'perfect_score_points' => 41,
                    'pass_score_points' => 32,
                    'fail_attempt_points' => 9,
                ],
            ],
            'streak_config' => [
                'max_savers_held' => 4,
                'saver_purchase_cost_points' => 42,
                'milestones' => [
                    ['days' => 5, 'bonus_points' => 40, 'priority' => 20],
                    ['days' => 10, 'bonus_points' => 120, 'priority' => 10],
                ],
            ],
            'shield_config' => [
                'daily_shields_default' => 5,
                'max_shields_per_day_cap' => 5,
                'refill_single_cost_points' => 21,
                'refill_full_cost_points' => 84,
                'refill_full_target_shields' => 5,
            ],
        ]);

        $learner = $this->createLearner();
        UserDailyShield::todayForUser($learner)->update(['shields_remaining' => 2]);

        $response = $this->actingAs($learner)
            ->get(route('learner.gamification'));

        $response->assertOk();
        $response->assertSeeText('2/5');

        $response->assertSeeText('5 shields');
        $response->assertSeeText('21 points');
        $response->assertSeeText('84 points');

        $response->assertSeeText('5-day streak');
        $response->assertSeeText('+40 bonus points');
        $response->assertSeeText('10-day streak');
        $response->assertSeeText('+120 bonus points');
        $response->assertSeeText('42 points');
        $response->assertSeeText('up to 4 savers');

        $response->assertSeeText('+11');
        $response->assertSeeText('+17');
        $response->assertSeeText('+130');
        $response->assertSeeText('+70');
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

    private function createLearner(): User
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
            'username' => 'rules_' . $learner->id,
            'birthdate' => now()->subYears(19)->toDateString(),
        ]);

        return $learner;
    }
}
