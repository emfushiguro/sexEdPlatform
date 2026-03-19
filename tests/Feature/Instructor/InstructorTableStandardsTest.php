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

class InstructorTableStandardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_pages_render_numbering_and_icon_readability_markers(): void
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
            ->get(route('instructor.users.index'))
            ->assertOk()
            ->assertSee('table-standard-numbering', false)
            ->assertSee('action-icon-standard', false);

        $this->actingAs($instructor)
            ->get(route('instructor.lessons.index'))
            ->assertOk()
            ->assertSee('table-standard-numbering', false)
            ->assertSee('action-icon-standard', false);

        $this->actingAs($instructor)
            ->get(route('instructor.quizzes.index'))
            ->assertOk()
            ->assertSee('table-standard-numbering', false)
            ->assertSee('action-icon-standard', false);

        $this->actingAs($instructor)
            ->get(route('instructor.modules.index'))
            ->assertOk()
            ->assertSee('action-icon-standard', false)
            ->assertSee('instructor-icon-readable', false);
    }
}
