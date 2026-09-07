<?php

namespace Tests\Feature;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipEvidenceService;
use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GuardianRelationshipEvidenceSubmissionTest extends TestCase
{
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
                    'file' => UploadedFile::fake()->image('order-front.jpg'),
                ],
                [
                    'document_type' => 'adoption_order',
                    'document_side' => 'back',
                    'pairing_key' => 'f2f07af0-1e32-45fb-9f37-02a6a653a2d9',
                    'file' => UploadedFile::fake()->image('order-back.jpg'),
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
        $second = new UploadedFile(
            $first->getPathname(),
            'renamed.pdf',
            'application/pdf',
            null,
            true,
        );

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
