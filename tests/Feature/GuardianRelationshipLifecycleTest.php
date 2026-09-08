<?php

namespace Tests\Feature;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class GuardianRelationshipLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_approval_and_revocation_use_explicit_states(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$admin, $guardian, $dependent, $relationship] = $this->actorsAndPendingRelationship();

        $submitted = app(GuardianRelationshipVerificationService::class)->submit(
            $relationship,
            $guardian,
            [[
                'document_type' => 'civil_registry_record',
                'document_side' => 'not_applicable',
                'pairing_key' => null,
                'file' => UploadedFile::fake()->create('record.pdf', 100, 'application/pdf'),
            ]],
            null,
        );

        $this->assertSame('pending', $submitted->relationship_status);
        $this->assertSame('under_review', $submitted->relationship_verified_status);
        $this->assertSame(1, $submitted->current_evidence_round);

        $approved = app(GuardianRelationshipVerificationService::class)->approve($submitted, $admin);
        $this->assertSame('active', $approved->relationship_status);
        $this->assertSame('verified', $approved->relationship_verified_status);

        $revoked = app(GuardianRelationshipVerificationService::class)->revoke(
            $approved,
            $admin,
            'cannot_verify',
            'Evidence was later invalidated.',
        );
        $this->assertSame('revoked', $revoked->relationship_status);
        $this->assertSame('revoked', $revoked->relationship_verified_status);
    }

    public function test_revoking_one_guardian_does_not_change_another_guardian(): void
    {
        [$admin, $firstGuardian, $dependent, $first] = $this->actorsAndVerifiedRelationship();
        $secondGuardian = $this->approvedGuardian();
        $second = $this->verifiedRelationship($secondGuardian, $dependent);

        app(GuardianRelationshipVerificationService::class)->revoke($first, $admin, 'cannot_verify', null);

        $this->assertSame('revoked', $first->fresh()->relationship_status);
        $this->assertSame('active', $second->fresh()->relationship_status);
        $this->assertSame('verified', $second->fresh()->relationship_verified_status);
    }

    public function test_admin_cannot_approve_without_current_round_evidence(): void
    {
        [$admin, $guardian, $dependent, $relationship] = $this->actorsAndPendingRelationship();
        $relationship->update(['relationship_verified_status' => 'under_review']);

        $this->expectException(InvalidArgumentException::class);
        app(GuardianRelationshipVerificationService::class)->approve($relationship, $admin);
    }

    public function test_admin_can_close_an_unsubmitted_or_resubmission_required_claim(): void
    {
        [$admin, $guardian, $dependent, $relationship] = $this->actorsAndPendingRelationship();

        $closed = app(GuardianRelationshipVerificationService::class)
            ->closePendingClaim($relationship, $admin, 'Claim was withdrawn before submission.');

        $this->assertSame(ParentChildAccount::STATUS_REJECTED, $closed->relationship_status);
        $this->assertSame(ParentChildAccount::VERIFICATION_REJECTED, $closed->relationship_verified_status);
        $this->assertSame('claim_closed', $closed->relationship_verification_rejection_reason);
        $this->assertDatabaseHas('guardian_relationship_verification_audits', [
            'parent_child_account_id' => $relationship->id,
            'action' => 'claim_closed',
            'previous_status' => ParentChildAccount::VERIFICATION_PENDING,
        ]);
        $this->assertFalse(ParentChildAccount::accessEligible()->whereKey($relationship->id)->exists());
    }

    public function test_resubmission_creates_a_new_round_and_marks_only_the_previous_round_superseded(): void
    {
        Storage::fake('local');
        Notification::fake();
        [$admin, $guardian, $dependent, $relationship] = $this->actorsAndPendingRelationship();
        $service = app(GuardianRelationshipVerificationService::class);

        $service->submit($relationship, $guardian, [$this->document('first.pdf')], null);
        $firstDocument = $relationship->verificationDocuments()->firstOrFail();
        $firstPath = $firstDocument->path;

        $service->reject($relationship, $admin, 'insufficient_support', 'More evidence is required.', true);
        $service->submit($relationship->fresh(), $guardian, [$this->document('second.pdf')], null);

        $firstDocument = $firstDocument->fresh();
        $secondDocument = $relationship->fresh()->verificationDocuments()
            ->where('submission_round', 2)
            ->firstOrFail();

        $this->assertSame(1, $firstDocument->submission_round);
        $this->assertNotNull($firstDocument->superseded_at);
        $this->assertSame($firstPath, $firstDocument->path);
        $this->assertSame(2, $secondDocument->submission_round);
        $this->assertNull($secondDocument->superseded_at);
        $this->assertDatabaseHas('guardian_relationship_verification_audits', [
            'parent_child_account_id' => $relationship->id,
            'action' => 'resubmitted',
            'submission_round' => 2,
        ]);
    }

    public function test_verified_relationship_can_be_deactivated_and_reactivation_requires_review(): void
    {
        [$admin, $guardian, $dependent, $relationship] = $this->actorsAndVerifiedRelationship();
        $service = app(GuardianRelationshipVerificationService::class);

        $deactivated = $service->deactivate($relationship, $guardian, 'No longer needed.');
        $this->assertSame(ParentChildAccount::STATUS_INACTIVE, $deactivated->relationship_status);
        $this->assertSame(ParentChildAccount::VERIFICATION_VERIFIED, $deactivated->relationship_verified_status);
        $this->assertFalse(ParentChildAccount::accessEligible()->whereKey($relationship->id)->exists());

        $reactivating = $service->requestReactivation($deactivated, $dependent);
        $this->assertSame(ParentChildAccount::STATUS_PENDING, $reactivating->relationship_status);
        $this->assertSame(ParentChildAccount::VERIFICATION_UNDER_REVIEW, $reactivating->relationship_verified_status);
    }

    public function test_relationship_parties_can_request_deactivation_and_only_parties_can_request_reactivation(): void
    {
        [$admin, $guardian, $dependent, $relationship] = $this->actorsAndVerifiedRelationship();
        $guardian->forceFill(['email_verified_at' => now()])->save();
        $dependent->forceFill(['email_verified_at' => now()])->save();
        $unrelated = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($dependent)
            ->post(route('guardian-relationships.deactivate', $relationship), ['note' => 'No longer needed.'])
            ->assertRedirect();

        $this->assertDatabaseHas('parent_child_accounts', [
            'id' => $relationship->id,
            'relationship_status' => ParentChildAccount::STATUS_INACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
        ]);

        $this->actingAs($unrelated)
            ->post(route('guardian-relationships.reactivate', $relationship))
            ->assertForbidden();
    }

    /** @return array{0: User, 1: User, 2: User, 3: ParentChildAccount} */
    private function actorsAndPendingRelationship(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');
        $guardian = $this->approvedGuardian();
        $dependent = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $dependent->assignRole('learner');

        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'biological_mother',
            'verification_pathway' => 'biological_parent',
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'current_evidence_round' => 0,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'verification_status' => 'approved',
        ]);

        return [$admin, $guardian, $dependent, $relationship];
    }

    /** @return array{0: User, 1: User, 2: User, 3: ParentChildAccount} */
    private function actorsAndVerifiedRelationship(): array
    {
        [$admin, $guardian, $dependent, $relationship] = $this->actorsAndPendingRelationship();
        $relationship->update([
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'current_evidence_round' => 1,
            'relationship_verified_at' => now(),
        ]);

        return [$admin, $guardian, $dependent, $relationship->fresh()];
    }

    private function approvedGuardian(): User
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

    private function verifiedRelationship(User $guardian, User $dependent): ParentChildAccount
    {
        return ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'aunt',
            'verification_pathway' => 'non_parental_care',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'current_evidence_round' => 1,
            'relationship_verified_at' => now(),
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'verification_status' => 'approved',
        ]);
    }

    /** @return array{document_type: string, document_side: string, pairing_key: null, file: UploadedFile} */
    private function document(string $name): array
    {
        return [
            'document_type' => 'civil_registry_record',
            'document_side' => 'not_applicable',
            'pairing_key' => null,
            'file' => UploadedFile::fake()->create($name, 100, 'application/pdf'),
        ];
    }
}
