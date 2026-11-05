<?php

namespace Tests\Feature\Admin;

use App\Models\InstructorApplication;
use App\Models\InstructorIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CentralizedInstructorApplicationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config([
            'instructor_application.enabled' => true,
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-only',
        ]);
    }

    public function test_unified_queue_contains_identity_cases_and_standalone_stage_two_applications(): void
    {
        $identityApplicant = $this->applicant('identity@example.test');
        $this->pendingIdentityCase($identityApplicant);

        $standalone = $this->stageTwoApplication($this->applicant('legacy@example.test'));
        $reviewer = $this->adminWith('review instructor identities');

        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.index'))
            ->assertOk()
            ->assertSee('Instructor Applications')
            ->assertSee('Applications table')
            ->assertSee('data-testid="unified-instructor-applications-table"', false)
            ->assertDontSee('Stage Two applications without an identity case')
            ->assertSee($identityApplicant->email)
            ->assertSee($standalone->user->email)
            ->assertSee(route('admin.instructor-applications.review', $standalone), false);
    }

    public function test_linked_identity_detail_renders_stage_two_documents_and_actions(): void
    {
        $applicant = $this->applicant('linked@example.test');
        $case = $this->pendingIdentityCase($applicant);
        $application = $this->stageTwoApplication($applicant, $case);
        $reviewer = $this->adminWith(['review instructor identities', 'review instructor applications']);

        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.show', $case))
            ->assertOk()
            ->assertSee('Stage Two application')
            ->assertSee('NBI / Police clearance')
            ->assertSee(route('admin.instructor-applications.approve', $application), false)
            ->assertSee(route('admin.instructor-applications.reject', $application), false);
    }

    public function test_stage_two_filter_keeps_a_linked_pending_application_in_the_unified_queue(): void
    {
        $applicant = $this->applicant('linked-stage-two@example.test');
        $case = $this->pendingIdentityCase($applicant);
        $case->update(['status' => InstructorIdentityVerification::STATUS_APPROVED]);
        $this->stageTwoApplication($applicant, $case);
        $reviewer = $this->adminWith(['review instructor identities', 'review instructor applications']);

        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.index', [
                'status' => InstructorIdentityVerification::STATUS_PENDING_REVIEW,
                'stage' => 'stage_two',
            ]))
            ->assertOk()
            ->assertSee($applicant->email)
            ->assertSee('Stage One + Stage Two');
    }

    public function test_requested_document_must_be_replaced_before_approval(): void
    {
        Storage::fake('local');
        Notification::fake();
        config(['instructor_application.document_disk' => 'local']);
        $applicant = $this->applicant('replacement@example.test');
        $application = $this->stageTwoApplication($applicant);
        $reviewer = $this->adminWith('review instructor applications');

        $this->actingAs($reviewer)->post(route('admin.instructor-applications.request-documents', $application), [
            'slots' => ['clearance'], 'note' => 'Please submit a clearer scan.',
        ])->assertRedirect();
        $this->assertSame(['clearance'], $application->fresh()->application_metadata['document_change_request']['slots']);
        $this->actingAs($this->applicant('another@example.test'))
            ->get(route('instructor-application.documents.requested', $application))->assertForbidden();
        $this->actingAs($reviewer)->post(route('admin.instructor-applications.approve', $application), [
            'admin_message' => 'Approved.',
        ])->assertSessionHasErrors('application');
        $this->actingAs($reviewer)->get(route('admin.instructor-applications.identities.index', ['status' => 'action_required']))
            ->assertOk()->assertSee($applicant->email);

        $this->actingAs($applicant)->post(route('instructor-application.documents.replace', $application), [
            'request_token' => (string) Str::uuid(),
            'clearance' => UploadedFile::fake()->createWithContent('clearance.pdf', '%PDF-1.4 test'),
        ])->assertRedirect();
        $this->assertNotEmpty($application->fresh()->application_metadata['document_change_request']['slots']);

        $this->actingAs($applicant)->post(route('instructor-application.documents.replace', $application), [
            'request_token' => $application->fresh()->application_metadata['document_change_request']['token'],
            'clearance' => UploadedFile::fake()->createWithContent('clearance.pdf', '%PDF-1.4 test'),
        ])->assertRedirect();
        $fresh = $application->fresh();
        $this->assertEmpty($fresh->application_metadata['document_change_request'] ?? null);
        Storage::disk('local')->assertExists($fresh->clearance_path);
        $this->assertSame('instructor-applications/cv.pdf', $fresh->cv_resume_path);
    }

    public function test_legacy_stage_two_index_redirects_to_the_canonical_queue(): void
    {
        $reviewer = $this->adminWith('review instructor applications');

        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.index'))
            ->assertRedirect(route('admin.instructor-applications.identities.index'));
    }

    public function test_stage_two_reviewer_can_view_queue_but_cannot_submit_stage_one_decision(): void
    {
        $applicant = $this->applicant('stage-two-only@example.test');
        $case = $this->pendingIdentityCase($applicant);
        $reviewer = $this->adminWith('review instructor applications');
        $this->revokePermission($reviewer, 'review instructor identities');

        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.index'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('admin.instructor-applications.identities.reject', $case), [
                'submission_round' => $case->submission_round,
                'persona_inquiry_id' => $case->personaInquiries()->value('id'),
                'reason_code' => 'identity_mismatch',
            ])
            ->assertForbidden();
    }

    public function test_stage_two_decision_from_combined_detail_returns_to_identity_review(): void
    {
        $applicant = $this->applicant('decision@example.test');
        $case = $this->pendingIdentityCase($applicant);
        $application = $this->stageTwoApplication($applicant, $case);
        $reviewer = $this->adminWith('review instructor applications');

        $this->actingAs($reviewer)
            ->post(route('admin.instructor-applications.reject', $application), [
                'identity_case_id' => $case->id,
                'rejection_reason_code' => 'other',
                'admin_message' => 'Please provide a clearer document scan.',
            ])
            ->assertRedirect(route('admin.instructor-applications.identities.show', $case));

        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'status' => 'rejected',
        ]);
    }

    private function applicant(string $email): User
    {
        $user = User::factory()->create([
            'role' => User::ACCOUNT_TYPE_APPLICANT,
            'account_type' => User::ACCOUNT_TYPE_APPLICANT,
            'email' => $email,
            'status' => User::STATUS_ACTIVE,
            'birthdate' => now()->subYears(28)->toDateString(),
            'email_verified_at' => now(),
        ]);
        $user->assignRole(User::ACCOUNT_TYPE_APPLICANT);

        return $user->fresh();
    }

    private function adminWith(array|string $permissions): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');
        $admin->givePermissionTo((array) $permissions);

        return $admin->fresh();
    }

    private function revokePermission(User $admin, string $permission): void
    {
        $admin->roles()->where('name', 'admin')->firstOrFail()->revokePermissionTo($permission);
        $admin->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function pendingIdentityCase(User $user): InstructorIdentityVerification
    {
        $case = InstructorIdentityVerification::query()->create([
            'user_id' => $user->id,
            'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
            'status' => InstructorIdentityVerification::STATUS_PENDING_REVIEW,
            'submission_round' => 1,
            'declared_snapshot' => ['first_name' => 'Test', 'last_name' => 'Applicant'],
            'comparison_summary' => ['fields' => ['first_name' => ['outcome' => 'match']]],
            'sandbox_manual_review' => false,
            'resubmission_allowed' => true,
            'submitted_at' => now(),
        ]);

        $case->personaInquiries()->create([
            'user_id' => $user->id,
            'flow_type' => PersonaInquiry::FLOW_INSTRUCTOR_GOVERNMENT,
            'target_submission_round' => 1,
            'attempt_number' => 1,
            'local_document_type' => 'government_id',
            'local_government_id_type' => 'national_id',
            'template_key' => PersonaInquiry::TEMPLATE_GOVID_SELFIE,
            'template_id' => 'template-centralized-review',
            'reference_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'persona_inquiry_id' => null,
            'persona_status' => 'completed',
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_code' => 'PH',
            'id_class' => 'id',
            'is_sandbox' => false,
            'completed_at' => now(),
            'last_synced_at' => now(),
        ]);

        return $case->fresh();
    }

    private function stageTwoApplication(User $user, ?InstructorIdentityVerification $case = null): InstructorApplication
    {
        return InstructorApplication::query()->create([
            'user_id' => $user->id,
            'instructor_identity_verification_id' => $case?->id,
            'status' => 'pending',
            'submitted_at' => now(),
            'educational_background' => 'College Graduate',
            'government_id_path' => 'instructor-applications/government-id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'cv_resume_path' => 'instructor-applications/cv.pdf',
            'bio' => 'Experienced educator.',
        ]);
    }
}
