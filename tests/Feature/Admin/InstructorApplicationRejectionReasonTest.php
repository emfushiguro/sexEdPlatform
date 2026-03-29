<?php

namespace Tests\Feature\Admin;

use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstructorApplicationRejectionReasonTest extends TestCase
{
    use DatabaseTransactions;

    public function test_instructor_applications_table_has_structured_rejection_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('instructor_applications', 'rejection_reason_code'));
        $this->assertTrue(Schema::hasColumn('instructor_applications', 'rejection_reason_note'));
    }

    public function test_reject_requires_reason_code(): void
    {
        $admin = $this->createUserWithRole('admin');
        $application = $this->createPendingApplication();

        $this->actingAs($admin)
            ->from(route('admin.instructor-applications.show', $application))
            ->post(route('admin.instructor-applications.reject', $application), [
                'rejection_reason_code' => '',
            ])
            ->assertRedirect(route('admin.instructor-applications.show', $application))
            ->assertSessionHasErrors(['rejection_reason_code']);
    }

    public function test_reject_requires_note_when_reason_code_is_other(): void
    {
        $admin = $this->createUserWithRole('admin');
        $application = $this->createPendingApplication();

        $this->actingAs($admin)
            ->from(route('admin.instructor-applications.show', $application))
            ->post(route('admin.instructor-applications.reject', $application), [
                'rejection_reason_code' => 'other',
                'rejection_reason_note' => '',
            ])
            ->assertRedirect(route('admin.instructor-applications.show', $application))
            ->assertSessionHasErrors(['rejection_reason_note']);
    }

    private function createPendingApplication(): InstructorApplication
    {
        $learner = $this->createUserWithRole('learner');

        return InstructorApplication::query()->create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'Bachelor of Education',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'bio' => 'Experienced educator focused on inclusive classroom facilitation.',
            'teaching_credential_path' => 'instructor-applications/teaching.pdf',
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
