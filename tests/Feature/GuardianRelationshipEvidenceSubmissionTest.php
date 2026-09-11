<?php

namespace Tests\Feature;

use App\Models\ParentChildAccount;
use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\User;
use App\Services\GuardianRelationshipEvidenceService;
use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use App\Http\Middleware\EnsureGuardianVerified;
use App\Http\Middleware\EnsureProfileCompleted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GuardianRelationshipEvidenceSubmissionTest extends TestCase
{
    public function test_guardian_submits_multiple_categorized_documents(): void
    {
        $this->withoutMiddleware([EnsureGuardianVerified::class, EnsureProfileCompleted::class]);
        Storage::fake('local');
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');

        $this->actingAs($guardian)
            ->get(route('parent.relationship-verifications.show', $relationship))
            ->assertOk()
            ->assertSee('documents[', false)
            ->assertSee('Add another document', false)
            ->assertSee('Administrative verification', false)
            ->assertSee('Adoptive Parent Evidence Review', false);

        $this->actingAs($guardian)->post(route('parent.relationship-verifications.store', $relationship), [
            'documents' => [
                ['document_type' => 'adoption_order', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => UploadedFile::fake()->createWithContent('front.jpg', 'front-evidence')->mimeType('image/jpeg')],
                ['document_type' => 'other_supporting_document', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => UploadedFile::fake()->create('support.pdf', 100, 'application/pdf')],
            ],
            'confirm_submission' => '1',
        ])->assertRedirect(route('parent.relationship-verifications.show', $relationship));

        $this->assertDatabaseCount('guardian_relationship_verification_documents', 2);
        $this->assertDatabaseHas('parent_child_accounts', [
            'id' => $relationship->id,
            'relationship_status' => 'pending',
            'relationship_verified_status' => 'under_review',
            'current_evidence_round' => 1,
        ]);
    }

    public function test_submitted_image_evidence_renders_an_inline_preview_and_download_link(): void
    {
        $this->withoutMiddleware([EnsureGuardianVerified::class, EnsureProfileCompleted::class]);
        Storage::fake('local');
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
        $storedPaths = [];

        $document = app(GuardianRelationshipEvidenceService::class)->storeUploadedRound(
            $relationship,
            $guardian,
            1,
            [[
                'document_type' => 'adoption_order',
                'document_side' => 'not_applicable',
                'pairing_key' => null,
                'file' => UploadedFile::fake()->createWithContent('adoption-order.jpg', 'image-data')->mimeType('image/jpeg'),
            ]],
            $storedPaths,
        )->sole();

        $documentUrl = route('parent.relationship-verifications.documents.show', [$relationship, $document]);

        $this->actingAs($guardian)
            ->get(route('parent.relationship-verifications.show', $relationship))
            ->assertOk()
            ->assertSee($documentUrl.'?inline=1', false)
            ->assertSee('data-testid="submitted-evidence-preview"', false)
            ->assertSee('alt="Adoption-Related Order or Record Not applicable evidence preview"', false)
            ->assertSee('Download', false);
    }

    public function test_non_owner_cannot_view_submit_or_download_relationship_evidence(): void
    {
        $this->withoutMiddleware([EnsureGuardianVerified::class, EnsureProfileCompleted::class]);
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('parent.relationship-verifications.show', $relationship))->assertForbidden();
        $this->actingAs($other)->post(route('parent.relationship-verifications.store', $relationship), [])->assertForbidden();
    }

    public function test_evidence_download_is_limited_to_submitting_guardian_and_authorized_admin(): void
    {
        $this->withoutMiddleware([EnsureGuardianVerified::class, EnsureProfileCompleted::class]);
        Storage::fake('local');
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
        $document = $this->storeEvidenceFor($relationship, $guardian);
        $otherGuardian = $this->approvedGuardian();
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => User::STATUS_ACTIVE]);
        $instructor->assignRole('instructor');
        $admin = $this->adminWithRelationshipReviewPermission();

        foreach ([$dependent, $otherGuardian, $instructor] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->get(route('parent.relationship-verifications.documents.show', [$relationship, $document]))
                ->assertForbidden();
        }

        $this->actingAs($guardian)
            ->get(route('parent.relationship-verifications.documents.show', [$relationship, $document]))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.parent-verifications.relationships.documents.show', [$relationship, $document]))
            ->assertOk();
    }

    public function test_submission_requires_a_core_pathway_document_and_context_when_configured(): void
    {
        $this->withoutMiddleware([EnsureGuardianVerified::class, EnsureProfileCompleted::class]);
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('aunt');

        $this->actingAs($guardian)->post(route('parent.relationship-verifications.store', $relationship), [
            'documents' => [[
                'document_type' => 'other_supporting_document',
                'document_side' => 'not_applicable',
                'pairing_key' => null,
                'file' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
            ]],
            'relationship_notes' => '',
            'confirm_submission' => '1',
        ])->assertSessionHasErrors(['documents', 'relationship_notes']);
    }

    public function test_multiple_documents_are_stored_privately_in_one_round(): void
    {
        Storage::fake('local');
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
        $storedPaths = [];

        $documents = app(GuardianRelationshipEvidenceService::class)->storeUploadedRound(
            $relationship,
            $guardian,
            1,
            [
                [
                    'document_type' => 'adoption_order',
                    'document_side' => 'front',
                    'pairing_key' => 'f2f07af0-1e32-45fb-9f37-02a6a653a2d9',
                    'file' => UploadedFile::fake()->createWithContent('order-front.jpg', 'front-evidence')->mimeType('image/jpeg'),
                ],
                [
                    'document_type' => 'adoption_order',
                    'document_side' => 'back',
                    'pairing_key' => 'f2f07af0-1e32-45fb-9f37-02a6a653a2d9',
                    'file' => UploadedFile::fake()->createWithContent('order-back.jpg', 'back-evidence')->mimeType('image/jpeg'),
                ],
            ],
            $storedPaths,
        );

        $this->assertCount(2, $documents);
        $this->assertSame([0, 1], $documents->pluck('display_order')->all());
        $this->assertSame(['front', 'back'], $documents->pluck('document_side')->all());
        $this->assertTrue($documents->every(fn ($document) => $document->disk === 'local'));
        $this->assertTrue($documents->every(fn ($document) => strlen($document->content_sha256) === 64));
        foreach ($storedPaths as $path) {
            Storage::disk('local')->assertExists($path);
        }
    }

    public function test_exact_duplicate_content_is_rejected_and_new_files_are_cleaned_up(): void
    {
        Storage::fake('local');
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
        $storedPaths = [];
        $first = UploadedFile::fake()->create('same.pdf', 100, 'application/pdf');
        $second = UploadedFile::fake()->create('renamed.pdf', 100, 'application/pdf');

        try {
            app(GuardianRelationshipEvidenceService::class)->storeUploadedRound(
                $relationship,
                $guardian,
                1,
                [
                    ['document_type' => 'adoption_order', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => $first],
                    ['document_type' => 'other_supporting_document', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => $second],
                ],
                $storedPaths,
            );
            $this->fail('Expected duplicate evidence validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('documents', $exception->errors());
        }

        $this->assertDatabaseCount('guardian_relationship_verification_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_submitted_rounds_are_immutable_but_a_fresh_round_can_be_stored(): void
    {
        Storage::fake('local');
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
        $service = app(GuardianRelationshipEvidenceService::class);
        $firstPaths = [];

        $service->storeUploadedRound($relationship, $guardian, 1, [
            $this->evidenceItem(UploadedFile::fake()->create('first.pdf', 100, 'application/pdf')),
        ], $firstPaths);

        $secondPaths = [];
        try {
            $service->storeUploadedRound($relationship, $guardian, 1, [
                $this->evidenceItem(UploadedFile::fake()->create('second.pdf', 100, 'application/pdf')),
            ], $secondPaths);
            $this->fail('Expected an occupied evidence round to reject new files.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('documents', $exception->errors());
        }

        $freshRoundPaths = [];
        $freshRound = $service->storeUploadedRound($relationship, $guardian, 2, [
            $this->evidenceItem(UploadedFile::fake()->create('fresh-round.pdf', 100, 'application/pdf')),
        ], $freshRoundPaths);

        $this->assertSame([], $secondPaths);
        $this->assertCount(1, $freshRound);
        $this->assertDatabaseCount('guardian_relationship_verification_documents', 2);
    }

    public function test_duplicate_content_constraint_race_is_rejected_as_validation(): void
    {
        Storage::fake('local');
        [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
        $insertedCompetingDocument = false;
        GuardianRelationshipVerificationDocument::creating(function (GuardianRelationshipVerificationDocument $document) use (&$insertedCompetingDocument): void {
            if ($insertedCompetingDocument) {
                return;
            }

            $insertedCompetingDocument = true;
            GuardianRelationshipVerificationDocument::withoutEvents(function () use ($document): void {
                GuardianRelationshipVerificationDocument::query()->create([
                    ...$document->getAttributes(),
                    'path' => 'guardian-relationship-verifications/competing-file.pdf',
                ]);
            });
        });

        $storedPaths = [];
        try {
            app(GuardianRelationshipEvidenceService::class)->storeUploadedRound(
                $relationship,
                $guardian,
                1,
                [$this->evidenceItem(UploadedFile::fake()->create('same.pdf', 100, 'application/pdf'))],
                $storedPaths,
            );
            $this->fail('Expected a duplicate database constraint to be translated to validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('documents', $exception->errors());
        } finally {
            GuardianRelationshipVerificationDocument::flushEventListeners();
        }

        $this->assertSame([], $storedPaths);
        $this->assertDatabaseCount('guardian_relationship_verification_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_evidence_validation_rejects_more_than_ten_files(): void
    {
        $validator = Validator::make(['documents' => array_map(
            fn (int $index): array => $this->evidenceItem(UploadedFile::fake()->create("evidence-{$index}.pdf", 100, 'application/pdf')),
            range(1, 11),
        )], GuardianRelationshipEvidenceRules::for(['adoption_order']));

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('documents'));
    }

    public function test_evidence_validation_rejects_files_larger_than_five_megabytes(): void
    {
        $validator = Validator::make(['documents' => [
            $this->evidenceItem(UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')),
        ]], GuardianRelationshipEvidenceRules::for(['adoption_order']));

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('documents.0.file'));
    }

    public function test_evidence_validation_accepts_supported_file_types(): void
    {
        foreach (['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'] as $extension => $mimeType) {
            $validator = Validator::make(['documents' => [
                $this->evidenceItem(UploadedFile::fake()->create("evidence.{$extension}", 100, $mimeType)),
            ]], GuardianRelationshipEvidenceRules::for(['adoption_order']));

            $this->assertFalse($validator->fails(), "Expected .{$extension} evidence to be accepted.");
        }
    }

    public function test_evidence_validation_rejects_unsupported_file_types(): void
    {
        $validator = Validator::make(['documents' => [
            $this->evidenceItem(UploadedFile::fake()->create('evidence.txt', 100, 'text/plain')),
        ]], GuardianRelationshipEvidenceRules::for(['adoption_order']));

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('documents.0.file'));
    }

    public function test_front_and_back_metadata_requires_one_uuid_pair_with_unique_sides(): void
    {
        $pairingKey = 'f2f07af0-1e32-45fb-9f37-02a6a653a2d9';

        $this->assertSame([], GuardianRelationshipEvidenceRules::metadataErrors([
            ['document_type' => 'adoption_order', 'document_side' => 'front', 'pairing_key' => $pairingKey],
            ['document_type' => 'adoption_order', 'document_side' => 'back', 'pairing_key' => $pairingKey],
        ]));
        $this->assertArrayHasKey('documents.0.pairing_key', GuardianRelationshipEvidenceRules::metadataErrors([
            ['document_type' => 'adoption_order', 'document_side' => 'front', 'pairing_key' => null],
        ]));
        $this->assertArrayHasKey('documents.0.pairing_key', GuardianRelationshipEvidenceRules::metadataErrors([
            ['document_type' => 'adoption_order', 'document_side' => 'not_applicable', 'pairing_key' => $pairingKey],
        ]));
        $this->assertArrayHasKey('documents.1.document_side', GuardianRelationshipEvidenceRules::metadataErrors([
            ['document_type' => 'adoption_order', 'document_side' => 'front', 'pairing_key' => $pairingKey],
            ['document_type' => 'adoption_order', 'document_side' => 'front', 'pairing_key' => $pairingKey],
        ]));
        $this->assertArrayHasKey('documents.1.document_type', GuardianRelationshipEvidenceRules::metadataErrors([
            ['document_type' => 'adoption_order', 'document_side' => 'front', 'pairing_key' => $pairingKey],
            ['document_type' => 'other_supporting_document', 'document_side' => 'back', 'pairing_key' => $pairingKey],
        ]));
        $this->assertArrayHasKey('documents', GuardianRelationshipEvidenceRules::metadataErrors([
            ['document_type' => 'adoption_order', 'document_side' => 'front', 'pairing_key' => $pairingKey],
        ]));
        $this->assertArrayHasKey('documents.4.pairing_key', GuardianRelationshipEvidenceRules::metadataErrors([
            4 => ['document_type' => 'adoption_order', 'document_side' => 'front', 'pairing_key' => null],
        ]));
    }

    private function evidenceItem(UploadedFile $file): array
    {
        return [
            'document_type' => 'adoption_order',
            'document_side' => 'not_applicable',
            'pairing_key' => null,
            'file' => $file,
        ];
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

    private function adminWithRelationshipReviewPermission(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function storeEvidenceFor(
        ParentChildAccount $relationship,
        User $guardian,
    ): GuardianRelationshipVerificationDocument {
        $storedPaths = [];

        return app(GuardianRelationshipEvidenceService::class)->storeUploadedRound(
            $relationship,
            $guardian,
            1,
            [[
                'document_type' => 'adoption_order',
                'document_side' => 'not_applicable',
                'pairing_key' => null,
                'file' => UploadedFile::fake()->create('order.pdf', 100, 'application/pdf'),
            ]],
            $storedPaths,
        )->sole();
    }

    private function pendingRelationship(string $type): array
    {
        $guardian = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $guardian->assignRole('learner');

        $dependent = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $dependent->assignRole('learner');

        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => $type,
            'verification_pathway' => GuardianRelationshipTypes::pathway($type),
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'current_evidence_round' => 0,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'verification_status' => 'approved',
        ]);

        return [$guardian, $dependent, $relationship];
    }
}
