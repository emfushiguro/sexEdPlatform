<?php

namespace Tests\Feature\Identity;

use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Services\Persona\PersonaInquirySynchronizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonaSingleSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-only',
            'persona.templates.govid_selfie' => 'itmpl_test',
            'persona.government_id_classes' => ['passport' => 'pp', 'drivers_license' => 'dl'],
        ]);
        Http::preventStrayRequests();
    }

    public function test_authoritative_completion_submits_an_adult_once_without_local_images(): void
    {
        $attempt = $this->attempt();
        $this->fakeInquiry();
        $sync = app(PersonaInquirySynchronizer::class);
        $sync->sync($attempt);
        $case = $attempt->learnerVerification->fresh();

        $this->assertSame('pending', $case->status);
        $this->assertSame('persona', $case->evidence_source);
        $this->assertSame(1, $case->submission_round);
        $this->assertSame(0, $case->evidence()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        $sync->sync($attempt->fresh());
        $this->assertSame(1, $case->audits()->where('action', 'submitted')->count());
        $this->assertSame(1, $case->fresh()->submission_round);

        $this->actingAs($attempt->user)->get(route('learner.identity.create'))
            ->assertRedirect(route('learner.identity.status'));
        $this->get(route('learner.identity.status'))->assertOk()->assertSee('under review');

        $this->actingAs($this->admin())->get(route('admin.parent-verifications.index', ['type' => 'learners']))
            ->assertOk()->assertSee(route('admin.parent-verifications.learners.show', $case), false);
        $this->post(route('admin.parent-verifications.learners.approve', $case), ['submission_round' => 1])
            ->assertRedirect();
        $this->assertSame('approved', $case->fresh()->status);
    }

    public function test_guardian_completion_submits_for_manual_review_without_local_documents(): void
    {
        $attempt = $this->attempt(true);
        $this->fakeInquiry('approved');
        app(PersonaInquirySynchronizer::class)->sync($attempt);
        $guardian = $attempt->user->fresh();

        $this->assertSame('pending', $guardian->parent_verification_status);
        $this->assertSame('persona', $guardian->parent_identity_evidence_source);
        $this->assertNull($guardian->parent_id_document_path);
        $this->assertSame(1, $guardian->guardianIdentityAudits()->where('action', 'submitted')->count());
        $this->actingAs($guardian)->get(route('persona.identity.return', ['inquiry-id' => $attempt->persona_inquiry_id]))
            ->assertRedirect(route('guardian.verification.status'));
        $this->actingAs($this->admin())->postJson(route('admin.parent-verifications.parents.approve', $guardian))
            ->assertOk();
        $this->assertSame('approved', $guardian->fresh()->parent_verification_status);
    }

    public function test_foreign_mismatched_and_declined_results_do_not_submit(): void
    {
        foreach ([['completed', 'US', 'pp'], ['completed', 'PH', 'dl'], ['declined', 'PH', 'pp'], ['needs-review', 'PH', 'pp']] as [$status, $country, $class]) {
            $attempt = $this->attempt();
            $this->fakeInquiry($status, $country, $class);
            app(PersonaInquirySynchronizer::class)->sync($attempt);
            $this->assertNull($attempt->learnerVerification->fresh()->status);
            $this->assertSame(0, $attempt->learnerVerification->evidence()->count());
        }
    }

    public function test_reconciliation_cannot_resubmit_an_admin_rejected_round(): void
    {
        $attempt = $this->attempt();
        $this->fakeInquiry();
        $sync = app(PersonaInquirySynchronizer::class);
        $sync->sync($attempt);
        $case = $attempt->learnerVerification->fresh();
        $this->assertSame('pending', $case->status);
        $case->forceFill(['status' => 'rejected'])->save();
        $sync->sync($attempt->fresh());
        $this->assertSame('rejected', $case->fresh()->status);
        $this->assertSame(1, $case->fresh()->submission_round);
    }

    public function test_completed_mismatch_explains_the_block_and_offers_replacement(): void
    {
        $attempt = $this->attempt();
        $this->fakeInquiry('completed', 'US', 'dl');
        app(PersonaInquirySynchronizer::class)->sync($attempt);
        $this->actingAs($attempt->user)->get(route('learner.identity.create'))
            ->assertOk()->assertSee('Philippine-issued')
            ->assertSee('Start a new Persona attempt')
            ->assertDontSee('name="identity_front"', false);
    }

    public function test_admin_previews_are_inline_and_a_foreign_case_cannot_be_viewed(): void
    {
        $attempt = $this->attempt();
        $this->fakeInquiry();
        app(PersonaInquirySynchronizer::class)->sync($attempt);
        $case = $attempt->learnerVerification->fresh();
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.parent-verifications.learners.show', $case))
            ->assertOk()->assertSee('Viewed from Persona')
            ->assertSee('<img', false)
            ->assertSee(route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie']), false);

        $another = $this->attempt();
        $attempt->forceFill(['learner_identity_verification_id' => $another->learner_identity_verification_id])->save();
        $this->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie']))->assertNotFound();
    }

    public function test_school_review_does_not_require_a_second_local_selfie(): void
    {
        $attempt = $this->attempt(false, '2010-01-01');
        $attempt->forceFill([
            'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL, 'local_document_type' => 'school_id',
            'local_government_id_type' => null, 'template_key' => PersonaInquiry::TEMPLATE_SELFIE,
            'persona_status' => 'completed', 'verification_outcome' => 'eligible',
            'country_policy_status' => 'not_applicable', 'document_policy_status' => 'not_applicable',
            'selfie_verification_id' => 'ver_selfie',
        ])->save();
        $case = $attempt->learnerVerification;
        $this->actingAs($attempt->user)->get(route('learner.identity.create'))
            ->assertOk()->assertSee('type="hidden" name="id_selection" value="school_id"', false);
        $case->forceFill(['status' => 'pending', 'submission_round' => 1, 'document_type' => 'school_id', 'submitted_at' => now()])->save();
        Storage::disk('local')->put('school.png', 'private-test-image');
        $case->evidence()->create([
            'slot' => 'identity_front', 'storage_path' => 'school.png', 'mime_type' => 'image/png',
            'byte_size' => 18, 'width' => 800, 'height' => 600, 'submitted_at' => now(),
        ]);
        $admin = $this->admin();
        config(['persona.enabled' => false]);
        $this->actingAs($admin)->get(route('admin.parent-verifications.learners.show', $case))
            ->assertOk()->assertSee('name="persona_portrait_compared"', false);
        $this->post(route('admin.parent-verifications.learners.approve', $case), ['submission_round' => 1])
            ->assertSessionHasErrors('persona_portrait_compared');
        $this->post(route('admin.parent-verifications.learners.approve', $case), [
            'submission_round' => 1, 'persona_portrait_compared' => '1',
        ])->assertRedirect();
        $this->assertSame('approved', $case->fresh()->status);
    }

    public function test_replacement_can_correct_the_id_choice_without_erasing_history(): void
    {
        $attempt = $this->attempt();
        $this->fakeInquiry('completed', 'US', 'dl');
        app(PersonaInquirySynchronizer::class)->sync($attempt);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(fn ($request) => str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/resume')
            ? Http::response(['meta' => ['session-token' => 'synthetic-session-token']])
            : Http::response(['data' => ['id' => 'inq_replacement', 'attributes' => ['status' => 'created']]]));

        $this->actingAs($attempt->user)->post(route('persona.identity.replace'), [
            'document_type' => 'government_id', 'government_id_type' => 'drivers_license',
        ])->assertRedirect();
        $this->assertNotNull($attempt->fresh()->superseded_at);
        $newAttempt = PersonaInquiry::query()->where('user_id', $attempt->user_id)->latest('attempt_number')->first();
        $this->assertSame('drivers_license', $newAttempt->local_government_id_type);
        $this->assertSame(2, $newAttempt->attempt_number);
        $this->assertSame(1, $newAttempt->target_submission_round);
    }

    public function test_school_persona_resubmission_hides_a_preserved_old_local_selfie(): void
    {
        $attempt = $this->attempt(false, '2010-01-01');
        $attempt->forceFill([
            'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL, 'local_document_type' => 'school_id',
            'local_government_id_type' => null, 'verification_outcome' => 'eligible',
            'selfie_verification_id' => 'ver_current_selfie',
        ])->save();
        $case = $attempt->learnerVerification;
        $case->forceFill([
            'status' => 'pending', 'submission_round' => 1, 'document_type' => 'school_id',
            'evidence_source' => 'persona_selfie', 'submitted_at' => now(),
        ])->save();
        Storage::disk('local')->put('old-selfie.png', 'old private image');
        $case->evidence()->create([
            'slot' => 'selfie', 'storage_path' => 'old-selfie.png', 'mime_type' => 'image/png',
            'byte_size' => 17, 'width' => 800, 'height' => 600, 'submitted_at' => now()->subDay(),
        ]);
        $this->actingAs($this->admin())->get(route('admin.parent-verifications.learners.show', $case))
            ->assertOk()->assertSee(route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie']), false)
            ->assertDontSee(route('admin.parent-verifications.learners.evidence', [$case, 'selfie']), false);
        Storage::disk('local')->assertExists('old-selfie.png');
    }

    public function test_refresh_of_a_local_pending_case_preserves_its_evidence_source(): void
    {
        $attempt = $this->attempt();
        $case = $attempt->learnerVerification;
        $case->forceFill(['status' => 'pending', 'submission_round' => 1, 'submitted_at' => now()])->save();
        $this->fakeInquiry();
        app(PersonaInquirySynchronizer::class)->sync($attempt);
        $this->assertSame('local', $case->fresh()->evidence_source);
        $this->assertSame('pending', $case->fresh()->status);
        $this->assertSame(0, $case->audits()->where('action', 'submitted')->count());
    }

    public function test_same_round_after_guardian_rejection_does_not_resubmit(): void
    {
        $attempt = $this->attempt(true);
        $this->fakeInquiry();
        $sync = app(PersonaInquirySynchronizer::class);
        $sync->sync($attempt);
        $guardian = $attempt->user->fresh();
        $guardian->forceFill(['parent_verification_status' => 'rejected'])->save();
        $sync->sync($attempt->fresh());
        $this->assertSame('rejected', $guardian->fresh()->parent_verification_status);
        $this->assertSame(1, $guardian->guardianIdentityAudits()->where('action', 'submitted')->count());
    }

    public function test_disabled_feature_cannot_bypass_persona_source_approval_policy(): void
    {
        $attempt = $this->attempt();
        $this->fakeInquiry();
        app(PersonaInquirySynchronizer::class)->sync($attempt);
        $case = $attempt->learnerVerification->fresh();
        $attempt->forceFill(['verification_outcome' => 'ineligible'])->save();
        config(['persona.enabled' => false]);
        $this->actingAs($this->admin())->postJson(route('admin.parent-verifications.learners.approve', $case), ['submission_round' => 1])
            ->assertStatus(422);
        $this->assertSame('pending', $case->fresh()->status);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function attempt(bool $guardian = false, string $birthdate = '2000-01-01'): PersonaInquiry
    {
        $user = User::factory()->create([
            'role' => 'learner', 'birthdate' => $birthdate, 'email_verified_at' => now(),
            'is_parent_registration' => $guardian, 'parent_verification_status' => null,
        ]);
        $user->assignRole('learner');
        $case = $guardian ? null : app(LearnerIdentityRequirement::class)->createForNewLearner($user);

        return PersonaInquiry::create([
            'user_id' => $user->id, 'learner_identity_verification_id' => $case?->id,
            'flow_type' => $guardian ? PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT : ($birthdate === '2010-01-01' ? PersonaInquiry::FLOW_TEEN_GOVERNMENT : PersonaInquiry::FLOW_ADULT_GOVERNMENT),
            'target_submission_round' => 1, 'attempt_number' => 1,
            'local_document_type' => 'government_id', 'local_government_id_type' => 'passport',
            'template_key' => 'govid_selfie', 'template_id' => 'itmpl_test',
            'reference_id' => (string) str()->uuid(), 'idempotency_key' => (string) str()->uuid(),
            'persona_inquiry_id' => 'inq_'.str()->random(12), 'is_sandbox' => true,
        ]);
    }

    private function fakeInquiry(string $status = 'completed', string $country = 'PH', string $class = 'pp'): void
    {
        Http::fake([
            '*/inquiries/*' => Http::response(['data' => [
                'attributes' => ['status' => $status],
                'relationships' => ['verifications' => ['data' => [
                    ['type' => 'verification/government-id', 'id' => 'ver_gov'],
                    ['type' => 'verification/selfie', 'id' => 'ver_selfie'],
                ]]],
            ]]),
            '*/verification/government-ids/*' => Http::response(['data' => [
                'type' => 'verification/government-id', 'id' => 'ver_gov',
                'attributes' => ['status' => 'passed', 'country-code' => $country, 'id-class' => $class],
            ]]),
            '*/verification/selfies/*' => Http::response(['data' => [
                'type' => 'verification/selfie', 'id' => 'ver_selfie', 'attributes' => ['status' => 'passed'],
            ]]),
        ]);
    }
}
