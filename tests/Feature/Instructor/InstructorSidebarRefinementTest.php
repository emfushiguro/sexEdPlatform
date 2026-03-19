<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorSidebarRefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_sidebar_structure_is_present()
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertStatus(200);
        
        // Assert Sidebar Container exists
        $response->assertSee('id="instructor-sidebar"', false);
        
        // Assert Branding text
        $response->assertSee('Conscious', false);
        $response->assertSee('Connections', false);
    }

    public function test_instructor_sidebar_contains_correct_navigation_links()
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $routes = [
            route('instructor.dashboard') => 'Dashboard',
            route('instructor.modules.index') => 'Modules',
            route('instructor.users.index') => 'Learners',
            route('instructor.image-library.index') => 'Image Library',
        ];

        foreach ($routes as $url => $label) {
            $response->assertSee($url, false);
            $response->assertSee($label, false);
        }
    }

    public function test_sidebar_is_responsive_ready()
    {
        // Verify accessibility/structure for later JS hook
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));
        
        // Ensure we have the Alpine.js store bound to the sidebar
        $response->assertSee('x-data="instructorSidebar"', false);
    }
}
