<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuardianVerified;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipVerificationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class GuardianRelationshipCorrectionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    public function test_stale_relationship_approval_cannot_approve_newer_unseen_evidence(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $old = $relationship->fresh();
        $support = $relationship->verificationDocuments()->where('document_type', 'other_supporting_document')->firstOrFail();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace support.', true, [$support->id], [], 1);
        $current = $service->submit($relationship->fresh(), $guardian, [$this->document('new-support.pdf', 'other_supporting_document', $support->id)], null, currentEvidenceRound: 1);
        $audits = $current->verificationAudits()->count();
        $this->actingAs($admin)->postJson(route('admin.parent-verifications.relationships.approve', $current), [])->assertUnprocessable();
        $this->postJson(route('admin.parent-verifications.relationships.approve', $current), ['current_evidence_round' => 1])->assertConflict();
        $this->assertSame('under_review', $current->fresh()->relationship_verified_status);
        $this->assertSame($audits, $current->verificationAudits()->count());
        try {
            $service->approve($old, $admin);
            $this->fail('The service must reject a stale evidence snapshot.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->postJson(route('admin.parent-verifications.relationships.approve', $current), ['current_evidence_round' => 2])->assertOk();
        $this->assertSame('verified', $current->fresh()->relationship_verified_status);
    }

    public function test_only_requested_evidence_changes_and_history_and_storage_are_preserved(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        [$core, $support] = $relationship->verificationDocuments()->orderBy('id')->get()->all();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace the supporting evidence.', true, [$support->id], [], 1);
        $this->assertSame('accepted', $core->fresh()->review_status);
        $this->assertSame('changes_requested', $support->fresh()->review_status);

        $result = $service->submit($relationship->fresh(), $guardian, [
            $this->document('replacement.pdf', 'other_supporting_document', $support->id),
        ], null, currentEvidenceRound: 1);

        $this->assertSame(2, $result->current_evidence_round);
        $this->assertSame('under_review', $result->relationship_verified_status);
        $this->assertNull($result->relationship_correction_request);
        $retained = $result->verificationDocuments->where('submission_round', 2)->firstWhere('document_type', 'civil_registry_record');
        $this->assertNotSame($core->id, $retained->id);
        $this->assertSame($core->path, $retained->path);
        $this->assertSame('accepted', $retained->review_status);
        $this->assertSame($core->submitted_at->toDateTimeString(), $retained->submitted_at->toDateTimeString());
        $this->assertSame('accepted', $core->fresh()->review_status);
        $this->assertSame('changes_requested', $support->fresh()->review_status);
        $this->assertCount(3, Storage::disk('local')->allFiles());
    }

    public function test_missing_requested_type_and_unrequested_replacement_are_rejected_atomically(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        [$core, $support] = $relationship->verificationDocuments()->orderBy('id')->get()->all();
        $service->reject($relationship, $admin, 'insufficient_support', 'Replace support and add an agency record.', true, [$support->id], ['agency_document'], 1);

        foreach ([
            [$this->document('support.pdf', 'other_supporting_document', $support->id)],
            [$this->document('core.pdf', 'civil_registry_record', $core->id), $this->document('agency.pdf', 'agency_document')],
            [$this->document('wrong-type.pdf', 'court_order', $support->id), $this->document('agency.pdf', 'agency_document')],
        ] as $documents) {
            try {
                $service->submit($relationship->fresh(), $guardian, $documents, null, currentEvidenceRound: 1);
                $this->fail('An incomplete or unrelated correction must fail.');
            } catch (ValidationException) {
                $this->assertSame(1, $relationship->fresh()->current_evidence_round);
                $this->assertCount(2, Storage::disk('local')->allFiles());
            }
        }

        $result = $service->submit($relationship->fresh(), $guardian, [
            $this->document('support.pdf', 'other_supporting_document', $support->id),
            $this->document('agency.pdf', 'agency_document'),
        ], null, currentEvidenceRound: 1);
        $this->assertCount(3, $result->verificationDocuments->where('submission_round', 2));
    }

    public function test_stale_or_missing_round_cannot_resubmit_corrections(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $document = $relationship->verificationDocuments()->firstOrFail();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace this record.', true, [$document->id], [], 1);

        foreach ([null, 0, 2] as $round) {
            try {
                $service->submit($relationship->fresh(), $guardian, [$this->document('replacement.pdf', 'civil_registry_record', $document->id)], null, currentEvidenceRound: $round);
                $this->fail('A correction needs the matching review round.');
            } catch (ValidationException) {
                $this->assertSame(1, $relationship->fresh()->current_evidence_round);
            }
        }
    }

    public function test_review_rejects_foreign_ids_present_types_and_stale_rounds(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        [, , , $other] = $this->submittedRelationship();
        foreach ([
            [[$other->verificationDocuments()->firstOrFail()->id], [], 1],
            [[], ['civil_registry_record'], 1],
            [[], ['adoption_order'], 1],
            [[], [], 1],
            [[$relationship->verificationDocuments()->firstOrFail()->id], [], 0],
        ] as [$ids, $types, $round]) {
            try {
                $service->reject($relationship, $admin, 'unclear_document', 'Please correct.', true, $ids, $types, $round);
                $this->fail('Invalid correction requests must fail.');
            } catch (ValidationException) {
                $this->assertSame('under_review', $relationship->fresh()->relationship_verified_status);
            }
        }
    }

    public function test_requesting_one_side_requires_both_sides_of_the_pair(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $pair = (string) Str::uuid();
        $documents = $relationship->verificationDocuments()->orderBy('id')->get();
        foreach ($documents as $index => $document) {
            $document->update(['document_type' => 'civil_registry_record', 'document_side' => $index === 0 ? 'front' : 'back', 'pairing_key' => $pair]);
        }
        $service->reject($relationship, $admin, 'unclear_document', 'Replace the pair.', true, [$documents[0]->id], [], 1);
        $this->assertEqualsCanonicalizing($documents->modelKeys(), $relationship->fresh()->relationship_correction_request['document_ids']);

        $this->expectException(ValidationException::class);
        $service->submit($relationship->fresh(), $guardian, [array_merge(
            $this->document('front.pdf', 'civil_registry_record', $documents[0]->id),
            ['document_side' => 'front', 'pairing_key' => $pair],
        )], null, currentEvidenceRound: 1);
    }

    public function test_duplicate_of_retained_content_rolls_back_without_deleting_shared_file(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        [$core, $support] = $relationship->verificationDocuments()->orderBy('id')->get()->all();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace support.', true, [$support->id], [], 1);
        $replacement = $this->document('duplicate.pdf', 'other_supporting_document', $support->id);
        file_put_contents($replacement['file']->getRealPath(), Storage::disk('local')->get($core->path));
        try {
            $service->submit($relationship->fresh(), $guardian, [$replacement], null, currentEvidenceRound: 1);
            $this->fail('Duplicate effective-round content must fail.');
        } catch (ValidationException) {
            Storage::disk('local')->assertExists($core->path);
            $this->assertCount(2, Storage::disk('local')->allFiles());
            $this->assertSame(2, $relationship->verificationDocuments()->count());
        }
    }

    public function test_final_rejection_is_closed_and_legacy_rejection_calls_still_work(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $result = $service->reject($relationship, $admin, 'cannot_verify', 'Final decision.', false);
        $this->assertSame('rejected', $result->relationship_verified_status);
        $this->assertNull($result->relationship_correction_request);
        $this->assertSame(['rejected'], $result->verificationDocuments->pluck('review_status')->unique()->values()->all());
        $this->expectException(InvalidArgumentException::class);
        $service->submit($result, $guardian, [$this->document('record.pdf', 'civil_registry_record')], null, currentEvidenceRound: 1);
    }

    public function test_legacy_resubmission_call_can_still_replace_the_entire_round(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $service->reject($relationship, $admin, 'unclear_document', 'Upload fresh evidence.');
        $result = $service->submit($relationship->fresh(), $guardian, [$this->document('fresh.pdf', 'civil_registry_record')], null);
        $this->assertSame(2, $result->current_evidence_round);
        $this->assertCount(1, $result->verificationDocuments->where('submission_round', 2));
    }

    public function test_a_closed_correction_claim_cannot_be_submitted(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $document = $relationship->verificationDocuments()->firstOrFail();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace record.', true, [$document->id], [], 1);
        $service->closePendingClaim($relationship->fresh(), $admin, 'Claim withdrawn.');
        $this->expectException(InvalidArgumentException::class);
        $service->submit($relationship->fresh(), $guardian, [$this->document('record.pdf', 'civil_registry_record', $document->id)], null, currentEvidenceRound: 1);
    }

    public function test_owner_http_submission_requires_a_round_and_accepts_only_the_requested_corrections(): void
    {
        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $document = $relationship->verificationDocuments()->where('document_type', 'other_supporting_document')->firstOrFail();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace support.', true, [$document->id], [], 1);
        $this->withoutMiddleware([EnsureGuardianVerified::class, EnsureProfileCompleted::class]);
        $payload = ['confirm_submission' => 1];
        $this->actingAs($guardian)->post(route('parent.relationship-verifications.store', $relationship), $payload)
            ->assertSessionHasErrors('current_evidence_round');
        $this->postJson(route('parent.relationship-verifications.draft.store', $relationship), [
            'current_evidence_round' => 1, 'slot' => 'document:'.$document->id,
            'file' => $this->document('replacement.pdf', 'other_supporting_document', $document->id)['file'],
        ])->assertOk();
        $this->post(route('parent.relationship-verifications.store', $relationship), $payload + ['current_evidence_round' => 1])
            ->assertRedirect(route('parent.relationship-verifications.show', $relationship))->assertSessionHasNoErrors();
        $this->assertSame(2, $relationship->fresh()->current_evidence_round);
    }

    public function test_reviewer_http_form_requires_a_round_and_can_finally_reject_without_correction_targets(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $payload = ['reason_code' => 'cannot_verify', 'note' => 'Final decision.', 'allow_resubmission' => 0];
        $this->actingAs($admin)->post(route('admin.parent-verifications.relationships.reject', $relationship), $payload)
            ->assertSessionHasErrors('current_evidence_round');
        $this->post(route('admin.parent-verifications.relationships.reject', $relationship), $payload + ['current_evidence_round' => 1])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('rejected', $relationship->fresh()->relationship_verified_status);
    }

    public function test_reviewer_http_request_persists_targeted_documents_and_missing_types(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $document = $relationship->verificationDocuments()->where('document_type', 'other_supporting_document')->firstOrFail();
        $payload = [
            'reason_code' => 'unclear_document', 'note' => 'Replace support and add the agency record.',
            'allow_resubmission' => 1, 'current_evidence_round' => 1,
            'requested_document_ids' => [$document->id], 'requested_document_types' => ['agency_document'],
        ];
        $this->actingAs($admin)->post(route('admin.parent-verifications.relationships.reject', $relationship), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([
            'round' => 1, 'document_ids' => [$document->id], 'document_types' => ['agency_document'],
        ], $relationship->fresh()->relationship_correction_request);
    }

    public function test_service_rejects_a_different_guardian(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        [, , $otherGuardian] = $this->submittedRelationship();
        $document = $relationship->verificationDocuments()->firstOrFail();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace record.', true, [$document->id], [], 1);
        $this->expectException(AuthorizationException::class);
        $service->submit($relationship->fresh(), $otherGuardian, [$this->document('record.pdf', 'civil_registry_record', $document->id)], null, currentEvidenceRound: 1);
    }

    public function test_owner_form_keeps_acceptable_evidence_and_has_only_targeted_inputs(): void
    {
        [$service, $admin, $guardian, $relationship] = $this->submittedRelationship();
        $document = $relationship->verificationDocuments()->where('document_type', 'other_supporting_document')->firstOrFail();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace support.', true, [$document->id], ['agency_document'], 1);
        $this->withoutMiddleware([EnsureGuardianVerified::class, EnsureProfileCompleted::class]);
        $this->actingAs($guardian)->get(route('parent.relationship-verifications.show', $relationship))
            ->assertOk()->assertSee('No changes needed')->assertSee('Submit all corrections')
            ->assertSee('Save replacement')->assertSee('current_evidence_round')->assertDontSee('Sandbox');
    }

    private function submittedRelationship(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');
        $guardian = User::factory()->create([
            'role' => 'learner', 'status' => User::STATUS_ACTIVE, 'email_verified_at' => now(),
            'is_parent_registration' => true, 'parent_verification_status' => 'approved',
        ]);
        $guardian->assignRole('learner');
        $dependent = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id, 'child_user_id' => $dependent->id,
            'relationship_type' => 'biological_mother', 'relationship_status' => 'pending',
            'relationship_verified_status' => 'pending', 'verification_status' => 'approved',
        ]);
        $service = app(GuardianRelationshipVerificationService::class);
        $relationship = $service->submit($relationship, $guardian, [
            $this->document('core.pdf', 'civil_registry_record'),
            $this->document('support.pdf', 'other_supporting_document'),
        ], null);

        return [$service, $admin, $guardian, $relationship];
    }

    private function document(string $name, string $type, ?int $replacementId = null): array
    {
        return [
            'document_type' => $type, 'document_side' => 'not_applicable', 'pairing_key' => null,
            'replacement_document_id' => $replacementId,
            'file' => UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".Str::uuid()."\n%%EOF"),
        ];
    }
}
