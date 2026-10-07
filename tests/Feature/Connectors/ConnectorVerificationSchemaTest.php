<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\ConnectorReview;
use App\Models\ConnectorVerificationAudit;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConnectorVerificationSchemaTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_forward_schema_backfills_only_verified_connectors_and_preserves_operational_status(): void
    {
        $this->seedCaviteAddress();

        $migration = require base_path('database/migrations/2026_10_07_000001_add_connector_verification_workflow.php');
        $user = User::factory()->create();

        $connectors = collect(['verified', 'pending', 'rejected', 'suspended', 'withdrawn'])
            ->mapWithKeys(function (string $status, int $index) use ($user): array {
                $connector = Connector::create([
                    ...$this->connectorPayload(['name' => 'Existing '.$status]),
                    'slug' => 'existing-'.$status.'-'.$index,
                    'status' => $status,
                    'verification_status' => null,
                    'created_by' => $user->id,
                    'primary_representative_user_id' => $user->id,
                ]);

                return [$status => $connector];
            });

        $migration->backfillLegacyVerified();

        $connectors = $connectors->map->fresh();

        $this->assertSame(Connector::VERIFICATION_LEGACY_VERIFIED, $connectors['verified']->verification_status);

        foreach (['pending', 'rejected', 'suspended', 'withdrawn'] as $status) {
            $this->assertNull($connectors[$status]->verification_status);
            $this->assertSame($status, $connectors[$status]->status);
        }

        $this->assertSame('verified', $connectors['verified']->status);
    }

    public function test_verification_tables_expose_expected_columns_relationships_and_casts(): void
    {
        $this->assertTrue(Schema::hasColumns('connectors', [
            'organization_type',
            'logo_path',
            'verification_status',
            'current_submission_round',
            'verification_submitted_at',
        ]));
        $this->assertTrue(Schema::hasColumns('connector_verification_requirements', [
            'connector_id',
            'requirement_key',
            'evidence_category',
            'label',
            'guidance',
            'is_required',
            'minimum_evidence_count',
            'maximum_evidence_count',
            'definition_snapshot',
            'source_snapshot',
            'status',
            'learner_identity_verification_id',
            'requested_by',
            'superseded_at',
        ]));
        $this->assertTrue(Schema::hasColumns('connector_verification_documents', [
            'connector_id',
            'connector_verification_requirement_id',
            'uploaded_by',
            'submission_round',
            'disk',
            'path',
            'original_name',
            'mime_type',
            'size_bytes',
            'content_sha256',
            'issuer',
            'document_number',
            'issue_date',
            'expiry_date',
            'status',
            'reviewed_by',
            'reviewed_at',
            'applicant_rejection_reason',
            'internal_review_note',
            'superseded_at',
        ]));
        $this->assertTrue(Schema::hasColumns('connector_verification_audits', [
            'connector_id',
            'connector_verification_requirement_id',
            'connector_verification_document_id',
            'actor_id',
            'action',
            'previous_state',
            'new_state',
            'submission_round',
            'applicant_reason',
            'internal_notes',
        ]));
        $this->assertTrue(Schema::hasColumns('connector_reviews', [
            'reason_code',
            'internal_notes',
            'submission_round',
        ]));

        $user = User::factory()->create();
        $connector = Connector::create([
            ...$this->connectorPayload(['name' => 'Relationship Connector']),
            'slug' => 'relationship-connector',
            'status' => 'pending',
            'created_by' => $user->id,
            'primary_representative_user_id' => $user->id,
        ]);
        $requirement = $connector->verificationRequirements()->create([
            'requirement_key' => 'representative_authority',
            'evidence_category' => 'representative_authority',
            'label' => 'Representative authority',
            'guidance' => 'Upload authority evidence.',
            'is_required' => true,
            'rule_group' => 'representative_authority',
            'rule_mode' => 'all',
            'minimum_evidence_count' => 1,
            'maximum_evidence_count' => 1,
            'definition_snapshot' => ['key' => 'representative_authority'],
            'source_snapshot' => [],
            'status' => ConnectorVerificationRequirement::STATUS_PENDING,
            'submission_round' => 1,
        ]);
        $document = $connector->verificationDocuments()->create([
            'connector_verification_requirement_id' => $requirement->id,
            'uploaded_by' => $user->id,
            'submission_round' => 1,
            'disk' => 'local',
            'path' => 'connector-verifications/1/round-1/evidence.pdf',
            'original_name' => 'evidence.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 123,
            'content_sha256' => hash('sha256', 'evidence'),
            'status' => ConnectorVerificationDocument::STATUS_PENDING,
        ]);
        $audit = $connector->verificationAudits()->create([
            'connector_verification_requirement_id' => $requirement->id,
            'connector_verification_document_id' => $document->id,
            'actor_id' => $user->id,
            'action' => 'uploaded',
            'previous_state' => [],
            'new_state' => ['status' => 'pending'],
            'submission_round' => 1,
        ]);
        $review = $connector->reviews()->create([
            'to_status' => 'pending',
            'reason_code' => 'submitted',
            'internal_notes' => 'Internal review note',
            'submission_round' => 1,
        ]);

        $this->assertSame('array', $requirement->getCasts()['definition_snapshot']);
        $this->assertSame('array', $audit->getCasts()['previous_state']);
        $this->assertSame('datetime', $document->getCasts()['reviewed_at']);
        $this->assertTrue($connector->verificationRequirements->contains($requirement));
        $this->assertTrue($connector->verificationDocuments->contains($document));
        $this->assertTrue($connector->verificationAudits->contains($audit));
        $this->assertTrue($connector->reviews->contains($review));
    }
}
