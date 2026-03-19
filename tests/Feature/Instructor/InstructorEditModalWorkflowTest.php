<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorEditModalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_lessons_and_quizzes_indexes_expose_modal_edit_triggers(): void
    {
        [$instructor, $lesson, $quiz] = $this->createOwnedLessonAndQuiz();

        $this->actingAs($instructor)
            ->get(route('instructor.lessons.index'))
            ->assertOk()
            ->assertSee('data-edit-lesson-trigger', false)
            ->assertDontSee(route('instructor.lessons.edit', $lesson), false);

        $this->actingAs($instructor)
            ->get(route('instructor.quizzes.index'))
            ->assertOk()
            ->assertSee('data-edit-quiz-trigger', false)
            ->assertDontSee(route('instructor.quizzes.edit', $quiz), false);
    }

    public function test_legacy_edit_pages_redirect_to_modal_enabled_indexes(): void
    {
        [$instructor, $lesson, $quiz] = $this->createOwnedLessonAndQuiz();

        $this->actingAs($instructor)
            ->get(route('instructor.lessons.edit', $lesson))
            ->assertRedirect(route('instructor.lessons.index', ['edit_lesson' => $lesson->id]));

        $this->actingAs($instructor)
            ->get(route('instructor.quizzes.edit', $quiz))
            ->assertRedirect(route('instructor.quizzes.index', ['edit_quiz' => $quiz->id]));
    }

    /**
     * @return array{0: User, 1: Lesson, 2: Quiz}
     */
    private function createOwnedLessonAndQuiz(): array
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'is_active' => true,
        ]);

        return [$instructor, $lesson, $quiz];
    }
}
