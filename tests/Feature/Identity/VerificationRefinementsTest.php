<?php

namespace Tests\Feature\Identity;

use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationRefinementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        Http::preventStrayRequests();
        config(['persona.enabled' => true, 'persona.environment' => 'sandbox', 'persona.api_key' => 'test-only',
            'persona.templates.selfie' => 'itmpl_selfie', 'persona.templates.govid_selfie' => 'itmpl_gov']);
    }

    public function test_school_id_is_saved_before_persona_starts(): void
    {
        [$teen] = $this->teen();
        $this->actingAs($teen)->post(route('learner.identity.document.store'), [
            'id_selection' => 'school_id', 'identity_front' => $this->image(),
        ])->assertRedirect(route('learner.identity.selfie.create'));
        $this->assertNotNull(session('learner_identity_document_draft.identity_front_path'));
        $this->assertSame(0, PersonaInquiry::count());
        $this->get(route('learner.identity.selfie.create'))->assertOk()->assertSee('Start selfie verification');
        $this->postJson(route('learner.identity.store'), ['confirm_submission' => '1'])->assertUnprocessable();
    }

    public function test_persona_school_start_cannot_skip_id_capture(): void
    {
        [$teen] = $this->teen();
        Http::fake(['*' => Http::response(['data' => ['id' => 'inq_test', 'attributes' => ['status' => 'created']], 'meta' => ['session-token' => 'test-token']])]);
        $this->actingAs($teen)->postJson(route('persona.identity.start'), ['document_type' => 'school_id'])
            ->assertUnprocessable();
        $this->assertSame(0, PersonaInquiry::count());
        Http::assertNothingSent();
    }

    private function teen(): array
    {
        $user = User::factory()->create(['role' => 'learner', 'birthdate' => '2009-12-01', 'email_verified_at' => now()]);
        $user->assignRole('learner');

        return [$user, app(LearnerIdentityRequirement::class)->createForNewLearner($user)];
    }

    public function test_id_only_correction_retains_selfie_but_requires_a_new_id_and_comparison(): void
    {
        [$teen, $case, $attempt] = $this->submittedSchool();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.parent-verifications.learners.reject', $case), [
            'submission_round' => 1, 'reason' => 'unclear_id', 'requested_corrections' => ['identity_front'],
            'correction_note' => 'Keep all corners visible.',
        ])->assertRedirect();
        $this->assertSame(['identity_front'], $case->fresh()->requested_corrections);
        $this->actingAs($teen)->postJson(route('learner.identity.document.store'), ['id_selection' => 'school_id', 'submission_round' => 1])
            ->assertUnprocessable();
        $this->postJson(route('learner.identity.document.store'), ['id_selection' => 'school_id', 'submission_round' => 0, 'identity_front' => $this->image()])
            ->assertUnprocessable();
        $this->post(route('learner.identity.document.store'), ['id_selection' => 'school_id', 'submission_round' => 1, 'identity_front' => $this->image()])
            ->assertRedirect(route('learner.identity.selfie.create'));
        $this->get(route('learner.identity.selfie.create'))->assertOk()->assertSee('Your selfie is ready.')->assertDontSee('Start selfie verification');
        $this->postJson(route('learner.identity.store'), ['confirm_submission' => '1', 'submission_round' => 0])->assertUnprocessable();
        $this->post(route('learner.identity.store'), ['confirm_submission' => '1', 'submission_round' => 1])->assertRedirect(route('learner.identity.status'));
        $case->refresh();
        $this->assertSame(2, $case->submission_round);
        $this->assertSame($attempt->id, $case->persona_evidence_attempt_id);
        $this->assertSame(1, $attempt->fresh()->target_submission_round);
        $this->actingAs($admin)->postJson(route('admin.parent-verifications.learners.approve', $case), ['submission_round' => 2])->assertUnprocessable();
        $this->post(route('admin.parent-verifications.learners.approve', $case), ['submission_round' => 2, 'persona_portrait_compared' => 1])->assertRedirect();
        $this->assertSame('approved', $case->fresh()->status);
    }

    public function test_selfie_correction_keeps_id_and_cannot_reuse_the_rejected_selfie(): void
    {
        [$teen, $case] = $this->submittedSchool();
        $path = $case->evidence()->where('slot', 'identity_front')->value('storage_path');
        $this->actingAs($this->admin())->post(route('admin.parent-verifications.learners.reject', $case), [
            'submission_round' => 1, 'reason' => 'unclear_selfie', 'requested_corrections' => ['selfie'],
        ])->assertRedirect();
        $this->actingAs($teen)->post(route('learner.identity.document.store'), ['id_selection' => 'school_id', 'submission_round' => 1])
            ->assertRedirect(route('learner.identity.selfie.create'));
        $this->get(route('learner.identity.selfie.create'))->assertOk()->assertSee('Start selfie verification');
        $this->postJson(route('learner.identity.store'), ['confirm_submission' => '1'])->assertUnprocessable();
        $this->assertSame($path, $case->evidence()->where('slot', 'identity_front')->value('storage_path'));
        Storage::disk('local')->assertExists($path);
    }

    public function test_final_rejection_cannot_be_reopened_by_direct_requests(): void
    {
        [$teen, $case] = $this->submittedSchool();
        $this->actingAs($this->admin())->post(route('admin.parent-verifications.learners.reject', $case), [
            'submission_round' => 1, 'reason' => 'unsupported_document_type', 'allow_resubmission' => '0',
        ])->assertRedirect();
        $this->actingAs($teen)->postJson(route('learner.identity.document.store'), ['id_selection' => 'school_id', 'identity_front' => $this->image()])->assertForbidden();
        $this->postJson(route('persona.identity.start'), ['document_type' => 'school_id'])->assertUnprocessable();
        $this->get(route('learner.identity.create'))->assertRedirect(route('learner.identity.status'));
    }

    private function submittedSchool(): array
    {
        [$teen, $case] = $this->teen();
        $this->actingAs($teen)->post(route('learner.identity.document.store'), ['id_selection' => 'school_id', 'identity_front' => $this->image()])->assertRedirect();
        $attempt = PersonaInquiry::create([
            'user_id' => $teen->id, 'learner_identity_verification_id' => $case->id, 'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL,
            'target_submission_round' => 1, 'attempt_number' => 1, 'local_document_type' => 'school_id', 'local_government_id_type' => null,
            'template_key' => 'selfie', 'template_id' => 'itmpl_selfie', 'reference_id' => (string) str()->uuid(), 'idempotency_key' => (string) str()->uuid(),
            'persona_inquiry_id' => 'inq_'.str()->random(12), 'selfie_verification_id' => 'ver_selfie', 'is_sandbox' => true,
            'persona_status' => 'completed', 'verification_outcome' => 'eligible', 'country_policy_status' => 'not_applicable', 'document_policy_status' => 'not_applicable',
        ]);
        $this->post(route('learner.identity.store'), ['confirm_submission' => '1'])->assertRedirect(route('learner.identity.status'));

        return [$teen, $case->fresh(), $attempt];
    }

    public function test_replace_cannot_switch_to_school_selfie_without_a_current_id_draft(): void
    {
        [$teen, $case, $attempt] = $this->submittedSchool();
        $case->forceFill(['status' => 'rejected', 'requested_corrections' => ['selfie']])->save();
        $attempt->forceFill(['target_submission_round' => 2, 'flow_type' => PersonaInquiry::FLOW_TEEN_GOVERNMENT,
            'local_document_type' => 'government_id', 'local_government_id_type' => 'national_id', 'persona_status' => 'failed'])->save();
        session()->forget('learner_identity_document_draft');
        $this->actingAs($teen)->postJson(route('persona.identity.replace'), ['document_type' => 'school_id'])->assertUnprocessable();
        $this->assertNull($attempt->fresh()->superseded_at);
        $this->assertSame(1, PersonaInquiry::count());
        Http::assertNothingSent();
    }

    public function test_failed_teen_government_attempt_exposes_replacement_controls(): void
    {
        [$teen, $case, $attempt] = $this->submittedSchool();
        $case->forceFill(['status' => 'rejected', 'document_type' => 'government_id', 'government_id_type' => 'national_id', 'requested_corrections' => ['persona_identity']])->save();
        $attempt->forceFill(['target_submission_round' => 2, 'flow_type' => PersonaInquiry::FLOW_TEEN_GOVERNMENT,
            'local_document_type' => 'government_id', 'local_government_id_type' => 'national_id', 'persona_status' => 'failed', 'verification_outcome' => 'retryable'])->save();
        $this->actingAs($teen)->get(route('learner.identity.create'))->assertOk()->assertSee(route('persona.identity.replace'), false)->assertSee('Start a new Persona attempt');
    }

    public function test_teen_can_change_from_government_id_to_a_newly_captured_school_id(): void
    {
        [$teen, $case, $attempt] = $this->submittedSchool();
        $case->forceFill(['status' => 'rejected', 'document_type' => 'government_id', 'government_id_type' => 'national_id', 'requested_corrections' => ['persona_identity']])->save();
        $attempt->forceFill(['target_submission_round' => 2, 'flow_type' => PersonaInquiry::FLOW_TEEN_GOVERNMENT,
            'local_document_type' => 'government_id', 'local_government_id_type' => 'national_id', 'persona_status' => 'failed', 'verification_outcome' => 'retryable'])->save();
        $this->actingAs($teen)->post(route('learner.identity.document.store'), ['id_selection' => 'school_id', 'submission_round' => 1, 'identity_front' => $this->image()])->assertRedirect();
        Http::fake(['*' => Http::response(['data' => ['id' => 'inq_new_school', 'attributes' => ['status' => 'created']], 'meta' => ['session-token' => 'test-token']])]);
        $this->post(route('persona.identity.start'), ['document_type' => 'school_id'])->assertRedirect();
        $this->assertNotNull($attempt->fresh()->superseded_at);
        $this->assertSame(PersonaInquiry::FLOW_TEEN_SCHOOL, PersonaInquiry::latest('id')->first()->flow_type);
        $this->assertNull(PersonaInquiry::latest('id')->first()->selfie_verification_id);
    }

    public function test_guardian_persona_rejection_requires_the_current_attempt_and_preserves_state_on_failure(): void
    {
        [$teen, $case, $attempt] = $this->submittedSchool();
        $guardian = User::factory()->create(['role' => 'learner', 'birthdate' => '1990-01-01', 'email_verified_at' => now(),
            'is_parent_registration' => true, 'parent_verification_status' => 'pending']);
        $guardian->assignRole('learner');
        $guardian->forceFill(['parent_identity_evidence_source' => 'persona'])->save();
        $attempt->forceFill(['user_id' => $guardian->id, 'learner_identity_verification_id' => null, 'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT])->save();
        $this->actingAs($this->admin());
        $payload = ['reason_code' => 'blurry_document', 'allow_resubmission' => '0', 'requested_corrections' => ['persona_identity']];
        $this->postJson(route('admin.parent-verifications.parents.reject', $guardian), $payload)->assertUnprocessable();
        $this->postJson(route('admin.parent-verifications.parents.reject', $guardian), $payload + ['persona_attempt_id' => $attempt->id + 1])->assertConflict();
        $this->assertSame('pending', $guardian->fresh()->parent_verification_status);
        $this->assertTrue($guardian->fresh()->parent_identity_resubmission_allowed);
        $this->assertSame(0, $guardian->guardianIdentityAudits()->count());
        $this->postJson(route('admin.parent-verifications.parents.reject', $guardian), $payload + ['persona_attempt_id' => $attempt->id])->assertOk();
        $this->assertFalse($guardian->fresh()->parent_identity_resubmission_allowed);
        $this->assertSame([], $guardian->fresh()->parent_identity_requested_corrections);
        $this->assertSame('rejected', $guardian->fresh()->parent_verification_status);
    }

    public function test_requested_local_selfie_is_required_even_when_an_older_image_exists(): void
    {
        [$teen, $case] = $this->submittedSchool();
        config(['persona.enabled' => false]);
        $case->forceFill(['status' => 'rejected', 'requested_corrections' => ['selfie']])->save();
        $front = $case->evidence()->where('slot', 'identity_front')->first();
        $copy = $front->replicate();
        $copy->slot = 'selfie';
        $copy->save();
        $data = ['document_type' => 'school_id', 'submission_round' => 1, 'confirm_submission' => 1];
        $request = \App\Http\Requests\Auth\SubmitLearnerIdentityRequest::create('/', 'POST', $data);
        $request->setUserResolver(fn () => $teen);
        $validator = \Illuminate\Support\Facades\Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('selfie'));
    }

    public function test_guardian_persona_rejection_persists_selected_corrections_for_resubmission(): void
    {
        [$teen, $case, $attempt] = $this->submittedSchool();
        $guardian = User::factory()->create([
            'role' => 'learner',
            'birthdate' => '1990-01-01',
            'email_verified_at' => now(),
            'is_parent_registration' => true,
            'parent_verification_status' => 'pending',
        ]);
        $guardian->assignRole('learner');
        $guardian->forceFill(['parent_identity_evidence_source' => 'persona'])->save();
        $attempt->forceFill([
            'user_id' => $guardian->id,
            'learner_identity_verification_id' => null,
            'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT,
        ])->save();

        $this->actingAs($this->admin())
            ->postJson(route('admin.parent-verifications.parents.reject', $guardian), [
                'reason_code' => 'blurry_document',
                'allow_resubmission' => '1',
                'requested_corrections' => ['persona_identity'],
                'persona_attempt_id' => $attempt->id,
            ])
            ->assertOk();

        $this->assertSame(['persona_identity'], $guardian->fresh()->parent_identity_requested_corrections);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function image(): UploadedFile
    {
        $width = 640;
        $height = 480;
        $chunk = static fn ($type, $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $png = "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress(str_repeat("\0".str_repeat("\x80", $width * 3), $height))).$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('captured-id.png', $png);
    }
}
