<?php

namespace Tests\Feature\Gamification;

use App\Models\User;
use App\Models\UserDailyShield;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserDailyShieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_table_exists_with_correct_columns(): void
    {
        $this->assertTrue(Schema::hasTable('user_daily_shields'));
        $this->assertTrue(Schema::hasColumn('user_daily_shields', 'user_id'));
        $this->assertTrue(Schema::hasColumn('user_daily_shields', 'shields_remaining'));
        $this->assertTrue(Schema::hasColumn('user_daily_shields', 'date'));
        $this->assertFalse(Schema::hasColumn('user_daily_shields', 'quiz_id'));
    }

    public function test_old_table_no_longer_exists(): void
    {
        $this->assertFalse(Schema::hasTable('quiz_daily_limits'));
    }

    public function test_user_gamification_has_longest_streak_and_streak_savers(): void
    {
        $this->assertTrue(Schema::hasColumn('user_gamifications', 'longest_streak'));
        $this->assertTrue(Schema::hasColumn('user_gamifications', 'streak_savers'));
    }

    public function test_get_shields_returns_3_for_new_user_today(): void
    {
        $user = User::factory()->create();
        $user->assignRole('learner');

        $shields = UserDailyShield::getShields($user);

        $this->assertEquals(3, $shields);
    }

    public function test_drain_shield_decrements_by_one(): void
    {
        $user = User::factory()->create();
        $user->assignRole('learner');

        UserDailyShield::drainShield($user);

        $this->assertEquals(2, UserDailyShield::getShields($user));
    }

    public function test_drain_shield_floors_at_zero(): void
    {
        $user = User::factory()->create();
        $user->assignRole('learner');

        UserDailyShield::drainShield($user);
        UserDailyShield::drainShield($user);
        UserDailyShield::drainShield($user);
        UserDailyShield::drainShield($user); // 4th drain — should stay at 0

        $this->assertEquals(0, UserDailyShield::getShields($user));
    }

    public function test_refill_one_increments_by_one_max_3(): void
    {
        $user = User::factory()->create();
        $user->assignRole('learner');

        UserDailyShield::drainShield($user); // now 2
        UserDailyShield::refillOne($user);   // back to 3

        $this->assertEquals(3, UserDailyShield::getShields($user));
    }

    public function test_refill_full_restores_to_3(): void
    {
        $user = User::factory()->create();
        $user->assignRole('learner');

        UserDailyShield::drainShield($user);
        UserDailyShield::drainShield($user); // now 1
        UserDailyShield::refillFull($user);

        $this->assertEquals(3, UserDailyShield::getShields($user));
    }
}
