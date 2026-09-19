<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Models\GamificationPolicy;
use App\Models\LearnerProfile;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\UserProgress;
use App\Models\User;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Services\Gamification\GamificationPolicyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerFinalQuizCompletionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_passing_module_final_quiz_redirects_to_completion_page(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'final_quiz_learner',
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);

        $module = Module::factory()->create([
            'is_published' => true,
            'min_age' => 18,
            'max_age' => 30,
        ]);

        $finalQuiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'passing_score' => 70,
            'attempt_limit' => 3,
            'is_active' => true,
        ]);

        $module->update([
            'final_quiz_id' => $finalQuiz->id,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $finalQuiz->id,
            'question_text' => 'Select the correct answer.',
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

        $this->actingAs($learner)
            ->post(route('quizzes.submit', $finalQuiz), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
                'started_at' => now()->timestamp,
            ])
            ->assertRedirect(route('learner.modules.completion', $module))
            ->assertSessionHas('learning_audio_event', 'success');

        $this->actingAs($learner)
            ->get(route('learner.modules.completion', $module))
            ->assertOk()
            ->assertSee($module->title, false)
            ->assertSee('Congratulations!', false)
            ->assertSee('You have successfully completed this module.', false)
            ->assertSee('Claim Certificate', false)
            ->assertSee('Return to Modules', false);
    }

    public function test_direct_module_completion_flashes_success_only_on_first_transition(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'direct_completion_learner',
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);

        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);
        $finalQuiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'is_active' => true,
            'attempt_limit' => 3,
        ]);
        $module->update(['final_quiz_id' => $finalQuiz->id]);

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
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
        QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $finalQuiz->id,
            'answers' => [],
            'score' => 100,
            'passed' => true,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.completion', $module))
            ->assertOk()
            ->assertSee('data-learning-audio-event="success"', false);

        $this->actingAs($learner)
            ->get(route('learner.modules.completion', $module))
            ->assertOk()
            ->assertDontSee('data-learning-audio-event="success"', false);
    }

    public function test_completion_page_requires_passed_final_quiz(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'completion_guard_learner',
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);

        $module = Module::factory()->create([
            'is_published' => true,
        ]);

        $finalQuiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'is_active' => true,
        ]);

        $module->update([
            'final_quiz_id' => $finalQuiz->id,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.modules.completion', $module))
            ->assertRedirect(route('learner.modules.show', $module))
            ->assertSessionHas('error');
    }

    public function test_failed_persisted_final_quiz_attempt_also_flashes_success_audio_event(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'failed_final_quiz_learner',
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);

        $module = Module::factory()->create(['is_published' => true]);
        $finalQuiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'passing_score' => 70,
            'attempt_limit' => 3,
            'is_active' => true,
        ]);
        $module->update(['final_quiz_id' => $finalQuiz->id]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $finalQuiz->id,
            'question_text' => 'Select the correct answer.',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order' => 1,
        ]);
        QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Correct',
            'is_correct' => true,
            'order' => 1,
        ]);
        $wrongOption = QuizOption::query()->create([
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
            ->post(route('quizzes.submit', $finalQuiz), [
                'answers' => [$question->id => $wrongOption->id],
                'started_at' => now()->timestamp,
            ]);

        $attempt = QuizAttempt::query()->latest('id')->firstOrFail();

        $response
            ->assertRedirect(route('quizzes.result', $attempt))
            ->assertSessionHas('learning_audio_event', 'success');

        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $learner->id,
            'quiz_id' => $finalQuiz->id,
            'passed' => false,
        ]);
    }

    public function test_passing_final_quiz_awards_module_completion_points_once_and_persists_completion_state(): void
    {
        GamificationPolicy::query()->update(['is_active' => false]);
        GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => [
                'points_config' => [
                    'level_up_bonus_points' => 0,
                    'module_complete_points' => 57,
                    'quiz_bands' => [
                        'perfect_score_points' => 13,
                        'pass_score_points' => 13,
                        'fail_attempt_points' => 0,
                    ],
                ],
            ],
            'updated_by' => null,
        ]);
        app(GamificationPolicyResolver::class)->clearCache();

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'module_reward_learner',
            'birthdate' => now()->subYears(20)->toDateString(),
        ]);

        $module = Module::factory()->create([
            'is_published' => true,
            'min_age' => 18,
            'max_age' => 30,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);

        $finalQuiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'passing_score' => 70,
            'attempt_limit' => 3,
            'is_active' => true,
        ]);

        $module->update([
            'final_quiz_id' => $finalQuiz->id,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $finalQuiz->id,
            'question_text' => 'Select the correct answer.',
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

        UserProgress::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);

        $this->actingAs($learner)
            ->post(route('quizzes.submit', $finalQuiz), [
                'answers' => [
                    $question->id => $correctOption->id,
                ],
                'started_at' => now()->timestamp,
            ])
            ->assertRedirect(route('learner.modules.completion', $module))
            ->assertSessionHas('module_completion_points_earned', 57);

        $learner->refresh();
        $enrollment = ModuleEnrollment::query()
            ->where('user_id', $learner->id)
            ->where('module_id', $module->id)
            ->firstOrFail();

        $this->assertSame(70, (int) $learner->gamification->score);
        $this->assertSame(70, (int) $learner->gamification->total_points);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertSame(100, (int) $enrollment->completion_percentage);

        $this->actingAs($learner)
            ->get(route('learner.modules.completion', $module))
            ->assertOk();

        $learner->refresh();
        $this->assertSame(70, (int) $learner->gamification->score);
        $this->assertSame(70, (int) $learner->gamification->total_points);
    }
}
