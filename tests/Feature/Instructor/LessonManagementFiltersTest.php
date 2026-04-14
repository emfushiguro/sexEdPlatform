<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class LessonManagementFiltersTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_instructor_lesson_filters_support_module_status_and_keyword(): void
    {
        $instructor = $this->createUser('instructor');
        $otherInstructor = $this->createUser('instructor');

        $moduleA = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor Module A',
        ]);

        $moduleB = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Instructor Module B',
        ]);

        $otherModule = Module::factory()->create([
            'created_by' => $otherInstructor->id,
            'content_owner_type' => 'instructor',
            'title' => 'Other Instructor Module',
        ]);

        Lesson::factory()->create([
            'module_id' => $moduleA->id,
            'title' => 'Target Lesson Match',
            'description' => 'keyword-one',
            'is_published' => false,
        ]);

        Lesson::factory()->create([
            'module_id' => $moduleA->id,
            'title' => 'Active Lesson Same Module',
            'description' => 'keyword-one',
            'is_published' => true,
        ]);

        Lesson::factory()->create([
            'module_id' => $moduleB->id,
            'title' => 'Other Module Lesson',
            'description' => 'keyword-one',
            'is_published' => false,
        ]);

        Lesson::factory()->create([
            'module_id' => $otherModule->id,
            'title' => 'Foreign Instructor Lesson',
            'description' => 'keyword-one',
            'is_published' => false,
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.lessons.index', [
            'module_id' => $moduleA->id,
            'lesson_status' => 'inactive',
            'search' => 'Target',
        ]));

        $response->assertOk();
        $response->assertSee('Target Lesson Match');
        $response->assertDontSee('Active Lesson Same Module');
        $response->assertDontSee('Other Module Lesson');
        $response->assertDontSee('Foreign Instructor Lesson');
        $response->assertSee('name="module_id"', false);
        $response->assertSee('name="lesson_status"', false);
        $response->assertSee('name="search"', false);
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