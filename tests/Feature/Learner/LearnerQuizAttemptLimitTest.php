<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Tests\TestCase;

class LearnerQuizAttemptLimitTest extends TestCase
{
    public function test_learner_is_blocked_after_reaching_attempt_limit(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create(['is_published' => true]);
        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'attempt_limit' => 1,
        ]);

        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        QuizAttempt::create([
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
            'score' => 50,
            'passed' => false,
            'answers' => [],
            'started_at' => now()->subMinute(),
            'completed_at' => now()->subMinute(),
        ]);

        $this->actingAs($learner)
            ->get(route('quizzes.start', $quiz))
            ->assertRedirect(route('learner.modules.show', $module->id))
            ->assertSessionHas('error');
    }
}
