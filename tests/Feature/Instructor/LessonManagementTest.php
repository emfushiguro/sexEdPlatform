<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_create_page_loads_for_instructor(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->createOne();
        $instructor->assignRole('instructor');

        Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.lessons.create'))
            ->assertOk();
    }
}
