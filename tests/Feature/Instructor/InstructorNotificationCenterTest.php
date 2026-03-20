<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_notification_center_shows_quiz_summary_and_enrollment_decision_notifications(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create([
            'title' => 'Consent Essentials',
            'created_by' => $instructor->id,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'title' => 'Consent Quiz',
        ]);

        $learner = User::factory()->create();
        $learner->assignRole('learner');

        QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'answers' => ['q1' => 'A'],
            'score' => 75,
            'passed' => true,
            'started_at' => now()->subHours(4),
            'completed_at' => now()->subHours(4),
        ]);

        QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'answers' => ['q1' => 'B'],
            'score' => 80,
            'passed' => true,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2),
        ]);

        $instructor->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'instructor.enrollment.decision',
            'data' => [
                'title' => 'Enrollment decision recorded',
                'message' => 'You rejected an enrollment request for Consent Essentials.',
                'action_url' => route('instructor.enrollments.index'),
            ],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertOk()
            ->assertSee('data-testid="instructor-notification-badge"', false)
            ->assertSee('data-testid="quiz-taking-summary"', false)
            ->assertSee('New quiz taking', false)
            ->assertSee('Enrollment decision recorded', false)
            ->assertSee('You rejected an enrollment request for Consent Essentials.', false);
    }
}
