<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureGuardianVerified;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipCorrectionDraft;
use App\Services\GuardianRelationshipVerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuardianRelationshipCorrectionDraftHttpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        $this->withCredentials()->withCookie(config('session.cookie'), Str::random(40));
        $this->withoutMiddleware([EnsureGuardianVerified::class, EnsureProfileCompleted::class]);
    }

    public function test_individual_saves_survive_reload_and_final_submission_uses_saved_files(): void
    {
        [$guardian, $relationship, $support] = $this->corrections();
        $this->actingAs($guardian);
        $this->save($relationship, 'document:'.$support->id, 'replacement.pdf')->assertOk()->assertExactJson(['name' => 'replacement.pdf']);
        $this->get(route('parent.relationship-verifications.show', $relationship))
            ->assertOk()->assertSee('replacement.pdf')->assertSee('Save replacement')->assertSee('Submit all corrections');
        $this->assertSame(1, $relationship->fresh()->current_evidence_round);
        $this->assertSame(2, $relationship->verificationDocuments()->count());

        $this->post(route('parent.relationship-verifications.store', $relationship), $this->finalPayload())
            ->assertSessionHasErrors('documents');
        $this->save($relationship, 'type:agency_document', 'agency.pdf')->assertOk();
        $this->get(route('parent.relationship-verifications.show', $relationship))
            ->assertOk()->assertSee('replacement.pdf')->assertSee('agency.pdf');
        $this->post(route('parent.relationship-verifications.store', $relationship), $this->finalPayload())
            ->assertRedirect(route('parent.relationship-verifications.show', $relationship))->assertSessionHasNoErrors();

        $fresh = $relationship->fresh();
        $this->assertSame(2, $fresh->current_evidence_round);
        $this->assertSame('under_review', $fresh->relationship_verified_status);
        $round = $fresh->verificationDocuments()->where('submission_round', 2)->get();
        $this->assertCount(3, $round);
        $this->assertSame('replacement.pdf', $round->firstWhere('document_type', 'other_supporting_document')->original_name);
        $this->assertSame('accepted', $round->firstWhere('document_type', 'civil_registry_record')->review_status);
    }

    public function test_foreign_owner_stale_round_unknown_slot_and_invalid_file_cannot_change_a_save(): void
    {
        [$guardian, $relationship, $support] = $this->corrections();
        $this->actingAs($guardian);
        $this->save($relationship, 'document:'.$support->id, 'saved.pdf')->assertOk();
        $this->save($relationship, 'document:'.$support->id, 'stale.pdf', 0)->assertUnprocessable()->assertJsonValidationErrors('current_evidence_round');
        $this->save($relationship, 'document:999999', 'foreign.pdf')->assertUnprocessable()->assertJsonValidationErrors('slot');
        $this->save($relationship, 'type:court_order', 'unrequested.pdf')->assertUnprocessable()->assertJsonValidationErrors('slot');
        $this->postJson(route('parent.relationship-verifications.draft.store', $relationship), [
            'current_evidence_round' => 1, 'slot' => 'document:'.$support->id,
            'file' => UploadedFile::fake()->createWithContent('bad.pdf', 'This is not a PDF.'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $other = User::factory()->create(['status' => User::STATUS_ACTIVE, 'is_parent_registration' => true, 'parent_verification_status' => 'approved']);
        $other->assignRole('learner');
        $this->actingAs($other)->get(route('parent.relationship-verifications.show', $relationship))->assertForbidden();
        $this->save($relationship, 'document:'.$support->id, 'foreign.pdf')->assertForbidden();
        $this->actingAs($guardian)->get(route('parent.relationship-verifications.show', $relationship))
            ->assertOk()->assertSee('saved.pdf')->assertDontSee('stale.pdf')->assertDontSee('foreign.pdf');
    }

    public function test_validation_failure_retains_drafts_and_direct_uploads_cannot_bypass_saving(): void
    {
        [$guardian, $relationship, $support] = $this->corrections();
        $this->actingAs($guardian);
        $this->save($relationship, 'document:'.$support->id, 'saved.pdf')->assertOk();
        $this->post(route('parent.relationship-verifications.store', $relationship), $this->finalPayload() + [
            'documents' => [$this->document('direct.pdf', 'agency_document')],
        ])->assertSessionHasErrors('documents');
        $this->save($relationship, 'type:agency_document', 'agency.pdf')->assertOk();
        $this->post(route('parent.relationship-verifications.store', $relationship), ['current_evidence_round' => 1])
            ->assertSessionHasErrors('confirm_submission');
        $this->get(route('parent.relationship-verifications.show', $relationship))
            ->assertOk()->assertSee('saved.pdf')->assertSee('agency.pdf');
        $this->assertSame(1, $relationship->fresh()->current_evidence_round);
    }

    public function test_atomic_service_failure_preserves_saved_files_and_the_evidence_round(): void
    {
        [$guardian, $relationship, $support] = $this->corrections();
        $this->actingAs($guardian);
        $core = $relationship->verificationDocuments()->where('document_type', 'civil_registry_record')->firstOrFail();
        $duplicate = UploadedFile::fake()->createWithContent('duplicate.pdf', Storage::disk('local')->get($core->path));
        $this->postJson(route('parent.relationship-verifications.draft.store', $relationship), [
            'current_evidence_round' => 1, 'slot' => 'document:'.$support->id, 'file' => $duplicate,
        ])->assertOk();
        $this->save($relationship, 'type:agency_document', 'agency.pdf')->assertOk();
        $this->post(route('parent.relationship-verifications.store', $relationship), $this->finalPayload())
            ->assertSessionHasErrors('documents');
        $this->assertSame(1, $relationship->fresh()->current_evidence_round);
        $this->assertCount(2, app(GuardianRelationshipCorrectionDraft::class)->current($guardian, $relationship->fresh()));
        Storage::disk('local')->assertExists($core->path);
    }

    public function test_closed_claims_cannot_save_or_submit_saved_drafts(): void
    {
        [$guardian, $relationship, $support] = $this->corrections();
        $this->actingAs($guardian);
        $this->save($relationship, 'document:'.$support->id, 'saved.pdf')->assertOk();
        $relationship->update(['relationship_status' => ParentChildAccount::STATUS_INACTIVE]);
        $this->save($relationship, 'type:agency_document', 'agency.pdf')->assertUnprocessable();
        $this->post(route('parent.relationship-verifications.store', $relationship), $this->finalPayload())
            ->assertSessionHasErrors('documents');
        $this->assertSame(1, $relationship->fresh()->current_evidence_round);
    }

    private function corrections(): array
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
            $this->document('core.pdf', 'civil_registry_record'), $this->document('support.pdf', 'other_supporting_document'),
        ], null);
        $support = $relationship->verificationDocuments()->where('document_type', 'other_supporting_document')->firstOrFail();
        $service->reject($relationship, $admin, 'unclear_document', 'Replace support and add agency evidence.', true, [$support->id], ['agency_document'], 1);

        return [$guardian, $relationship->fresh(), $support];
    }

    private function save(ParentChildAccount $relationship, string $slot, string $name, int $round = 1)
    {
        return $this->postJson(route('parent.relationship-verifications.draft.store', $relationship), [
            'current_evidence_round' => $round, 'slot' => $slot, 'file' => $this->document($name, 'agency_document')['file'],
        ]);
    }

    private function finalPayload(): array
    {
        return ['current_evidence_round' => 1, 'confirm_submission' => 1];
    }

    private function document(string $name, string $type): array
    {
        return [
            'document_type' => $type, 'document_side' => 'not_applicable', 'pairing_key' => null,
            'file' => UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".Str::uuid()."\n%%EOF"),
        ];
    }
}
