<?php

namespace Tests\Feature\Admin;

use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonaReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.enforced_from' => null,
            'persona.government_id_classes' => ['passport' => 'pp'],
        ]);
    }

    public function test_learner_approval_is_denied_without_the_current_eligible_attempt(): void
    {
        $admin = $this->admin();
        $case = $this->learnerCase('adult', 'government_id', 'passport');

        $this->actingAs($admin)
            ->postJson(route('admin.parent-verifications.learners.approve', $case), [
                'submission_round' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Persona verification is required before learner approval.');

        $this->assertSame('pending', $case->fresh()->status);
    }

    public function test_school_approval_requires_explicit_portrait_comparison_and_persona_is_not_the_decision(): void
    {
        $admin = $this->admin();
        $case = $this->learnerCase('teen', 'school_id', null);
        $this->eligibleAttempt($case, PersonaInquiry::FLOW_TEEN_SCHOOL, 'school_id', null);

        $this->actingAs($admin)
            ->post(route('admin.parent-verifications.learners.approve', $case), [
                'submission_round' => 1,
            ])
            ->assertSessionHasErrors('persona_portrait_compared');
        $this->assertSame('pending', $case->fresh()->status);

        $this->post(route('admin.parent-verifications.learners.approve', $case), [
            'submission_round' => 1,
            'persona_portrait_compared' => '1',
        ])->assertRedirect();

        $this->assertSame('approved', $case->fresh()->status);
    }

    public function test_guardian_approval_is_denied_without_persona_but_allowed_with_current_eligible_attempt(): void
    {
        $admin = $this->admin();
        $guardian = User::factory()->create([
            'role' => 'learner',
            'is_parent_registration' => true,
            'email_verified_at' => now(),
            'parent_verification_status' => 'pending',
            'parent_id_type' => 'passport',
            'parent_verification_submitted_at' => now(),
        ]);
        $guardian->assignRole('learner');

        $this->actingAs($admin)
            ->postJson(route('admin.parent-verifications.parents.approve', $guardian))
            ->assertStatus(422);
        $this->assertSame('pending', $guardian->fresh()->parent_verification_status);

        PersonaInquiry::create([
            'user_id' => $guardian->id,
            'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT,
            'target_submission_round' => 1,
            'attempt_number' => 1,
            'local_document_type' => 'government_id',
            'local_government_id_type' => 'passport',
            'template_key' => PersonaInquiry::TEMPLATE_GOVID_SELFIE,
            'template_id' => 'tmpl_govid_test',
            'reference_id' => (string) str()->uuid(),
            'idempotency_key' => (string) str()->uuid(),
            'persona_inquiry_id' => 'inq_guardian_review',
            'persona_status' => 'completed',
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_code' => 'PH',
            'id_class' => 'pp',
            'country_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'document_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'is_sandbox' => true,
        ]);

        $this->postJson(route('admin.parent-verifications.parents.approve', $guardian))
            ->assertOk()
            ->assertJsonPath('status', 'approved');
    }

    public function test_review_summary_is_sandbox_labeled_without_raw_provider_identifiers_or_urls(): void
    {
        $admin = $this->admin();
        $case = $this->learnerCase('adult', 'government_id', 'passport');
        $attempt = $this->eligibleAttempt($case, PersonaInquiry::FLOW_ADULT_GOVERNMENT, 'government_id', 'passport');
        $attempt->update([
            'government_id_verification_id' => 'gov_secret_identifier',
            'selfie_verification_id' => 'selfie_secret_identifier',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.parent-verifications.learners.show', $case))
            ->assertOk()
            ->assertSee('Simulated Sandbox result', false)
            ->assertSee('View Persona selfie', false)
            ->assertDontSee('gov_secret_identifier', false)
            ->assertDontSee('selfie_secret_identifier', false)
            ->assertDontSee('files.withpersona.com', false);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function learnerCase(string $pathway, string $documentType, ?string $governmentIdType): LearnerIdentityVerification
    {
        $birthdate = $pathway === 'teen' ? '2010-01-01' : '2000-01-01';
        $learner = User::factory()->create([
            'role' => 'learner',
            'birthdate' => $birthdate,
            'email_verified_at' => now(),
        ]);
        $learner->assignRole('learner');
        $case = app(\App\Services\Identity\LearnerIdentityRequirement::class)->createForNewLearner($learner);
        $case->update([
            'pathway' => $pathway,
            'document_type' => $documentType,
            'government_id_type' => $governmentIdType,
            'status' => 'pending',
            'submission_round' => 1,
            'submitted_at' => now(),
        ]);

        $slots = ['identity_front', 'selfie'];
        if ($documentType === 'government_id' && (bool) data_get(config('guardian_identity.id_types', []), $governmentIdType.'.requires_back', false)) {
            $slots[] = 'identity_back';
        }
        foreach ($slots as $slot) {
            $path = 'learner-verifications/'.$learner->id.'/'.$slot.'.png';
            Storage::disk('local')->put($path, 'private image bytes');
            $case->evidence()->create([
                'slot' => $slot,
                'storage_path' => $path,
                'mime_type' => 'image/png',
                'byte_size' => 19,
                'width' => 800,
                'height' => 600,
                'submitted_at' => now(),
            ]);
        }

        return $case->load('learner');
    }

    private function eligibleAttempt(LearnerIdentityVerification $case, string $flow, string $documentType, ?string $governmentIdType): PersonaInquiry
    {
        return PersonaInquiry::create([
            'user_id' => $case->user_id,
            'learner_identity_verification_id' => $case->id,
            'flow_type' => $flow,
            'target_submission_round' => 1,
            'attempt_number' => 1,
            'local_document_type' => $documentType,
            'local_government_id_type' => $governmentIdType,
            'template_key' => $flow === PersonaInquiry::FLOW_TEEN_SCHOOL ? PersonaInquiry::TEMPLATE_SELFIE : PersonaInquiry::TEMPLATE_GOVID_SELFIE,
            'template_id' => 'tmpl_test',
            'reference_id' => (string) str()->uuid(),
            'idempotency_key' => (string) str()->uuid(),
            'persona_inquiry_id' => 'inq_learner_review_'.str()->random(6),
            'persona_status' => 'completed',
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_code' => $flow === PersonaInquiry::FLOW_TEEN_SCHOOL ? null : 'PH',
            'id_class' => $flow === PersonaInquiry::FLOW_TEEN_SCHOOL ? null : 'pp',
            'country_policy_status' => $flow === PersonaInquiry::FLOW_TEEN_SCHOOL ? PersonaInquiry::POLICY_NOT_APPLICABLE : PersonaInquiry::POLICY_ELIGIBLE,
            'document_policy_status' => $flow === PersonaInquiry::FLOW_TEEN_SCHOOL ? PersonaInquiry::POLICY_NOT_APPLICABLE : PersonaInquiry::POLICY_ELIGIBLE,
            'is_sandbox' => true,
        ]);
    }
}
