<?php

namespace Tests\Feature\Admin;

use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminSharedEnrollmentManagementTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_cannot_approve_enrollment_for_instructor_module(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');
        $learner = $this->createUser('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'enrollment_mode' => 'manual',
        ]);

        $enrollment = ModuleEnrollment::query()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.enrollments.approve', $enrollment));

        $response->assertForbidden();

        $this->assertDatabaseHas('module_enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::Pending->value,
        ]);
    }

    public function test_admin_can_approve_enrollment_for_admin_owned_module(): void
    {
        $admin = $this->createUser('admin');
        $learner = $this->createUser('learner');

        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'enrollment_mode' => 'manual',
        ]);

        $enrollment = ModuleEnrollment::query()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.enrollments.approve', $enrollment));

        $response->assertRedirect();

        $this->assertDatabaseHas('module_enrollments', [
            'id' => $enrollment->id,
            'status' => EnrollmentStatus::Approved->value,
        ]);
    }

    public function test_instructor_cannot_approve_enrollment_for_another_instructor_module(): void
    {
        $owner = $this->createUser('instructor');
        $otherInstructor = $this->createUser('instructor');
        $learner = $this->createUser('learner');

        $module = Module::factory()->create([
            'created_by' => $owner->id,
            'content_owner_type' => 'instructor',
            'enrollment_mode' => 'manual',
        ]);

        $enrollment = ModuleEnrollment::query()->create([
            'module_id' => $module->id,
            'user_id' => $learner->id,
            'status' => EnrollmentStatus::Pending,
        ]);

        $this->actingAs($otherInstructor)
            ->patch(route('instructor.enrollments.approve', $enrollment))
            ->assertForbidden();
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
