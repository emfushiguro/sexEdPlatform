<?php

namespace Tests\Feature\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Tests\TestCase;

class LearnerModuleCapacityBehaviorTest extends TestCase
{
    public function test_at_capacity_module_routes_new_enrollment_to_pending_queue(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);

        $existingLearner = User::factory()->create(['role' => 'learner', 'birthdate' => now()->subYears(20)->toDateString()]);
        $existingLearner->assignRole('learner');
        LearnerProfile::create([
            'user_id' => $existingLearner->id,
            'username' => 'existing_learner',
            'birthdate' => $existingLearner->birthdate,
        ]);

        $newLearner = User::factory()->create(['role' => 'learner', 'birthdate' => now()->subYears(20)->toDateString()]);
        $newLearner->assignRole('learner');
        LearnerProfile::create([
            'user_id' => $newLearner->id,
            'username' => 'new_learner',
            'birthdate' => $newLearner->birthdate,
        ]);

        $module = Module::factory()->create([
            'is_published' => true,
            'enrollment_mode' => 'auto',
            'enrollment_limit' => 1,
            'min_age' => 18,
            'max_age' => 25,
        ]);

        ModuleEnrollment::create([
            'user_id' => $existingLearner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($newLearner)
            ->post(route('learner.modules.enroll', $module))
            ->assertRedirect(route('learner.modules.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('module_enrollments', [
            'user_id' => $newLearner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Pending->value,
        ]);
    }
}
