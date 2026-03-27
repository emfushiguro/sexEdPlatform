<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class InstructorModuleStatusLifecycleTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_create_defaults_to_governed_draft_for_module_while_lessons_and_quizzes_stay_active(): void
    {
        $instructor = $this->createInstructor();

        $this->actingAs($instructor)->post(route('instructor.modules.store'), [
            'title' => 'Status Module',
            'description' => 'Status module description',
            'age_bracket' => 'teens',
            'enrollment_mode' => 'auto',
        ])->assertRedirect();

        $module = Module::query()->latest('id')->firstOrFail();
        $this->assertFalse((bool) $module->is_published);
        $this->assertSame('draft', $module->current_review_status);
        $this->assertSame('instructor', $module->content_owner_type);

        $this->actingAs($instructor)->post(route('instructor.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'Status Lesson',
            'description' => 'Status lesson description',
        ])->assertRedirect();

        $lesson = Lesson::query()->latest('id')->firstOrFail();
        $this->assertTrue((bool) $lesson->is_published);

        $this->actingAs($instructor)->post(route('instructor.quizzes.store'), [
            'title' => 'Status Quiz',
            'description' => 'Status quiz description',
            'module_id' => $module->id,
            'passing_score' => 70,
        ])->assertRedirect();

        $quiz = Quiz::query()->latest('id')->firstOrFail();
        $this->assertTrue((bool) $quiz->is_active);
    }

    public function test_edit_keeps_module_offline_under_governance_and_can_deactivate_lesson_and_quiz(): void
    {
        $instructor = $this->createInstructor();

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'min_age' => 13,
            'max_age' => 17,
            'enrollment_mode' => 'auto',
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($instructor)->put(route('instructor.modules.update', $module), [
            'title' => $module->title,
            'description' => $module->description,
            'age_bracket' => 'teens',
            'enrollment_mode' => 'auto',
        ])->assertRedirect();

        $this->actingAs($instructor)->put(route('instructor.lessons.update', $lesson), [
            'module_id' => $module->id,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'is_published' => 0,
        ])->assertRedirect();

        $this->actingAs($instructor)->put(route('instructor.quizzes.update', $quiz), [
            'title' => $quiz->title,
            'description' => $quiz->description,
            'module_id' => $module->id,
            'passing_score' => $quiz->passing_score,
            'is_active' => 0,
        ])->assertRedirect();

        $this->assertFalse((bool) $module->fresh()->is_published);
        $this->assertSame('instructor', $module->fresh()->content_owner_type);
        $this->assertFalse((bool) $lesson->fresh()->is_published);
        $this->assertFalse((bool) $quiz->fresh()->is_active);
    }

    private function createInstructor(): User
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        return $instructor;
    }
}
