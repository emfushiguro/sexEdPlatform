<?php

namespace Tests\Feature\DependentSupport;

use App\Models\DependentSupportInformationAudit;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Admin\UserRelationshipService;
use App\Services\GuardianRelationshipVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DependentSupportPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_revocation_clears_only_the_affected_guardian_access_and_audits_it(): void
    {
        [$admin, $guardianA, $dependent] = $this->actors();
        $guardianB = $this->guardian();
        $relationshipA = $this->relationship($guardianA, $dependent);
        $relationshipB = $this->relationship($guardianB, $dependent);

        app(GuardianRelationshipVerificationService::class)->revoke(
            $relationshipA,
            $admin,
            'cannot_verify',
            'Evidence was invalidated.',
        );

        $this->assertFalse($relationshipA->fresh()->can_manage_support_information);
        $this->assertTrue($relationshipB->fresh()->can_manage_support_information);
        $this->assertDatabaseHas('dependent_support_information_audits', [
            'dependent_user_id' => $dependent->id,
            'parent_child_account_id' => $relationshipA->id,
            'action' => DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED,
        ]);
        $this->assertDatabaseMissing('dependent_support_information_audits', [
            'parent_child_account_id' => $relationshipB->id,
            'action' => DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED,
        ]);
    }

    public function test_rejection_and_pending_claim_closure_clear_access(): void
    {
        [$admin, $guardian, $dependent] = $this->actors();
        $verification = app(GuardianRelationshipVerificationService::class);

        $resubmission = $this->relationship($guardian, $dependent, [
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            'relationship_verified_at' => null,
        ]);
        $verification->reject($resubmission, $admin, 'insufficient_support', 'More evidence is required.');

        $final = $this->relationship($this->guardian(), $dependent, [
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            'relationship_verified_at' => null,
        ]);
        $verification->reject($final, $admin, 'not_eligible', 'The relationship could not be verified.', false);

        $closed = $this->relationship($this->guardian(), $dependent, [
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'relationship_verified_at' => null,
        ]);
        $verification->closePendingClaim($closed, $admin, 'Claim withdrawn.');

        $this->assertFalse($resubmission->fresh()->can_manage_support_information);
        $this->assertFalse($final->fresh()->can_manage_support_information);
        $this->assertFalse($closed->fresh()->can_manage_support_information);
        $this->assertSame(3, DependentSupportInformationAudit::query()
            ->where('dependent_user_id', $dependent->id)
            ->where('action', DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED)
            ->count());
    }

    public function test_deactivation_reactivation_and_approval_do_not_restore_access(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$admin, $guardian, $dependent] = $this->actors();
        $verification = app(GuardianRelationshipVerificationService::class);
        $relationship = $this->relationship($guardian, $dependent, [
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'relationship_verified_at' => null,
            'current_evidence_round' => 0,
        ]);
        $approved = $verification->approve(
            $verification->submit($relationship, $guardian, [$this->document()], null),
            $admin,
        );

        $deactivated = $verification->deactivate($approved, $guardian, 'No longer needed.');
        $this->assertFalse($deactivated->fresh()->can_manage_support_information);

        $reactivating = $verification->requestReactivation($deactivated, $dependent);
        $this->assertFalse($reactivating->fresh()->can_manage_support_information);

        $approved = $verification->approve($reactivating, $admin);

        $this->assertFalse($approved->fresh()->can_manage_support_information);
    }

    public function test_approval_preserves_access_preconfigured_during_new_dependent_registration(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$admin, $guardian, $dependent] = $this->actors();
        $relationship = $this->relationship($guardian, $dependent, [
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'relationship_verified_at' => null,
            'current_evidence_round' => 0,
        ]);
        $verification = app(GuardianRelationshipVerificationService::class);

        $submitted = $verification->submit($relationship, $guardian, [$this->document()], null);
        $approved = $verification->approve($submitted, $admin);

        $this->assertTrue($approved->fresh()->can_manage_support_information);
    }

    public function test_admin_attachment_resets_stale_access_on_a_restored_relationship(): void
    {
        [$admin, $guardian, $dependent] = $this->actors();
        $relationship = $this->relationship($guardian, $dependent);
        $relationship->delete();

        $restored = app(UserRelationshipService::class)->attachParentChild([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'aunt',
            'relationship_notes' => null,
        ], $admin->id);

        $this->assertFalse($restored->fresh()->can_manage_support_information);
    }

    /** @return array{0: User, 1: User, 2: User} */
    private function actors(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        return [$admin, $this->guardian(), $this->dependent()];
    }

    private function guardian(): User
    {
        $guardian = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $guardian->assignRole('learner');

        return $guardian;
    }

    private function dependent(): User
    {
        $dependent = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $dependent->assignRole('learner');

        return $dependent;
    }

    /** @param array<string, mixed> $overrides */
    private function relationship(User $guardian, User $dependent, array $overrides = []): ParentChildAccount
    {
        return ParentChildAccount::query()->create(array_merge([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'biological_mother',
            'verification_pathway' => 'biological_parent',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'current_evidence_round' => 1,
            'relationship_verified_at' => now(),
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'can_manage_support_information' => true,
            'verification_status' => 'approved',
        ], $overrides));
    }

    /** @return array{document_type: string, document_side: string, pairing_key: null, file: UploadedFile} */
    private function document(): array
    {
        return [
            'document_type' => 'civil_registry_record',
            'document_side' => 'not_applicable',
            'pairing_key' => null,
            'file' => UploadedFile::fake()->create('record.pdf', 100, 'application/pdf'),
        ];
    }
}
