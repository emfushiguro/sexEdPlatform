<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\ModuleEnrollment;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectorModuleProgressPreservationTest extends TestCase
{
    use RefreshDatabase;
    use ConnectorLearnerAccessHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_revocation_does_not_delete_progress_attempts_completion_or_certificate_history(): void
    {
        $fixture = $this->connectorLearnerFixture();
        $learner = $fixture['learner'];
        $enrollment = ModuleEnrollment::query()
            ->where('user_id', $learner->id)
            ->where('module_id', $fixture['module']->id)
            ->firstOrFail();
        $enrollment->update([
            'completion_percentage' => 100,
            'completed_at' => now()->subHour(),
        ]);
        UserProgress::query()->create([
            'user_id' => $learner->id,
            'module_id' => $fixture['module']->id,
            'lesson_id' => $fixture['lesson']->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now()->subHour(),
        ]);
        QuizAttempt::query()->create([
            'user_id' => $learner->id,
            'quiz_id' => $fixture['quiz']->id,
            'answers' => [],
            'score' => 100,
            'passed' => true,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(4),
        ]);

        $attemptCount = QuizAttempt::query()->where('user_id', $learner->id)->count();
        $this->revokeConnectorLearnerAccess($fixture);

        $this->actingAs($learner)
            ->post(route('quizzes.submit', $fixture['quiz']), ['answers' => []])
            ->assertForbidden();

        $this->assertSame($attemptCount, QuizAttempt::query()->where('user_id', $learner->id)->count());
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $learner->id,
            'module_id' => $fixture['module']->id,
            'lesson_id' => $fixture['lesson']->id,
            'completed' => 1,
        ]);
        $this->assertDatabaseHas('module_enrollments', [
            'id' => $enrollment->id,
            'completion_percentage' => 100,
        ]);
        $this->assertDatabaseHas('certificates', ['id' => $fixture['certificate']->id]);
    }
}
