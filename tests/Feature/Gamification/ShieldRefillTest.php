<?php

namespace Tests\Feature\Gamification;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\User;
use App\Models\UserDailyShield;
use App\Models\UserGamification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShieldRefillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    private function learnerWithPoints(int $score): User
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $user->assignRole('learner');
        $user->gamification()->update([
            'score'        => $score,
            'total_points' => $score,
        ]);
        UserDailyShield::create(['user_id' => $user->id, 'shields_remaining' => 0, 'date' => today()]);
        return $user;
    }

    public function test_single_refill_costs_50_points_and_adds_one_shield(): void
    {
        $user = $this->learnerWithPoints(100);

        $this->actingAs($user)->post(route('learner.shields.refill'), ['type' => 'single']);

        $this->assertEquals(50, $user->gamification()->first()->score);
        $this->assertEquals(1, UserDailyShield::getShields($user));
    }

    public function test_full_refill_costs_100_points_and_restores_3_shields(): void
    {
        $user = $this->learnerWithPoints(150);

        $this->actingAs($user)->post(route('learner.shields.refill'), ['type' => 'full']);

        $this->assertEquals(50, $user->gamification()->first()->score);
        $this->assertEquals(3, UserDailyShield::getShields($user));
    }

    public function test_refill_fails_when_insufficient_points(): void
    {
        $user = $this->learnerWithPoints(30);

        $response = $this->actingAs($user)->post(route('learner.shields.refill'), ['type' => 'single']);

        $response->assertRedirect();
        $this->assertEquals(0, UserDailyShield::getShields($user)); // unchanged
        $this->assertEquals(30, $user->gamification()->first()->score); // unchanged
    }

    public function test_streak_saver_purchase_costs_75_points(): void
    {
        $user = $this->learnerWithPoints(150);

        $this->actingAs($user)->post(route('learner.streak-savers.buy'));

        $this->assertEquals(75, $user->gamification()->first()->score);
        $this->assertEquals(1, $user->gamification()->first()->streak_savers);
    }

    public function test_streak_saver_capped_at_3(): void
    {
        $user = $this->learnerWithPoints(500);
        $user->gamification()->update(['streak_savers' => 3]);

        $response = $this->actingAs($user)->post(route('learner.streak-savers.buy'));

        $response->assertRedirect();
        $this->assertEquals(3, $user->gamification()->first()->streak_savers); // unchanged
        $this->assertEquals(500, $user->gamification()->first()->score); // unchanged
    }
}
