<?php

namespace Tests\Feature\Gamification;

use App\Models\User;
use App\Models\UserGamification;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GamificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GamificationService();
    }

    private function userWithGamification(): User
    {
        $user = User::factory()->create();
        $user->assignRole('learner');
        UserGamification::create([
            'user_id'      => $user->id,
            'level'        => 1,
            'score'        => 0,
            'total_points' => 0,
            'streak_count' => 0,
        ]);
        return $user;
    }

    public function test_award_points_increments_score_and_total_points(): void
    {
        $user = $this->userWithGamification();

        $this->service->awardPoints($user, 'topic_complete', 10);

        $gamification = $user->gamification()->first();
        $this->assertEquals(10, $gamification->score);
        $this->assertEquals(10, $gamification->total_points);
    }

    public function test_spend_points_decrements_score_only(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['score' => 100, 'total_points' => 200]);

        $result = $this->service->spendPoints($user, 50);

        $this->assertTrue($result);
        $gamification = $user->gamification()->first();
        $this->assertEquals(50, $gamification->score);
        $this->assertEquals(200, $gamification->total_points); // unchanged
    }

    public function test_spend_points_returns_false_when_insufficient(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['score' => 30]);

        $result = $this->service->spendPoints($user, 50);

        $this->assertFalse($result);
        $this->assertEquals(30, $user->gamification()->first()->score); // unchanged
    }

    public function test_update_streak_increments_when_last_act_was_yesterday(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['last_act_at' => now()->subDay(), 'streak_count' => 3]);

        $this->service->updateStreak($user);

        $this->assertEquals(4, $user->gamification()->first()->streak_count);
    }

    public function test_update_streak_resets_when_missed_day_and_no_savers(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update([
            'last_act_at'   => now()->subDays(2),
            'streak_count'  => 5,
            'streak_savers' => 0,
        ]);

        $this->service->updateStreak($user);

        $this->assertEquals(1, $user->gamification()->first()->streak_count);
    }

    public function test_update_streak_preserves_when_missed_day_and_has_savers(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update([
            'last_act_at'   => now()->subDays(2),
            'streak_count'  => 5,
            'streak_savers' => 2,
        ]);

        $this->service->updateStreak($user);

        $gamification = $user->gamification()->first();
        $this->assertEquals(5, $gamification->streak_count);  // preserved
        $this->assertEquals(1, $gamification->streak_savers); // consumed one
    }

    public function test_longest_streak_updated_when_exceeded(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update([
            'last_act_at'    => now()->subDay(),
            'streak_count'   => 6,
            'longest_streak' => 6,
        ]);

        $this->service->updateStreak($user);

        $this->assertEquals(7, $user->gamification()->first()->longest_streak);
    }

    public function test_check_streak_milestone_returns_50_on_7th_day(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['streak_count' => 7]);

        $bonus = $this->service->checkStreakMilestone($user);

        $this->assertEquals(50, $bonus);
    }

    public function test_check_streak_milestone_returns_200_on_30th_day(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['streak_count' => 30]);

        $bonus = $this->service->checkStreakMilestone($user);

        $this->assertEquals(200, $bonus);
    }

    public function test_check_streak_milestone_returns_null_on_non_milestone(): void
    {
        $user = $this->userWithGamification();
        $user->gamification()->update(['streak_count' => 5]);

        $this->assertNull($this->service->checkStreakMilestone($user));
    }
}
