<?php

namespace Tests\Feature\Admin;

use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminInstructorApplicationsUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_instructor_applications_index_uses_enhanced_table_structure(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $application = $this->createPendingApplication();

        $this->actingAs($admin)
            ->get(route('admin.instructor-applications.index'))
            ->assertOk()
            ->assertSeeText($application->user->name)
            ->assertSee('data-testid="applications-col-applicant"', false)
            ->assertSee('data-testid="applications-col-email"', false)
            ->assertSee('Date Applied', false)
            ->assertSee('Status', false)
            ->assertSee('Reviewed By', false)
            ->assertSee('Decision Date', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false);
    }

    public function test_instructor_application_review_modal_renders_structured_sections_and_reject_inputs(): void
    {
        $this->withoutVite();

        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $application = $this->createPendingApplication();

        $this->actingAs($admin)
            ->get(route('admin.instructor-applications.index', ['focus' => $application->id]))
            ->assertOk()
            ->assertSee('data-testid="review-application-button-' . $application->id . '"', false)
            ->assertSee('data-testid="application-review-modal-' . $application->id . '"', false)
            ->assertSee('Section 1 - Application Information', false)
            ->assertSee('Section 2 - Submitted Documents', false)
            ->assertSee('Section 3 - Learner Data Snapshot', false)
            ->assertSee('Section 4 - Moderation History', false)
            ->assertSee('Section 5 - Moderation Actions', false)
            ->assertSee('Finished Modules Breakdown', false)
            ->assertSee('name="rejection_reason_code"', false)
            ->assertSee('name="rejection_reason_note"', false)
            ->assertSee('name="admin_message"', false)
            ->assertSee('Reject Application', false);
    }

    private function createPendingApplication(): InstructorApplication
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        $learner->learnerProfile()->create([
            'username' => 'ui-check-learner',
            'birthdate' => now()->subYears(20)->toDateString(),
            'city_code' => null,
            'barangay' => 'Barangay Test',
            'barangay_code' => null,
        ]);

        return InstructorApplication::query()->create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'Bachelor of Secondary Education',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'cv_resume_path' => 'instructor-applications/cv_resume.pdf',
            'bio' => 'Professional educator focused on adolescent development and evidence-based facilitation.',
            'teaching_credential_path' => 'instructor-applications/teaching.pdf',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
    }
}
