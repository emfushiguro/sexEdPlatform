<?php

namespace Tests\Feature\Connectors;

use App\Http\Middleware\EnsureLearnerIdentityVerified;
use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationSubmittedNotification;
use App\Services\Connectors\ConnectorVerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConnectorVerificationSubmissionTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_failed_upload_rolls_back_application_and_preserves_existing_files(): void
    {
        $disk = Storage::fake('local');
        Storage::fake('public');
        $disk->put('existing-evidence.pdf', 'Existing evidence must remain.');
        $adapter = \Mockery::mock($disk);
        $writes = 0;
        $adapter->shouldReceive('putFileAs')->andReturnUsing(function (...$arguments) use ($disk, &$writes) {
            if (++$writes === 2) {
                throw new \RuntimeException('Simulated evidence upload failure.');
            }

            return $disk->putFileAs(...$arguments);
        });
        $adapter->shouldReceive('delete')->andReturnUsing(fn ($path) => $disk->delete($path));
        Storage::partialMock()->shouldReceive('disk')->with('local')->andReturn($adapter);
        $user = User::factory()->create();
        $this->seedCaviteAddress();

        try {
            app(ConnectorVerificationService::class)->registerInitialApplication($user, $this->connectorPayload());
            $this->fail('The simulated failed upload must abort registration.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated evidence upload failure.', $exception->getMessage());
        }

        $this->assertSame(['existing-evidence.pdf'], $disk->allFiles());
        $this->assertDatabaseCount('connectors', 0);
        $this->assertDatabaseCount('connector_verification_documents', 0);
        $this->assertDatabaseCount('connector_verification_requirements', 0);
        $this->assertDatabaseCount('connector_memberships', 0);
    }

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

    public function test_broad_organization_types_register_without_a_category_and_ignore_category_tampering(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->seedCaviteAddress();
        $applicant = User::factory()->create(['role' => 'learner']);

        foreach (\App\Support\ConnectorOptions::categoryKeys() as $type) {
            $payload = $this->verificationPayload();
            unset($payload['category']);
            $payload['name'] = 'Single choice '.$type;
            $payload['organization_type'] = $type;
            if ($type === 'other') {
                $payload['organization_type_detail'] = 'Independent youth center';
            }
            $identityKey = $type === 'ngo' ? 'sec_nonstock_registration' : 'other_organization_identity';
            $payload['evidence'][$identityKey] = $payload['evidence']['sec_nonstock_registration'];
            if ($identityKey !== 'sec_nonstock_registration') {
                unset($payload['evidence']['sec_nonstock_registration']);
            }
            if ($type === 'government') {
                $payload['category'] = 'ngo';
            }
            if ($type === 'school_educational_institution') {
                $payload['evidence']['deped_authority'] = [
                    'files' => [UploadedFile::fake()->create('recognition.pdf', 100, 'application/pdf')],
                ];
            }

            $response = $this->actingAs($applicant)->post(route('connectors.store'), $payload);
            $response->assertSessionHasNoErrors();
            $connector = Connector::query()->where('name', $payload['name'])->sole();
            $response->assertRedirect(route('connector.status', $connector));
            $this->assertSame($type, $connector->organization_type);
            $this->assertSame($type, $connector->category);
            $this->assertSame($type === 'other' ? 'Independent youth center' : null, $connector->organization_type_detail);
            $this->assertTrue($connector->verificationRequirements()->where('requirement_key', $identityKey)->exists());
        }
    }

    public function test_other_requires_a_specific_type_and_shows_it_to_applicant_and_reviewer(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->seedCaviteAddress();
        $applicant = User::factory()->create(['role' => 'learner']);
        $payload = $this->verificationPayload([
            'organization_type' => 'other',
            'organization_type_detail' => 'Independent youth center',
        ]);
        unset($payload['category']);
        $payload['evidence']['other_organization_identity'] = $payload['evidence']['sec_nonstock_registration'];
        unset($payload['evidence']['sec_nonstock_registration']);

        $this->actingAs($applicant)
            ->post(route('connectors.store'), [...$payload, 'organization_type_detail' => '   '])
            ->assertSessionHasErrors('organization_type_detail');
        $this->assertDatabaseCount('connectors', 0);

        $this->post(route('connectors.store'), $payload)->assertSessionHasNoErrors();
        $connector = Connector::query()->sole();
        $this->assertSame('Independent youth center', $connector->organization_type_detail);
        $this->get(route('connector.status', $connector))->assertOk()->assertSee('Independent youth center');

        $reviewer = User::factory()->create(['role' => 'admin']);
        $reviewer->assignRole('admin');
        $this->actingAs($reviewer)
            ->get(route('admin.connectors.show', $connector))
            ->assertOk()
            ->assertSee('Independent youth center');
    }

    public function test_non_other_type_rejects_a_supplied_other_detail(): void
    {
        $this->seedCaviteAddress();

        $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->post(route('connectors.store'), $this->verificationPayload([
                'organization_type_detail' => 'Misleading extra type',
            ]))
            ->assertSessionHasErrors('organization_type_detail');

        $this->assertDatabaseCount('connectors', 0);
    }

    public function test_single_organization_type_still_requires_identity_and_authority_evidence(): void
    {
        $this->seedCaviteAddress();
        $payload = $this->verificationPayload(['organization_type' => 'government']);
        unset($payload['category']);
        $payload['evidence'] = [];

        $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->post(route('connectors.store'), $payload)
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('connectors', 0);
    }

    public function test_school_type_requires_at_least_one_education_authority_document(): void
    {
        $this->seedCaviteAddress();
        $payload = $this->verificationPayload(['organization_type' => 'school_educational_institution']);
        unset($payload['category']);
        $payload['evidence']['other_organization_identity'] = $payload['evidence']['sec_nonstock_registration'];
        unset($payload['evidence']['sec_nonstock_registration']);

        $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->post(route('connectors.store'), $payload)
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('connectors', 0);
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
