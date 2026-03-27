<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorEnrollmentsRefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollments_page_has_modern_layout_markers()
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        // Create a module belonging to this instructor
        $module = Module::factory()->create(['created_by' => $instructor->id]);

        // Create a pending enrollment
        $learner = User::factory()->create();
        $modEnrollment = ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.enrollments.index'));

        $response->assertStatus(200);
        
        // Assert modern page header exists with left border
        $response->assertSee('Enrollments', false);
        
        // Assert refined card layout marker for status filtering
        $response->assertSee('data-enrollment-list', false);
        
        // Assert learner card section exists
        $response->assertSee($learner->name ?? $learner->first_name, false);
        $response->assertSee($module->title, false);
    }

    public function test_enrollments_page_shows_status_filter_tabs()
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $learner = User::factory()->create();
        
        ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.enrollments.index'));

        // Assert status filter tabs exist
        $response->assertSee('All', false);
        $response->assertSee('Pending', false);
        $response->assertSee('Approved', false);
        $response->assertSee('Rejected', false);
    }

    public function test_enrollments_page_approval_actions_are_visible()
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $learner = User::factory()->create();
        
        $enrollment = ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.enrollments.index'));

        // Assert approval action links exist
        $response->assertSee(route('instructor.enrollments.show', $enrollment), false);
        $response->assertSee(route('instructor.enrollments.approve', $enrollment), false);
        $response->assertSee(route('instructor.enrollments.reject', $enrollment), false);
    }
}
