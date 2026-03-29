<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Tests\TestCase;

class InstructorQuizSettingsValidationTest extends TestCase
{
    public function test_hms_inputs_are_normalized_to_seconds_and_attempt_limit_is_saved(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $module = Module::factory()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.store'), [
                'title' => 'Quiz settings test',
                'description' => 'desc',
                'module_id' => $module->id,
                'passing_score' => 70,
                'time_limit_hours' => 0,
                'time_limit_minutes' => 2,
                'time_limit_seconds' => 30,
                'attempt_limit' => 3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quizzes', [
            'title' => 'Quiz settings test',
            'time_limit' => 150,
            'attempt_limit' => 3,
        ]);
    }

    public function test_attempt_limit_can_be_null(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $module = Module::factory()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->post(route('instructor.quizzes.store'), [
                'title' => 'Quiz unlimited',
                'description' => 'desc',
                'module_id' => $module->id,
                'passing_score' => 70,
                'time_limit_hours' => 0,
                'time_limit_minutes' => 0,
                'time_limit_seconds' => 0,
            ])
            ->assertRedirect();

        $quiz = Quiz::where('title', 'Quiz unlimited')->firstOrFail();
        $this->assertNull($quiz->attempt_limit);
        $this->assertNull($quiz->time_limit);
    }
}
