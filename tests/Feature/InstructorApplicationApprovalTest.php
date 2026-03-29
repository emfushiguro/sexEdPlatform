<?php

namespace Tests\Feature;

use App\Models\InstructorApplication;
use App\Models\User;
use Tests\TestCase;

class InstructorApplicationApprovalTest extends TestCase
{
    private function makePendingApplication(User $learner): InstructorApplication
    {
        return InstructorApplication::create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'Bachelor of Secondary Education',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'bio' => str_repeat('B', 120),
            'teaching_credential_path' => 'instructor-applications/teaching.pdf',
        ]);
    }

    public function test_admin_can_approve_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $application = $this->makePendingApplication($learner);

        $response = $this->actingAs($admin)
            ->post(route('admin.instructor-applications.approve', $application));

        $response->assertRedirect();
        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'role' => 'instructor',
        ]);
        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $learner->id,
            'educational_background' => 'Bachelor of Secondary Education',
            'professional_background' => str_repeat('B', 120),
        ]);
        $this->assertDatabaseHas('role_transitions', [
            'user_id' => $learner->id,
            'to_role' => 'instructor',
        ]);
    }

    public function test_admin_can_reject_application_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $application = $this->makePendingApplication($learner);

        $response = $this->actingAs($admin)
            ->post(route('admin.instructor-applications.reject', $application), [
                'rejection_reason' => 'Missing verifiable supporting documents.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'rejected',
        ]);
    }
}
