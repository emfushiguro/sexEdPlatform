<?php

namespace Tests\Feature\Instructor;

use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_view_owned_module_with_enrolled_learners(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        /** @var User $learner */
        $learner = User::factory()->create([
            'first_name' => 'Aira',
            'last_name' => 'Lopez',
            'email' => 'aira@example.test',
        ]);

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'title' => 'Healthy Boundaries',
        ]);

        ModuleEnrollment::factory()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => EnrollmentStatus::Approved,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.modules.show', $module))
            ->assertOk()
            ->assertSee('Enrolled Learners', false)
            ->assertSee('Healthy Boundaries', false)
            ->assertSee('aira@example.test', false);
    }

    public function test_instructor_cannot_view_module_owned_by_other_instructor(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        $owner->assignRole('instructor');

        /** @var User $otherInstructor */
        $otherInstructor = User::factory()->create();
        $otherInstructor->assignRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $owner->id,
        ]);

        $this->actingAs($otherInstructor)
            ->get(route('instructor.modules.show', $module))
            ->assertStatus(403);
    }
}
