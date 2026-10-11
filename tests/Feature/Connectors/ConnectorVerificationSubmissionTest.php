<?php

namespace Tests\Feature\Connectors;

use App\Http\Middleware\EnsureLearnerIdentityVerified;
use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConnectorVerificationSubmissionTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_valid_submission_persists_private_evidence_snapshots_and_public_logo(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->seedCaviteAddress();

        $applicant = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($applicant)->post(
            route('connectors.store'),
            $this->verificationPayload()
        );

        $connector = Connector::query()->sole();

        $response->assertRedirect(route('connector.status', $connector));
        $this->assertSame('pending', $connector->status);
        $this->assertSame(Connector::VERIFICATION_UNDER_REVIEW, $connector->verification_status);
        $this->assertSame(1, $connector->current_submission_round);
        $this->assertNotNull($connector->logo_path);

        $this->assertDatabaseCount('connector_verification_requirements', 4);
        $this->assertDatabaseCount('connector_verification_documents', 3);
        $this->assertDatabaseHas('connector_verification_documents', [
            'connector_id' => $connector->id,
            'submission_round' => 1,
            'disk' => 'local',
            'status' => ConnectorVerificationDocument::STATUS_PENDING,
            'mime_type' => 'application/pdf',
        ]);
        $this->assertDatabaseHas('connector_verification_audits', [
            'connector_id' => $connector->id,
            'action' => 'submitted',
            'submission_round' => 1,
        ]);
        $this->assertDatabaseHas('connector_reviews', [
            'connector_id' => $connector->id,
            'to_status' => 'pending',
            'submission_round' => 1,
        ]);

        $this->assertNotEmpty(Storage::disk('local')->allFiles('connector-verifications/'.$connector->id));
        $this->assertNotEmpty(Storage::disk('public')->allFiles('connector-logos/'.$connector->id));
        $this->assertEmpty(Storage::disk('public')->allFiles('connector-verifications'));
        Notification::assertSentTo($applicant, ConnectorApplicationSubmittedNotification::class);
    }

    public function test_submission_rejects_incompatible_type_and_missing_required_evidence(): void
    {
        $this->seedCaviteAddress();
        $applicant = User::factory()->create(['role' => 'learner']);

        $this->actingAs($applicant)
            ->post(route('connectors.store'), $this->verificationPayload([
                'category' => 'ngo',
                'organization_type' => 'barangay_lgu',
                'evidence' => [],
            ]))
            ->assertSessionHasErrors(['organization_type', 'evidence']);
    }

    public function test_submission_rejects_unknown_requirement_and_unconfigured_metadata(): void
    {
        $this->seedCaviteAddress();
        $applicant = User::factory()->create(['role' => 'learner']);

        $this->actingAs($applicant)
            ->post(route('connectors.store'), $this->verificationPayload([
                'evidence' => [
                    ...$this->verificationPayload()['evidence'],
                    'unknown_key' => [
                        'files' => [UploadedFile::fake()->create('unknown.pdf', 10, 'application/pdf')],
                    ],
                    'sec_nonstock_registration' => [
                        'files' => $this->verificationPayload()['evidence']['sec_nonstock_registration']['files'],
                        'document_number' => 'not-configured',
                    ],
                ],
            ]))
            ->assertSessionHasErrors(['evidence']);
    }

    public function test_approved_identity_case_is_referenced_without_copying_its_private_files(): void
    {
        $this->withoutMiddleware(EnsureLearnerIdentityVerified::class);

        Storage::fake('local');
        Storage::fake('public');
        $this->seedCaviteAddress();

        $applicant = User::factory()->create(['role' => 'learner']);
        $identity = LearnerIdentityVerification::create([
            'user_id' => $applicant->id,
            'pathway' => 'adult',
            'status' => 'approved',
            'submission_round' => 1,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($applicant)->post(
            route('connectors.store'),
            $this->verificationPayload([
                'reuse_representative_identity' => '1',
                'evidence' => [
                    'sec_nonstock_registration' => $this->verificationPayload()['evidence']['sec_nonstock_registration'],
                    'representative_identity' => ['files' => null],
                    'representative_authority' => $this->verificationPayload()['evidence']['representative_authority'],
                ],
            ])
        );

        $response->assertRedirect();

        $connector = Connector::query()->sole();
        $requirement = $connector->verificationRequirements()
            ->where('requirement_key', 'representative_identity')
            ->sole();

        $this->assertSame($identity->id, $requirement->learner_identity_verification_id);
        $this->assertDatabaseMissing('connector_verification_documents', [
            'connector_verification_requirement_id' => $requirement->id,
        ]);
        $this->assertEmpty(Storage::disk('local')->allFiles('learner-verifications'));
    }

    private function verificationPayload(array $overrides = []): array
    {
        $evidence = [
            'sec_nonstock_registration' => [
                'files' => [UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf')],
            ],
            'representative_identity' => [
                'files' => [UploadedFile::fake()->create('identity.pdf', 100, 'application/pdf')],
            ],
            'representative_authority' => [
                'files' => [UploadedFile::fake()->create('authority.pdf', 100, 'application/pdf')],
                'issuer' => 'Board of Trustees',
                'issue_date' => '2026-10-07',
            ],
        ];

        return array_replace_recursive([
            ...$this->connectorPayload(),
            'organization_type' => 'ngo_nonprofit_foundation',
            'confirm_accuracy' => '1',
            'reuse_representative_identity' => '0',
            'logo' => UploadedFile::fake()->create('logo.png', 100, 'image/png'),
            'evidence' => $evidence,
        ], $overrides);
    }
}
