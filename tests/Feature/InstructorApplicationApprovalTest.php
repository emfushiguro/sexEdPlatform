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
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $application = $this->makePendingApplication($learner);

        $response = $this->actingAs($admin)
            ->post(route('admin.instructor-applications.approve', $application), [
                'admin_message' => '<p>Congratulations! Your instructor application has been approved.</p>',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'approved',
        ]);
        $approvedApplication = InstructorApplication::findOrFail($application->id);
        $this->assertStringContainsString('Congratulations! Your instructor application has been approved.', (string) $approvedApplication->review_message);
        $this->assertDatabaseHas('users', [
            'id' => $learner->id,
            'role' => 'instructor',
        ]);
        $this->assertDatabaseHas('instructor_profiles', [
            'user_id' => $learner->id,
            'educational_background' => 'Bachelor of Secondary Education',
            'professional_background' => str_repeat('B', 120),
        ]);
        $this->assertTrue($learner->fresh()->hasRole('instructor'));
        $this->assertNotContains('instructor-applications/id.pdf', $learner->fresh()->instructorProfile?->credentials ?? []);
        $this->assertNotContains('instructor-applications/clearance.pdf', $learner->fresh()->instructorProfile?->credentials ?? []);
        $this->assertDatabaseHas('role_transitions', [
            'user_id' => $learner->id,
            'to_role' => 'instructor',
        ]);
        $this->assertDatabaseHas('instructor_application_reviews', [
            'instructor_application_id' => $application->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_application_with_reason(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        /** @var User $learner */
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $application = $this->makePendingApplication($learner);

        $response = $this->actingAs($admin)
            ->post(route('admin.instructor-applications.reject', $application), [
                'rejection_reason_code' => 'invalid_credentials',
                'rejection_reason_note' => 'Missing verifiable supporting documents.',
                'admin_message' => '<p>Your submission is missing verifiable supporting documents.</p>',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'rejected',
            'rejection_reason_code' => 'invalid_credentials',
            'rejection_reason_note' => 'Missing verifiable supporting documents.',
            'review_message' => '<p>Your submission is missing verifiable supporting documents.</p>',
        ]);
        $this->assertDatabaseHas('instructor_application_reviews', [
            'instructor_application_id' => $application->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
        ]);
    }
}
