<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminConnectorDocumentReviewTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_admin_can_accept_and_reject_documents_with_separate_reasons(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);
        $accepted = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'sec_nonstock_registration'))
            ->firstOrFail();
        $rejected = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'representative_authority'))
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.documents.review', [$connector, $accepted]), [
                'submission_round' => 1,
                'decision' => 'accept',
                'internal_note' => 'Registry matched the submitted certificate.',
            ])
            ->assertRedirect(route('admin.connectors.show', $connector));

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.documents.review', [$connector, $rejected]), [
                'submission_round' => 1,
                'decision' => 'reject',
                'applicant_reason' => 'Upload a clearer authorization.',
                'internal_note' => 'Signature block is not legible.',
            ])
            ->assertRedirect(route('admin.connectors.show', $connector));

        $this->assertSame(ConnectorVerificationDocument::STATUS_ACCEPTED, $accepted->fresh()->status);
        $this->assertSame(ConnectorVerificationDocument::STATUS_REJECTED, $rejected->fresh()->status);
        $this->assertSame('Upload a clearer authorization.', $rejected->fresh()->applicant_rejection_reason);
        $this->assertSame('Signature block is not legible.', $rejected->fresh()->internal_review_note);
        $this->assertSame(
            ConnectorVerificationRequirement::STATUS_SATISFIED,
            $accepted->requirement()->firstOrFail()->fresh()->status,
        );
        $this->assertSame(
            ConnectorVerificationRequirement::STATUS_REJECTED,
            $rejected->requirement()->firstOrFail()->fresh()->status,
        );
        $this->assertSame(Connector::VERIFICATION_UNDER_REVIEW, $connector->fresh()->verification_status);
        $this->assertTrue($connector->verificationAudits()->where('action', 'document_accepted')->exists());
        $this->assertTrue($connector->verificationAudits()->where('action', 'document_rejected')->exists());
    }

    public function test_rejection_requires_applicant_reason_and_cross_connector_documents_are_not_found(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);
        $otherConnector = $this->submitInitialApplication($otherOwner, ['name' => 'Other Review Connector']);
        $document = $connector->verificationDocuments()->firstOrFail();
        $otherDocument = $otherConnector->verificationDocuments()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.documents.review', [$connector, $otherDocument]), [
                'submission_round' => 1,
                'decision' => 'accept',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->from(route('admin.connectors.show', $connector))
            ->post(route('admin.connectors.verification.documents.review', [$connector, $document]), [
                'submission_round' => 1,
                'decision' => 'reject',
            ])
            ->assertRedirect(route('admin.connectors.show', $connector))
            ->assertSessionHasErrors('applicant_reason');
    }

    public function test_stale_and_finalized_documents_cannot_be_reviewed_again(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);
        $document = $connector->verificationDocuments()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.documents.review', [$connector, $document]), [
                'submission_round' => 1,
                'decision' => 'accept',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.documents.review', [$connector, $document]), [
                'submission_round' => 1,
                'decision' => 'reject',
                'applicant_reason' => 'Already finalized.',
            ])
            ->assertStatus(409);

        $otherDocument = $connector->verificationDocuments()
            ->where('status', ConnectorVerificationDocument::STATUS_PENDING)
            ->firstOrFail();
        $connector->update(['current_submission_round' => 2]);

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.documents.review', [$connector, $otherDocument]), [
                'submission_round' => 1,
                'decision' => 'accept',
            ])
            ->assertStatus(409);
    }

    public function test_only_exempt_requirements_can_be_marked_not_applicable(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $admin = $this->admin();
        $connector = $this->submitInitialApplication($owner);
        $optional = $connector->verificationRequirements()
            ->where('requirement_key', 'activity_specific_authority')
            ->firstOrFail();
        $optional->update(['status' => ConnectorVerificationRequirement::STATUS_PENDING]);

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.requirements.resolve', [$connector, $optional]), [
                'submission_round' => 1,
                'reason' => 'The declared activity does not require sector-specific authority.',
                'internal_note' => 'Reviewed the activity statement.',
            ])
            ->assertRedirect(route('admin.connectors.show', $connector));

        $this->assertSame(
            ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE,
            $optional->fresh()->status,
        );
        $this->assertTrue($connector->verificationAudits()->where('action', 'requirement_not_applicable')->exists());

        $mandatory = $connector->verificationRequirements()
            ->where('requirement_key', 'representative_authority')
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.requirements.resolve', [$connector, $mandatory]), [
                'submission_round' => 1,
                'reason' => 'This should not be allowed.',
            ])
            ->assertForbidden();
    }

    public function test_custom_supplemental_evidence_is_scoped_to_other_organizations(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = $this->admin();
        $owner = User::factory()->create();
        $connector = Connector::create([
            ...$this->connectorPayload([
                'name' => 'Other Organization Review',
                'category' => 'other',
                'organization_type' => 'other',
            ]),
            'slug' => 'other-review-'.str()->random(6),
            'status' => 'pending',
            'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
            'current_submission_round' => 1,
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ]);
        $before = array_keys(config('connector_verification.requirements'));

        $payload = [
            'submission_round' => 1,
            'label' => 'Current activity certification',
            'guidance' => 'Provide current certification for the declared activity.',
            'evidence_category' => 'supporting_evidence',
            'minimum_evidence_count' => 1,
            'maximum_evidence_count' => 2,
            'accepted_mime_types' => ['application/pdf', 'image/png'],
            'metadata_fields' => ['issuer', 'issue_date'],
            'expires' => true,
        ];

        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.request-evidence', $connector), $payload)
            ->assertRedirect(route('admin.connectors.show', $connector));

        $requirement = $connector->verificationRequirements()->latest('id')->firstOrFail();
        $this->assertStringStartsWith('custom_', $requirement->requirement_key);
        $this->assertSame($admin->id, $requirement->requested_by);
        $this->assertSame(ConnectorVerificationRequirement::STATUS_PENDING, $requirement->status);
        $this->assertSame(Connector::VERIFICATION_RESUBMISSION_REQUIRED, $connector->fresh()->verification_status);
        $this->assertSame($before, array_keys(config('connector_verification.requirements')));

        $ngo = $this->submitInitialApplication(User::factory()->create(), ['name' => 'NGO Review']);
        $this->actingAs($admin)
            ->post(route('admin.connectors.verification.request-evidence', $ngo), $payload)
            ->assertForbidden();
    }

    public function test_non_admin_cannot_review_documents(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $document = $connector->verificationDocuments()->firstOrFail();

        $this->actingAs($owner)
            ->post(route('admin.connectors.verification.documents.review', [$connector, $document]), [
                'submission_round' => 1,
                'decision' => 'accept',
            ])
            ->assertForbidden();
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
