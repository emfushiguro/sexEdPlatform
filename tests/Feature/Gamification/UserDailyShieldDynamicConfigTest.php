<?php

namespace Tests\Feature\Gamification;

use App\Models\GamificationPolicy;
use App\Models\User;
use App\Models\UserDailyShield;
use App\Services\Gamification\GamificationPolicyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDailyShieldDynamicConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_record_uses_configured_daily_default(): void
    {
        $this->activatePolicy([
            'shield_config' => [
                'daily_shields_default' => 5,
                'max_shields_per_day_cap' => 6,
            ],
        ]);

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->assertSame(5, UserDailyShield::getShields($learner));
    }

    public function test_refill_methods_respect_configured_cap(): void
    {
        $this->activatePolicy([
            'shield_config' => [
                'daily_shields_default' => 2,
                'max_shields_per_day_cap' => 4,
            ],
        ]);

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->assertSame(2, UserDailyShield::getShields($learner));

        UserDailyShield::refillOne($learner);
        UserDailyShield::refillOne($learner);
        UserDailyShield::refillOne($learner);

        $this->assertSame(4, UserDailyShield::getShields($learner));

        UserDailyShield::drainShield($learner);
        $this->assertSame(3, UserDailyShield::getShields($learner));

        UserDailyShield::refillFull($learner);
        $this->assertSame(4, UserDailyShield::getShields($learner));
    }

    public function test_drain_shield_still_floors_at_zero_with_dynamic_defaults(): void
    {
        $this->activatePolicy([
            'shield_config' => [
                'daily_shields_default' => 1,
                'max_shields_per_day_cap' => 2,
                'refill_full_target_shields' => 2,
            ],
        ]);

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->assertTrue(UserDailyShield::drainShield($learner));
        $this->assertFalse(UserDailyShield::drainShield($learner));
        $this->assertSame(0, UserDailyShield::getShields($learner));
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
}
