<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorIdentityVerification;
use App\Models\LearnerIdentityVerification;
use App\Models\LearnerProfile;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Admin\RoleSyncService;
use App\Services\InstructorApplication\InstructorIdentityService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstructorIdentityWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['instructor_application.enabled' => true]);
    }

    public function test_direct_identity_transitions_are_audited_without_role_changes(): void
    {
        $user = $this->applicant();
        $service = app(InstructorIdentityService::class);
        $snapshot = $this->snapshot();

        $awaiting = $service->ensureAwaitingVerification($user);
        $this->assertSame(InstructorIdentityVerification::STATUS_AWAITING_VERIFICATION, $awaiting->status);
        $case = $service->startAttempt($user, $snapshot);
        $this->assertSame($awaiting->id, $case->id);
        $this->assertSame(InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS, $case->status);

        $attempt = $this->personaAttempt($user, $case);
        $service->markPendingReview($case, $attempt, ['normalization_version' => 'v1', 'fields' => []]);
        $this->assertSame(InstructorIdentityVerification::STATUS_PENDING_REVIEW, $case->fresh()->status);

        $reviewer = User::factory()->create(['role' => 'admin']);
        $reviewer->assignRole('admin');
        $service->approve($case->fresh(), $reviewer, ['declared' => true, 'provider' => true]);

        $this->assertSame('applicant', $user->fresh()->role);
        $this->assertSame(InstructorIdentityVerification::STATUS_APPROVED, $case->fresh()->status);
        $this->assertSame(4, $case->audits()->count());
        $this->assertDatabaseHas('instructor_identity_audits', [
            'instructor_identity_verification_id' => $case->id,
            'to_status' => InstructorIdentityVerification::STATUS_APPROVED,
        ]);
    }

    public function test_repeated_entry_converges_and_stale_rounds_cannot_change_a_current_case(): void
    {
        $user = $this->applicant();
        $service = app(InstructorIdentityService::class);
        $case = $service->startAttempt($user, $this->snapshot());
        $same = $service->startAttempt($user, $this->snapshot());

        $this->assertSame($case->id, $same->id);

        $case->forceFill([
            'status' => InstructorIdentityVerification::STATUS_ACTION_REQUIRED,
            'resubmission_allowed' => true,
        ])->save();
        $replacement = $service->startAttempt($user, $this->snapshot());

        $this->assertNotSame($case->id, $replacement->id);
        $this->assertSame(2, $replacement->submission_round);

        $this->expectException(DomainException::class);
        $service->markActionRequired($case->fresh(), $this->personaAttempt($user, $case), 'stale');
    }

    public function test_approved_adult_learner_identity_is_reused_without_a_persona_attempt(): void
    {
        $user = $this->learner(24);
        $learnerCase = LearnerIdentityVerification::create([
            'user_id' => $user->id,
            'pathway' => 'adult',
            'status' => 'approved',
            'submission_round' => 1,
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $case = app(InstructorIdentityService::class)->satisfyFromApprovedLearnerIdentity($user);

        $this->assertSame(InstructorIdentityVerification::SOURCE_LEARNER_IDENTITY, $case->source);
        $this->assertSame(InstructorIdentityVerification::STATUS_APPROVED, $case->status);
        $this->assertSame($learnerCase->id, $case->learner_identity_verification_id);
        $this->assertDatabaseCount('persona_inquiries', 0);
        $this->assertSame($case->id, app(InstructorIdentityService::class)->satisfyFromApprovedLearnerIdentity($user)->id);
    }

    public function test_underage_or_unapproved_learner_cannot_satisfy_stage_one(): void
    {
        $underage = $this->learner(17);
        LearnerIdentityVerification::create([
            'user_id' => $underage->id,
            'pathway' => 'teen',
            'status' => 'approved',
            'submission_round' => 1,
        ]);

        $this->expectException(DomainException::class);
        app(InstructorIdentityService::class)->satisfyFromApprovedLearnerIdentity($underage);
    }

    public function test_pending_learner_is_not_copied_into_an_approved_instructor_case(): void
    {
        $user = $this->learner(24);
        LearnerIdentityVerification::create([
            'user_id' => $user->id,
            'pathway' => 'adult',
            'status' => 'pending',
            'submission_round' => 1,
        ]);

        try {
            app(InstructorIdentityService::class)->satisfyFromApprovedLearnerIdentity($user);
            $this->fail('Expected pending learner identity to be rejected.');
        } catch (DomainException) {
            $this->assertDatabaseCount('instructor_identity_verifications', 0);
        }
    }

    public function test_dashboard_is_a_status_hub_for_direct_applicants_and_pending_learners(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($applicant)
            ->get(route('instructor-application.dashboard'))
            ->assertOk()
            ->assertSee('Awaiting Verification')
            ->assertSee('Identity review');

        $learner = $this->learner(24);
        LearnerIdentityVerification::create([
            'user_id' => $learner->id,
            'pathway' => 'adult',
            'status' => 'pending',
            'submission_round' => 1,
        ]);

        $this->actingAs($learner)
            ->get(route('instructor-application.dashboard'))
            ->assertOk()
            ->assertSee('Complete learner identity verification before starting an instructor application.')
            ->assertSee('Continue learner verification');
    }

    private function applicant(): User
    {
        $user = User::factory()->create([
            'role' => User::ACCOUNT_TYPE_APPLICANT,
            'account_type' => User::ACCOUNT_TYPE_APPLICANT,
            'birthdate' => now()->subYears(24)->format('Y-m-d'),
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, User::ACCOUNT_TYPE_APPLICANT);

        return $user->fresh();
    }

    private function learner(int $age): User
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'birthdate' => now()->subYears($age)->format('Y-m-d'),
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, 'learner');
        LearnerProfile::create([
            'user_id' => $user->id,
            'username' => 'learner_'.$user->id,
            'birthdate' => $user->birthdate,
            'city_code' => null,
            'barangay_code' => null,
            'barangay' => null,
        ]);

        return $user->fresh();
    }

    private function snapshot(): array
    {
        return [
            'first_name' => 'Direct',
            'middle_initial' => 'A.',
            'last_name' => 'Applicant',
            'suffix' => null,
            'birthdate' => '2000-01-01',
            'city_name' => 'Cavite City',
            'barangay_name' => 'Barangay Test',
        ];
    }

    private function personaAttempt(User $user, InstructorIdentityVerification $case): PersonaInquiry
    {
        return PersonaInquiry::create([
            'user_id' => $user->id,
            'instructor_identity_verification_id' => $case->id,
            'flow_type' => 'instructor_government',
            'target_submission_round' => $case->submission_round,
            'attempt_number' => 1,
            'local_document_type' => 'government_id',
            'local_government_id_type' => 'ph_drivers_license',
            'template_key' => PersonaInquiry::TEMPLATE_GOVID_SELFIE,
            'template_id' => 'instructor-template',
            'reference_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'is_sandbox' => false,
        ]);
    }
}
