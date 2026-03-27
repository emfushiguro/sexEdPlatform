<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_search_modules(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');
        Module::factory()->create(['title' => 'Puberty Basics', 'created_by' => $instructor->id]);

        $this->actingAs($instructor)
             ->getJson(route('instructor.search', ['q' => 'Puberty']))
             ->assertOk()
             ->assertJsonStructure(['modules', 'lessons', 'learners']);
    }

    public function test_search_requires_authentication(): void
    {
        $response = $this->getJson(route('instructor.search', ['q' => 'test']));
        // Unauthenticated JSON requests get 401 or redirect
        $this->assertContains($response->status(), [401, 302, 301]);
    }

    public function test_short_query_returns_empty_results(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
             ->getJson(route('instructor.search', ['q' => 'a']))
             ->assertOk()
             ->assertJson(['modules' => [], 'lessons' => [], 'learners' => []]);
    }

    public function test_search_only_returns_instructors_own_modules(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $otherInstructor = User::factory()->create();
        $otherInstructor->assignRole('instructor');

        Module::factory()->create(['title' => 'Puberty Basics', 'created_by' => $instructor->id]);
        Module::factory()->create(['title' => 'Puberty Advanced', 'created_by' => $otherInstructor->id]);

        $response = $this->actingAs($instructor)
             ->getJson(route('instructor.search', ['q' => 'Puberty']))
             ->assertOk();

        $modules = $response->json('modules');
        $this->assertCount(1, $modules);
        $this->assertStringContainsString('Puberty Basics', $modules[0]['title']);
    }
}
