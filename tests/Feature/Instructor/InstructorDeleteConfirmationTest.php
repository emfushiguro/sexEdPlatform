<?php

namespace Tests\Feature\Instructor;

use App\Enums\EnrollmentStatus;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorDeleteConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_pages_expose_page_level_delete_confirmation_modal_controls(): void
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

        Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'is_active' => true,
        ]);

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.modules.index'))
            ->assertOk()
            ->assertSee('id="modules-delete-confirm-modal"', false)
            ->assertSee('data-delete-confirm-cancel', false)
            ->assertSee('data-delete-confirm-submit', false);

        $this->actingAs($instructor)
            ->get(route('instructor.lessons.index'))
            ->assertOk()
            ->assertSee('id="lessons-delete-confirm-modal"', false)
            ->assertSee('data-delete-confirm-cancel', false)
            ->assertSee('data-delete-confirm-submit', false);

        $this->actingAs($instructor)
            ->get(route('instructor.quizzes.index'))
            ->assertOk()
            ->assertSee('id="quizzes-delete-confirm-modal"', false)
            ->assertSee('data-delete-confirm-cancel', false)
            ->assertSee('data-delete-confirm-submit', false);

        $this->actingAs($instructor)
            ->get(route('instructor.image-library.index'))
            ->assertOk()
            ->assertSee('id="image-library-delete-confirm-modal"', false)
            ->assertSee('data-delete-confirm-cancel', false)
            ->assertSee('data-delete-confirm-submit', false);

        $this->actingAs($instructor)
            ->get(route('instructor.users.index'))
            ->assertOk()
            ->assertSee('id="users-delete-confirm-modal"', false);
    }
}
