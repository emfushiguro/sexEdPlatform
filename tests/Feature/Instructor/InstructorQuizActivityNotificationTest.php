<?php

namespace Tests\Feature\Instructor;

use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorQuizActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_attempt_emits_instructor_notification_and_dashboard_summary_remains_visible(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'current_review_status' => null,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => null,
            'is_active' => true,
            'attempt_limit' => null,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Sample question',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order' => 1,
        ]);

        $correctOption = QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Correct answer',
            'is_correct' => true,
            'order' => 1,
        ]);

        QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'option_text' => 'Wrong answer',
            'is_correct' => false,
            'order' => 2,
        ]);

        ModuleEnrollment::create([
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

        $this->assertSame(1, QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', $learner->id)->count());

        $notification = $instructor->fresh()->notifications()->latest()->first();

        $this->assertNotNull($notification);
        $this->assertSame('quiz_attempt_activity', data_get($notification->data, 'type'));

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="quiz-taking-summary"', false);
    }
}
