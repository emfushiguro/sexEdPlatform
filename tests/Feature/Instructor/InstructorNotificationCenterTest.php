<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstructorNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_notification_index_page_renders(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $instructor->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'instructor.update',
            'data' => [
                'title' => 'Module updated',
                'message' => 'Your module metadata was updated.',
            ],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.notifications.index'))
            ->assertOk()
            ->assertSee('Notifications', false)
            ->assertSee('Module updated', false);
    }

    public function test_instructor_can_mark_all_notifications_as_read_from_header_dropdown(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $instructor->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'instructor.update',
            'data' => [
                'title' => 'Module updated',
                'message' => 'Your module metadata was updated.',
                'action_url' => route('instructor.dashboard'),
            ],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee(route('instructor.notifications.mark-all-read'), false)
            ->assertSee('Mark all read', false);

        $this->actingAs($instructor)
            ->from(route('instructor.dashboard'))
            ->post(route('instructor.notifications.mark-all-read'))
            ->assertRedirect(route('instructor.dashboard'));

        $this->assertSame(0, $instructor->fresh()->unreadNotifications()->count());
    }

    public function test_dashboard_notification_center_shows_quiz_summary_and_enrollment_decision_notifications(): void
    {
        /** @var User $instructor */
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

    public function test_instructor_dropdown_open_endpoint_marks_notifications_read(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $instructor->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'instructor.update',
            'data' => [
                'title' => 'Unread notification',
                'message' => 'Please review this update.',
            ],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->actingAs($instructor)
            ->postJson(route('instructor.notifications.dropdown-open'))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'updated' => 1,
            ]);

        $this->assertSame(0, $instructor->fresh()->unreadNotifications()->count());
    }

    public function test_instructor_can_mark_all_notifications_as_read_from_full_page(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $instructor->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'instructor.update',
            'data' => [
                'title' => 'Unread notification',
                'message' => 'Mark all should clear this.',
            ],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->actingAs($instructor)
            ->from(route('instructor.notifications.index'))
            ->post(route('instructor.notifications.mark-all-read'))
            ->assertRedirect(route('instructor.notifications.index'));

        $this->assertSame(0, $instructor->fresh()->unreadNotifications()->count());
    }
}
