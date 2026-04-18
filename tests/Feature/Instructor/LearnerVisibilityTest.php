<?php

namespace Tests\Feature\Instructor;

use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_only_sees_learners_enrolled_in_owned_modules(): void
    {
        $instructor = $this->createInstructor();
        $otherInstructor = $this->createInstructor();

        $ownedModule = Module::factory()->create([
            'title' => 'Owned Module',
            'created_by' => $instructor->id,
        ]);

        $otherModule = Module::factory()->create([
            'title' => 'Other Module',
            'created_by' => $otherInstructor->id,
        ]);

        $visibleLearner = $this->createLearner('Visible', 'Learner', 'visible@example.test');
        $hiddenLearner = $this->createLearner('Hidden', 'Learner', 'hidden@example.test');

        ModuleEnrollment::factory()->create([
            'module_id' => $ownedModule->id,
            'user_id' => $visibleLearner->id,
            'status' => EnrollmentStatus::Approved,
        ]);

        ModuleEnrollment::factory()->create([
            'module_id' => $otherModule->id,
            'user_id' => $hiddenLearner->id,
            'status' => EnrollmentStatus::Approved,
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.users.index'));

        $response->assertOk();
        $response->assertViewHas('users', function ($users) use ($visibleLearner, $hiddenLearner) {
            $ids = $users->pluck('id');

            return $ids->contains($visibleLearner->id)
                && ! $ids->contains($hiddenLearner->id);
        });
    }

    public function test_instructor_learner_management_routes_are_view_only(): void
    {
        $instructor = $this->createInstructor();
        $learner = $this->createLearner('View', 'Only', 'view-only@example.test');

        $this->actingAs($instructor)
            ->get('/instructor/users/create')
            ->assertNotFound();

        $this->actingAs($instructor)
            ->get('/instructor/users/' . $learner->id . '/edit')
            ->assertNotFound();
    }

    public function test_instructor_can_open_learner_show_page_with_quiz_performance_summary(): void
    {
        $instructor = $this->createInstructor();

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
        ]);

        $learner = $this->createLearner('Quiz', 'Learner', 'quiz-learner@example.test');

        ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => EnrollmentStatus::Approved,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => null,
        ]);

        QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'answers' => [],
            'score' => 80,
            'passed' => true,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(19),
        ]);

        QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'answers' => [],
            'score' => 50,
            'passed' => false,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
        ]);

        $response = $this->actingAs($instructor)
            ->get(route('instructor.users.show', $learner));

        $response->assertOk()
            ->assertDontSee('Email Not Verified', false)
            ->assertSee('open-global-chat', false);

        $response->assertViewHas('quizPerformanceSummary', function (array $summary): bool {
            return $summary['attempts'] === 2
                && $summary['passed'] === 1
                && (float) $summary['average_score'] === 65.0;
        });
    }

    private function createInstructor(): User
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        return $instructor;
    }

    private function createLearner(string $firstName, string $lastName, string $email): User
    {
        $learner = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'role' => 'learner',
        ]);

        $learner->assignRole('learner');

        return $learner;
    }
}
