<?php

namespace Tests\Feature\Instructor;

use App\Enums\EnrollmentStatus;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_results_use_show_routes_for_module_lesson_and_learner(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create([
            'title' => 'Puberty Basics',
            'created_by' => $instructor->id,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'Puberty Intro',
        ]);

        $learner = User::factory()->create([
            'first_name' => 'Pia',
            'last_name' => 'Luna',
            'email' => 'pia@example.test',
            'role' => 'learner',
        ]);
        $learner->assignRole('learner');

        ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
        ]);

        $response = $this->actingAs($instructor)
            ->getJson(route('instructor.search', ['q' => 'Puberty']))
            ->assertOk();

        $response->assertJsonPath('modules.0.url', route('instructor.modules.show', $module));
        $response->assertJsonPath('lessons.0.url', route('instructor.lessons.show', $lesson));

        $learnerResponse = $this->actingAs($instructor)
            ->getJson(route('instructor.search', ['q' => 'Pia']))
            ->assertOk();

        $learnerResponse->assertJsonPath('learners.0.url', route('instructor.users.show', $learner));
    }
}
