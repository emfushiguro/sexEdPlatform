<?php

namespace Tests\Feature\Instructor;

use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_only_sees_learners_enrolled_in_owned_modules(): void
    {
        $instructor = $this->createInstructor();
        $otherInstructor = $this->createInstructor();

        $ownedModule = Module::factory()->create([
            'title' => 'Owned Module',
            'created_by' => $instructor->id,
        ]);

        $otherModule = Module::factory()->create([
            'title' => 'Other Module',
            'created_by' => $otherInstructor->id,
        ]);

        $visibleLearner = $this->createLearner('Visible', 'Learner', 'visible@example.test');
        $hiddenLearner = $this->createLearner('Hidden', 'Learner', 'hidden@example.test');

        ModuleEnrollment::factory()->create([
            'module_id' => $ownedModule->id,
            'user_id' => $visibleLearner->id,
            'status' => EnrollmentStatus::Approved,
        ]);

        ModuleEnrollment::factory()->create([
            'module_id' => $otherModule->id,
            'user_id' => $hiddenLearner->id,
            'status' => EnrollmentStatus::Approved,
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.users.index'));

        $response->assertOk();
        $response->assertViewHas('users', function ($users) use ($visibleLearner, $hiddenLearner) {
            $ids = $users->pluck('id');

            return $ids->contains($visibleLearner->id)
                && ! $ids->contains($hiddenLearner->id);
        });
    }

    private function createInstructor(): User
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        return $instructor;
    }

    private function createLearner(string $firstName, string $lastName, string $email): User
    {
        $learner = User::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'role' => 'learner',
        ]);

        $learner->assignRole('learner');

        return $learner;
    }
}
