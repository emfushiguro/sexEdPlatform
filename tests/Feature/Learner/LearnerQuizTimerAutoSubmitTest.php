<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\User;
use Tests\TestCase;

class LearnerQuizTimerAutoSubmitTest extends TestCase
{
    public function test_timer_expiry_auto_submit_behavior_is_present_in_view(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create(['is_published' => true]);
        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'time_limit' => 90,
        ]);

        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('quizzes.start', $quiz))
            ->assertOk()
            ->assertSee('name="auto_submit"', false)
            ->assertSee('document.getElementById(\'quizForm\').submit()', false);
    }

    public function test_server_accepts_expired_timer_submission_as_auto_submit_fallback(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $module = Module::factory()->create(['is_published' => true]);
        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'time_limit' => 1,
        ]);

        ModuleEnrollment::create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($learner)
            ->post(route('quizzes.submit', $quiz), [
                'started_at' => now()->subSeconds(120)->timestamp,
                'auto_submit' => '1',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', [
            'user_id' => $learner->id,
            'quiz_id' => $quiz->id,
        ]);
    }
}
