<?php

namespace Tests\Feature\Learner;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserDailyShield;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerQuizResultShieldPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_out_of_shields_popup_does_not_show_when_shields_remain(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'is_published' => true,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'attempt_limit' => 1,
            'passing_score' => 70,
            'is_active' => true,
        ]);

        $attempt = QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 50,
            'passed' => false,
            'answers' => [],
            'started_at' => now()->subMinutes(2),
            'completed_at' => now()->subMinute(),
        ]);

        UserDailyShield::todayForUser($learner)->update([
            'shields_remaining' => 2,
        ]);

        $this->actingAs($learner)
            ->get(route('quizzes.result', $attempt))
            ->assertOk()
            ->assertDontSeeText("You're out of shields for today");
    }

    public function test_out_of_shields_popup_shows_when_shields_are_zero(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'is_published' => true,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'attempt_limit' => 1,
            'passing_score' => 70,
            'is_active' => true,
        ]);

        $attempt = QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 50,
            'passed' => false,
            'answers' => [],
            'started_at' => now()->subMinutes(2),
            'completed_at' => now()->subMinute(),
        ]);

        UserDailyShield::todayForUser($learner)->update([
            'shields_remaining' => 0,
        ]);

        $this->actingAs($learner)
            ->get(route('quizzes.result', $attempt))
            ->assertOk()
            ->assertSeeText('out of shields for today');
    }
}
