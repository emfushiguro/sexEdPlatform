<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\User;
use App\Support\ConnectorVerificationRequirements;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConnectorVerificationResubmissionTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_applicant_can_replace_rejected_document_without_replacing_accepted_document(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);

        $accepted = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'sec_nonstock_registration'))
            ->firstOrFail();
        $rejected = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'representative_authority'))
            ->firstOrFail();

        $accepted->update(['status' => ConnectorVerificationDocument::STATUS_ACCEPTED]);
        $accepted->requirement()->update(['status' => ConnectorVerificationRequirement::STATUS_SATISFIED]);
        $rejected->update([
            'status' => ConnectorVerificationDocument::STATUS_REJECTED,
            'applicant_rejection_reason' => 'Upload a clearer authorization.',
        ]);
        $rejected->requirement()->update(['status' => ConnectorVerificationRequirement::STATUS_REJECTED]);
        $connector->update([
            'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            'current_submission_round' => 1,
        ]);

        $this->actingAs($owner)
            ->post(route('connector.verification.resubmit', $connector), [
                'submission_round' => 1,
                'evidence' => [
                    'representative_authority' => [
                        'files' => [UploadedFile::fake()->createWithContent('replacement.pdf', 'replacement content')],
                        'issuer' => 'Board of Trustees',
                        'issue_date' => '2026-10-07',
                    ],
                ],
            ])
            ->assertRedirect(route('connector.status', $connector));

        $this->assertSame(
            ConnectorVerificationDocument::STATUS_ACCEPTED,
            $accepted->fresh()->status,
        );
        $this->assertSame(
            ConnectorVerificationDocument::STATUS_SUPERSEDED,
            $rejected->fresh()->status,
        );
        $this->assertSame(2, $connector->fresh()->current_submission_round);
        $this->assertSame(
            Connector::VERIFICATION_UNDER_REVIEW,
            $connector->fresh()->verification_status,
        );
        $this->assertSame(
            ConnectorVerificationDocument::STATUS_PENDING,
            $connector->verificationDocuments()
                ->where('submission_round', 2)
                ->firstOrFail()
                ->status,
        );
    }

    public function test_legacy_pending_application_can_complete_current_requirements(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = Connector::create([
            ...$this->connectorPayload(['organization_type' => null]),
            'slug' => 'legacy-pending-'.str()->random(6),
            'organization_type' => null,
            'verification_status' => null,
            'current_submission_round' => 0,
            'status' => 'pending',
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->post(route('connector.verification.resubmit', $connector), [
                'submission_round' => 0,
                'organization_type' => 'ngo_nonprofit_foundation',
                'evidence' => [
                    'sec_nonstock_registration' => [
                        'files' => [UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf')],
                    ],
                    'representative_identity' => [
                        'files' => [UploadedFile::fake()->create('identity.pdf', 100, 'application/pdf')],
                    ],
                    'representative_authority' => [
                        'files' => [UploadedFile::fake()->createWithContent('authority.pdf', 'supplemental content')],
                    ],
                ],
            ])
            ->assertRedirect(route('connector.status', $connector));

        $fresh = $connector->fresh();
        $this->assertSame('ngo_nonprofit_foundation', $fresh->organization_type);
        $this->assertSame(1, $fresh->current_submission_round);
        $this->assertSame(Connector::VERIFICATION_UNDER_REVIEW, $fresh->verification_status);
        $this->assertCount(4, $fresh->verificationRequirements);
        $this->assertCount(3, $fresh->verificationDocuments);
    }

    public function test_newly_requested_supplemental_requirement_can_be_submitted(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $admin = User::factory()->create(['role' => 'admin']);
        $registry = app(ConnectorVerificationRequirements::class);
        $definition = collect($registry->requirementsFor('ngo', 'ngo_nonprofit_foundation'))
            ->firstWhere('key', 'activity_specific_authority');

        $requirement = $connector->verificationRequirements()->create([
            'requirement_key' => $definition['key'],
            'evidence_category' => $definition['evidence_category'],
            'label' => $definition['label'],
            'guidance' => $definition['guidance'],
            'is_required' => false,
            'rule_group' => $definition['rule_group'],
            'rule_mode' => $definition['rule_mode'],
            'minimum_evidence_count' => $definition['minimum_evidence_count'],
            'maximum_evidence_count' => $definition['maximum_evidence_count'],
            'definition_snapshot' => $definition,
            'source_snapshot' => [
                'issuing_authority' => $definition['issuing_authority'],
                'source_url' => $definition['source_url'],
            ],
            'status' => ConnectorVerificationRequirement::STATUS_PENDING,
            'requested_by' => $admin->id,
        ]);
        $connector->update([
            'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            'current_submission_round' => 1,
        ]);
        $accepted = $connector->verificationRequirements()
            ->where('requirement_key', 'sec_nonstock_registration')
            ->firstOrFail();
        $accepted->update(['status' => ConnectorVerificationRequirement::STATUS_SATISFIED]);
        $accepted->documents()->update(['status' => ConnectorVerificationDocument::STATUS_ACCEPTED]);

        $this->actingAs($owner)
            ->post(route('connector.verification.resubmit', $connector), [
                'submission_round' => 1,
                'evidence' => [
                    $requirement->requirement_key => [
                        'files' => [UploadedFile::fake()->create('authority.pdf', 100, 'application/pdf')],
                    ],
                ],
            ])
            ->assertRedirect(route('connector.status', $connector));

        $this->assertSame(
            ConnectorVerificationRequirement::STATUS_PENDING,
            $requirement->fresh()->status,
        );
        $this->assertSame(2, $connector->fresh()->current_submission_round);
        $this->assertSame(
            ConnectorVerificationRequirement::STATUS_SATISFIED,
            $connector->verificationRequirements()
                ->where('requirement_key', 'sec_nonstock_registration')
                ->firstOrFail()
                ->fresh()
                ->status,
        );
    }

    public function test_expired_evidence_can_be_replaced_without_changing_other_documents(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $expired = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'representative_authority'))
            ->firstOrFail();
        $expired->update(['status' => ConnectorVerificationDocument::STATUS_EXPIRED]);
        $expired->requirement()->update(['status' => ConnectorVerificationRequirement::STATUS_EXPIRED]);
        $connector->update([
            'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            'current_submission_round' => 1,
        ]);

        $this->actingAs($owner)
            ->post(route('connector.verification.resubmit', $connector), [
                'submission_round' => 1,
                'evidence' => [
                    'representative_authority' => [
                        'files' => [
                            UploadedFile::fake()->createWithContent('renewed-authority.pdf', 'renewed authority'),
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('connector.status', $connector));

        $this->assertSame(
            ConnectorVerificationDocument::STATUS_SUPERSEDED,
            $expired->fresh()->status,
        );
        $this->assertSame(2, $connector->fresh()->current_submission_round);
        $this->assertSame(
            ConnectorVerificationDocument::STATUS_PENDING,
            $connector->verificationDocuments()
                ->where('submission_round', 2)
                ->firstOrFail()
                ->status,
        );
    }

    public function test_duplicate_hash_for_active_requirement_is_rejected(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $document = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'representative_authority'))
            ->firstOrFail();
        $document->update([
            'content_sha256' => hash('sha256', 'already-submitted'),
            'status' => ConnectorVerificationDocument::STATUS_REJECTED,
        ]);
        $document->requirement()->update(['status' => ConnectorVerificationRequirement::STATUS_REJECTED]);
        $connector->update([
            'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            'current_submission_round' => 1,
        ]);

        $this->actingAs($owner)
            ->from(route('connector.status', $connector))
            ->post(route('connector.verification.resubmit', $connector), [
                'submission_round' => 1,
                'evidence' => [
                    'representative_authority' => [
                        'files' => [
                            UploadedFile::fake()->createWithContent('same.pdf', 'already-submitted'),
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('connector.status', $connector))
            ->assertSessionHasErrors('representative_authority.files');

        $this->assertSame(1, $connector->fresh()->current_submission_round);
    }

    public function test_stale_round_conflict_and_ordinary_member_denial_are_enforced(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $member = $this->createAdultConnectorMember($connector);
        $rejected = $connector->verificationDocuments()
            ->whereHas('requirement', fn ($query) => $query->where('requirement_key', 'representative_authority'))
            ->firstOrFail();
        $rejected->update(['status' => ConnectorVerificationDocument::STATUS_REJECTED]);
        $rejected->requirement()->update(['status' => ConnectorVerificationRequirement::STATUS_REJECTED]);
        $connector->update([
            'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            'current_submission_round' => 1,
        ]);

        $payload = [
            'submission_round' => 1,
            'evidence' => [
                'representative_authority' => [
                    'files' => [UploadedFile::fake()->createWithContent('replacement.pdf', 'stale replacement content')],
                ],
            ],
        ];

        $this->actingAs($member)
            ->post(route('connector.verification.resubmit', $connector), $payload)
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('connector.verification.resubmit', $connector), [
                ...$payload,
                'submission_round' => 0,
            ])
            ->assertStatus(409);
    }

    public function test_status_hides_internal_notes_from_regular_members(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $connector->reviews()->create([
            'from_status' => 'pending',
            'to_status' => 'verified',
            'reason' => 'Application approved.',
            'internal_notes' => 'Do not disclose this internal review note.',
            'reviewed_at' => now(),
        ]);
        $member = $this->createAdultConnectorMember($connector);

        $this->actingAs($member)
            ->get(route('connector.status', $connector))
            ->assertOk()
            ->assertSee('Verified')
            ->assertDontSee('Do not disclose this internal review note.');
    }

    public function test_under_review_applications_cannot_be_resubmitted(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $owner = User::factory()->create();
        $connector = $this->submitInitialApplication($owner);
        $connector->update(['verification_status' => Connector::VERIFICATION_UNDER_REVIEW]);

        $this->actingAs($owner)
            ->post(route('connector.verification.resubmit', $connector), [
                'submission_round' => 1,
                'evidence' => ['representative_authority' => ['files' => []]],
            ])
            ->assertForbidden();
    }

    private function submitInitialApplication(User $owner): Connector
    {
        $this->seedCaviteAddress();

        $this->actingAs($owner)
            ->post(route('connectors.store'), $this->connectorPayload())
            ->assertRedirect();

        return Connector::query()->latest('id')->firstOrFail();
    }
}
