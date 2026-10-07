<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminConnectorVerificationDecisionTest extends TestCase
{
    use ConnectorTestHelpers;

    private const CHECKLIST = [
        'organization_identity_established' => '1',
        'regulatory_evidence_resolved' => '1',
        'representative_identity_verified' => '1',
        'representative_authority_established' => '1',
        'required_evidence_complete' => '1',
        'application_consistent' => '1',
    ];

    public function test_approval_requires_complete_evidence_and_exact_checklist(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);

        $this->actingAs($admin)
            ->post(route('admin.connectors.approve', $connector), [
                'submission_round' => 1,
                'checklist' => self::CHECKLIST,
            ])
            ->assertStatus(422);

        $this->completeCurrentEvidence($connector);

        $checklist = self::CHECKLIST;
        $checklist['application_consistent'] = '0';

        $this->actingAs($admin)
            ->from(route('admin.connectors.show', $connector))
            ->post(route('admin.connectors.approve', $connector), [
                'submission_round' => 1,
                'checklist' => $checklist,
            ])
            ->assertRedirect(route('admin.connectors.show', $connector))
            ->assertSessionHasErrors('checklist.application_consistent');

        $this->assertSame('pending', $connector->fresh()->status);
    }

    public function test_valid_approval_activates_only_primary_pending_membership_and_preserves_evidence(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);
        $member = $this->createAdultConnectorMember($connector);
        $documentIds = $connector->verificationDocuments()->pluck('id')->all();
        $this->completeCurrentEvidence($connector);

        $this->actingAs($admin)
            ->post(route('admin.connectors.approve', $connector), [
                'submission_round' => 1,
                'checklist' => self::CHECKLIST,
                'internal_note' => 'All required evidence reviewed.',
            ])
            ->assertRedirect();

        $fresh = $connector->fresh();
        $this->assertSame('verified', $fresh->status);
        $this->assertSame(Connector::VERIFICATION_VERIFIED, $fresh->verification_status);
        $this->assertTrue($fresh->memberships()->where('user_id', $owner->id)->where('status', 'active')->exists());
        $this->assertTrue($fresh->memberships()->where('user_id', $member->id)->where('status', 'active')->exists());
        $this->assertSame($documentIds, $fresh->verificationDocuments()->pluck('id')->all());
        $this->assertTrue($fresh->reviews()->where('to_status', 'verified')->where('submission_round', 1)->exists());
        $this->assertTrue($fresh->verificationAudits()->where('action', 'connector_approved')->exists());

        $this->actingAs($admin)
            ->post(route('admin.connectors.approve', $connector), [
                'submission_round' => 1,
                'checklist' => self::CHECKLIST,
            ])
            ->assertStatus(409);
    }

    public function test_stale_round_and_non_pending_connectors_cannot_be_approved(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);
        $connector->update(['current_submission_round' => 2]);
        $this->completeCurrentEvidence($connector);

        $this->actingAs($admin)
            ->post(route('admin.connectors.approve', $connector), [
                'submission_round' => 1,
                'checklist' => self::CHECKLIST,
            ])
            ->assertStatus(409);

        $verified = $this->createVerifiedConnector(User::factory()->create());
        $verified->update([
            'verification_status' => Connector::VERIFICATION_LEGACY_VERIFIED,
            'current_submission_round' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.connectors.approve', $verified), [
                'submission_round' => 1,
                'checklist' => self::CHECKLIST,
            ])
            ->assertStatus(409);
    }

    public function test_request_resubmission_requires_an_eligible_requirement(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);
        $document = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'representative_authority'))
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.documents.review', [$connector, $document]), [
                'submission_round' => 1,
                'decision' => 'reject',
                'applicant_reason' => 'Upload a clearer authorization.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.request-resubmission', $connector), [
                'submission_round' => 1,
                'applicant_reason' => 'Please replace the rejected authorization.',
                'internal_note' => 'Waiting for a clearer board resolution.',
            ])
            ->assertRedirect(route('admin.connectors.show', $connector));

        $this->assertSame(
            Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            $connector->fresh()->verification_status,
        );
        $this->assertTrue($connector->reviews()->where('reason_code', 'resubmission_requested')->exists());
        $this->assertTrue($connector->verificationAudits()->where('action', 'resubmission_requested')->exists());
    }

    public function test_permanent_rejection_preserves_evidence_and_requires_allow_listed_reason(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);
        $documentCount = $connector->verificationDocuments()->count();

        $this->actingAs($admin)
            ->from(route('admin.connectors.show', $connector))
            ->post(route('admin.connectors.reject', $connector), [
                'submission_round' => 1,
                'reason_code' => 'ineligible',
                'applicant_reason' => 'The organization could not establish eligibility.',
                'internal_note' => 'Reviewed the submitted identity materials.',
            ])
            ->assertRedirect(route('admin.connectors.show', $connector));

        $fresh = $connector->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame(Connector::VERIFICATION_REJECTED, $fresh->verification_status);
        $this->assertSame('The organization could not establish eligibility.', $fresh->rejection_reason);
        $this->assertSame($documentCount, $fresh->verificationDocuments()->count());
        $this->assertTrue($fresh->reviews()->where('reason_code', 'permanently_rejected')->exists());
        $this->assertTrue($fresh->verificationAudits()->where('action', 'connector_permanently_rejected')->exists());

        $this->actingAs($admin)
            ->post(route('admin.connectors.reject', $connector), [
                'submission_round' => 1,
                'reason_code' => 'ineligible',
                'applicant_reason' => 'Already finalized.',
            ])
            ->assertStatus(409);
    }

    private function completeCurrentEvidence(Connector $connector): void
    {
        foreach ($connector->verificationRequirements as $requirement) {
            if ($requirement->requirement_key === 'activity_specific_authority') {
                $requirement->update(['status' => ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE]);

                continue;
            }

            $requirement->documents()->update([
                'status' => ConnectorVerificationDocument::STATUS_ACCEPTED,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
            $requirement->update(['status' => ConnectorVerificationRequirement::STATUS_SATISFIED]);
        }
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function submitInitialApplication(User $owner, array $overrides = []): Connector
    {
        $this->seedCaviteAddress();

        $this->actingAs($owner)
            ->post(route('connectors.store'), $this->connectorPayload(array_merge($overrides, [
                'logo' => UploadedFile::fake()->create('logo.png', 100, 'image/png'),
            ])))
            ->assertRedirect();

        return Connector::query()->latest('id')->firstOrFail();
    }
}
