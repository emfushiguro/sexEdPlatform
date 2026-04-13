<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Http\Middleware\EnsureProfileCompleted;
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
            ->assertRedirect(route('learner.modules.completion', $module));

        $this->actingAs($learner)
            ->get(route('learner.modules.completion', $module))
            ->assertOk()
            ->assertSee($module->title, false)
            ->assertSee('Congratulations!', false)
            ->assertSee('You have successfully completed this module.', false)
            ->assertSee('Claim Certificate', false)
            ->assertSee('Return to Modules', false);
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
}
