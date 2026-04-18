<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\GamificationPolicy;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserDailyShield;
use App\Models\UserProgress;
use App\Services\Gamification\GamificationPolicyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerGamificationDynamicFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_shield_refill_and_streak_saver_purchase_use_configured_costs_and_caps(): void
    {
        $this->activatePolicy([
            'streak_config' => [
                'max_savers_held' => 2,
                'saver_purchase_cost_points' => 23,
            ],
            'shield_config' => [
                'refill_single_cost_points' => 17,
                'refill_full_cost_points' => 91,
                'max_shields_per_day_cap' => 5,
                'refill_full_target_shields' => 5,
            ],
        ]);

        $user = $this->learnerWithPoints(200);

        $this->actingAs($user)
            ->post(route('learner.shields.refill'), ['type' => 'single'])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame(183, (int) $user->gamification->score);
        $this->assertSame(1, UserDailyShield::getShields($user));

        $fullRefillResponse = $this->actingAs($user)
            ->post(route('learner.shields.refill'), ['type' => 'full']);

        $fullRefillResponse->assertRedirect();
        $fullRefillResponse->assertSessionHas('success', "Full shield refill! You're back to 5 shields.");

        $user->refresh();
        $this->assertSame(92, (int) $user->gamification->score);

        $user->gamification()->update(['streak_savers' => 1]);

        $this->actingAs($user)
            ->post(route('learner.streak-savers.buy'))
            ->assertRedirect();

        $user->refresh();
        $this->assertSame(69, (int) $user->gamification->score);
        $this->assertSame(2, (int) $user->gamification->streak_savers);

        $capResponse = $this->actingAs($user)
            ->post(route('learner.streak-savers.buy'));

        $capResponse->assertRedirect();
        $capResponse->assertSessionHas('error', 'You already have the maximum number of streak savers (2).');

        $user->refresh();
        $this->assertSame(69, (int) $user->gamification->score);
        $this->assertSame(2, (int) $user->gamification->streak_savers);
    }

    public function test_lesson_completion_awards_configured_points(): void
    {
        $this->activatePolicy([
            'points_config' => [
                'lesson_complete_points' => 31,
            ],
        ]);

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'is_published' => true,
            'current_review_status' => null,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($learner)
            ->post(route('learner.lessons.complete', $lesson));

        $response->assertRedirect();
        $response->assertSessionHas('points_earned', 31);

        $learner->refresh();
        $this->assertSame(31, (int) $learner->gamification->score);
    }

    public function test_quiz_submission_awards_configured_perfect_score_points(): void
    {
        $this->activatePolicy([
            'points_config' => [
                'quiz_bands' => [
                    'perfect_score_points' => 44,
                    'pass_score_points' => 29,
                    'fail_attempt_points' => 6,
                ],
            ],
        ]);

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'is_published' => true,
            'current_review_status' => null,
            'final_quiz_id' => null,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'is_active' => true,
            'passing_score' => 70,
            'attempt_limit' => null,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Pick the correct answer.',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order' => 1,
        ]);

        $correctOption = QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Correct',
            'is_correct' => true,
            'order' => 1,
        ]);

        QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Wrong',
            'is_correct' => false,
            'order' => 2,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($learner)
            ->post(route('quizzes.submit', $quiz), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
                'started_at' => now()->timestamp,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('xp_earned', 44);

        $learner->refresh();
        $this->assertSame(44, (int) $learner->gamification->score);
    }

    public function test_certificate_generation_awards_configured_bonus_points(): void
    {
        $this->activatePolicy([
            'points_config' => [
                'certificate_earned_points' => 77,
            ],
        ]);

        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'current_review_status' => null,
            'final_quiz_id' => null,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        UserProgress::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($learner)
            ->post(route('learner.certificates.check', $module));

        $response->assertRedirect();

        $learner->refresh();
        $this->assertSame(77, (int) $learner->gamification->score);
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

    private function learnerWithPoints(int $score): User
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);

        $user->assignRole('learner');

        $user->gamification()->update([
            'score' => $score,
            'total_points' => $score,
            'streak_savers' => 0,
        ]);

        UserDailyShield::query()->create([
            'user_id' => $user->id,
            'shields_remaining' => 0,
            'date' => today(),
        ]);

        return $user;
    }
}
