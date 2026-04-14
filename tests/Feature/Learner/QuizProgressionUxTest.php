<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class QuizProgressionUxTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_passed_quiz_result_shows_proceed_to_next_lesson_button_when_next_lesson_exists(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        [$learner, $module, $lesson, $nextLesson, $quiz] = $this->createQuizScenarioWithNextLesson();

        $attempt = QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 92,
            'passed' => true,
            'answers' => [],
            'started_at' => now()->subMinutes(2),
            'completed_at' => now()->subMinute(),
        ]);

        $this->actingAs($learner)
            ->get(route('quizzes.result', $attempt))
            ->assertOk()
            ->assertSee('Proceed to Next Lesson')
            ->assertSee(route('learner.lessons.show', $nextLesson), false);
    }

    public function test_final_lesson_passed_quiz_shows_completion_options_in_lesson_page(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        [$learner, $module, $lesson, $quiz] = $this->createFinalLessonScenario();

        UserProgress::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);

        $attempt = QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 95,
            'passed' => true,
            'answers' => [],
            'started_at' => now()->subMinutes(2),
            'completed_at' => now()->subMinute(),
        ]);

        $this->actingAs($learner)
            ->withSession([
                'quiz_result' => true,
                'quiz_attempt_id' => $attempt->id,
            ])
            ->get(route('learner.lessons.show', ['lesson' => $lesson->id, 'quiz' => 1]))
            ->assertOk()
            ->assertSee('View Completion Options');
    }

    /**
     * @return array{0: User, 1: Module, 2: Lesson, 3: Lesson, 4: Quiz}
     */
    private function createQuizScenarioWithNextLesson(): array
    {
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => 'active']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'quizux_' . $learner->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Quiz UX test profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'current_review_status' => null,
            'final_quiz_id' => null,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'order' => 1,
            'is_published' => true,
        ]);

        $nextLesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'order' => 2,
            'is_published' => true,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'passing_score' => 70,
            'attempt_limit' => 3,
            'is_active' => true,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        return [$learner, $module, $lesson, $nextLesson, $quiz];
    }

    /**
     * @return array{0: User, 1: Module, 2: Lesson, 3: Quiz}
     */
    private function createFinalLessonScenario(): array
    {
        [$learner, $module, $lesson, $nextLesson, $quiz] = $this->createQuizScenarioWithNextLesson();

        $nextLesson->update(['is_published' => false]);

        return [$learner, $module, $lesson, $quiz];
    }
}
