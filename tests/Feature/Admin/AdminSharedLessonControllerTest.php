<?php

namespace Tests\Feature\Admin;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminSharedLessonControllerTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_cannot_create_lesson_for_instructor_owned_module(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.lessons.store'), [
            'module_id' => $module->id,
            'title' => 'Admin Added Lesson',
            'description' => 'Admin lesson description',
            'is_published' => 1,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('lessons', [
            'module_id' => $module->id,
            'title' => 'Admin Added Lesson',
        ]);
    }

    public function test_admin_lessons_index_includes_instructor_modules(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor Module For Admin',
        ]);

        Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Instructor Lesson Visible To Admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.lessons.index'));

        $response->assertOk();
        $response->assertSee('Instructor Module For Admin');
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
