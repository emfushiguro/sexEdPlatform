<?php

namespace Tests\Feature\Admin;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminLessonVisibilityFiltersTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_lesson_filters_apply_and_instructor_owned_rows_are_view_only(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        $instructorModule = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor Module Visibility',
        ]);

        $platformModule = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'title' => 'Platform Module Visibility',
        ]);

        $instructorLesson = Lesson::factory()->create([
            'module_id' => $instructorModule->id,
            'title' => 'Instructor Filter Match Lesson',
            'description' => 'admin-filter-keyword',
            'is_published' => true,
        ]);

        $platformLesson = Lesson::factory()->create([
            'module_id' => $platformModule->id,
            'title' => 'Platform Filter Match Lesson',
            'description' => 'admin-filter-keyword',
            'is_published' => true,
        ]);

        Lesson::factory()->create([
            'module_id' => $instructorModule->id,
            'title' => 'Instructor Inactive Lesson',
            'description' => 'admin-filter-keyword',
            'is_published' => false,
        ]);

        $filteredResponse = $this->actingAs($admin)->get(route('admin.lessons.index', [
            'module_id' => $instructorModule->id,
            'lesson_status' => 'active',
            'search' => 'Filter Match',
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Instructor Filter Match Lesson');
        $filteredResponse->assertDontSee('Instructor Inactive Lesson');
        $filteredResponse->assertDontSee('Platform Filter Match Lesson');

        $fullResponse = $this->actingAs($admin)->get(route('admin.lessons.index'));

        $fullResponse->assertOk();
        $fullResponse->assertSee('Instructor Filter Match Lesson');
        $fullResponse->assertSee('Platform Filter Match Lesson');

        $fullResponse->assertDontSee('data-testid="lesson-edit-' . $instructorLesson->id . '"', false);
        $fullResponse->assertDontSee('data-testid="lesson-delete-' . $instructorLesson->id . '"', false);
        $fullResponse->assertSee('data-testid="lesson-edit-' . $platformLesson->id . '"', false);
        $fullResponse->assertSee('data-testid="lesson-delete-' . $platformLesson->id . '"', false);
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }
}