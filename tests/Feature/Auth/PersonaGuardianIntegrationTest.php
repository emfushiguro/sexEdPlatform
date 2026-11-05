<?php

namespace Tests\Feature\Auth;

use App\Models\GuardianIdentityAudit;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\ParentChildVerificationService;
use App\Services\Persona\PersonaReviewSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonaGuardianIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-persona-api-key',
            'persona.templates.govid_selfie' => 'tmpl_govid_test',
            'persona.government_id_classes' => ['passport' => 'pp'],
        ]);
    }

    public function test_new_guardian_sees_persona_before_local_id_input(): void
    {
        $guardian = $this->guardian();

        $this->actingAs($guardian)->get(route('guardian.verification.create'))
            ->assertOk()
            ->assertSee('Identity evidence step')
            ->assertDontSee('Simulated Sandbox result')
            ->assertDontSee('name="government_id_front"', false);

        $this->post(route('guardian.verification.store'), $this->guardianFiles())
            ->assertSessionHasErrors('persona');
        $this->assertNull($guardian->fresh()->parent_verification_status);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_eligible_guardian_attempt_does_not_request_a_duplicate_local_form(): void
    {
        $guardian = $this->guardian();
        $attempt = $this->attempt($guardian);

        $this->actingAs($guardian)->get(route('guardian.verification.create'))
            ->assertOk()
            ->assertSee('Persona evidence is eligible')
            ->assertSee('No additional ID or selfie upload is required.')
            ->assertDontSee('name="government_id_front"', false)
            ->assertDontSee('<select id="government_id_type"', false);

        $this->assertSame($guardian->id, $attempt->user_id);
    }

    public function test_guardian_submit_requires_eligible_owned_attempt_and_appends_audit(): void
    {
        $guardian = $this->guardian();
        $attempt = $this->attempt($guardian);

        app(PersonaReviewSubmission::class)->submit($attempt);

        $guardian->refresh();
        $this->assertSame('pending', $guardian->parent_verification_status);
        $audit = GuardianIdentityAudit::query()->where('guardian_id', $guardian->id)->firstOrFail();
        $this->assertSame('submitted', $audit->action);
        $this->assertSame($attempt->id, $audit->persona_inquiry_id);
        $this->assertSame(1, $audit->attempt_number);
    }

    public function test_pending_and_approved_guardians_are_grandfathered(): void
    {
        foreach (['pending', 'approved'] as $status) {
            $guardian = $this->guardian(['parent_verification_status' => $status]);

            $this->actingAs($guardian)->get(route('guardian.verification.create'))
                ->assertRedirect(route('guardian.verification.status'));
            $this->assertDatabaseCount('persona_inquiries', 0);
        }
    }

    public function test_rejection_and_resubmission_keep_persona_and_audit_history(): void
    {
        $guardian = $this->guardian();
        $first = $this->attempt($guardian);

        app(PersonaReviewSubmission::class)->submit($first);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        app(ParentChildVerificationService::class)->rejectParent($guardian, 'The document is blurred.', personaAttemptId: $first->id);

        $second = $this->attempt($guardian, ['target_submission_round' => 2, 'attempt_number' => 2]);
        app(PersonaReviewSubmission::class)->submit($second);

        $audits = GuardianIdentityAudit::query()->where('guardian_id', $guardian->id)->orderBy('id')->get();
        $this->assertSame(['submitted', 'rejected', 'submitted'], $audits->pluck('action')->all());
        $this->assertSame([$first->id, null, $second->id], $audits->pluck('persona_inquiry_id')->all());
        $this->assertSame([1, 1, 2], $audits->pluck('attempt_number')->all());
        $this->assertSame('pending', $guardian->fresh()->parent_verification_status);
    }

    public function test_persona_success_alone_does_not_approve_guardian_or_relationships(): void
    {
        $guardian = $this->guardian();
        $this->attempt($guardian);

        $this->assertNull($guardian->fresh()->parent_verification_status);
        $this->actingAs($guardian)->get(route('guardian.verification.create'))
            ->assertOk()
            ->assertSee('Persona evidence is eligible');
    }

    private function guardian(array $overrides = []): User
    {
        $guardian = User::factory()->create(array_merge([
            'role' => 'learner',
            'birthdate' => '1980-01-01',
            'is_parent_registration' => true,
            'email_verified_at' => now(),
            'parent_verification_status' => null,
        ], $overrides));
        $guardian->assignRole('learner');

        return $guardian;
    }

    private function attempt(User $guardian, array $overrides = []): PersonaInquiry
    {
        return PersonaInquiry::create(array_merge([
            'user_id' => $guardian->id,
            'learner_identity_verification_id' => null,
            'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT,
            'target_submission_round' => 1,
            'attempt_number' => 1,
            'local_document_type' => 'government_id',
            'local_government_id_type' => 'passport',
            'template_key' => PersonaInquiry::TEMPLATE_GOVID_SELFIE,
            'template_id' => 'tmpl_govid_test',
            'reference_id' => (string) str()->uuid(),
            'idempotency_key' => (string) str()->uuid(),
            'persona_inquiry_id' => 'inq_guardian_'.str()->random(10),
            'persona_status' => 'completed',
            'government_id_verification_id' => 'ver_test_gov',
            'selfie_verification_id' => 'ver_test_selfie',
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_code' => 'PH',
            'id_class' => 'pp',
            'country_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'document_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'is_sandbox' => true,
        ], $overrides));
    }

    private function guardianFiles(): array
    {
        return [
            'government_id_type' => 'passport',
            'government_id_front' => UploadedFile::fake()->create('front.jpg', 100, 'image/jpeg'),
            'government_id_back' => UploadedFile::fake()->create('back.jpg', 100, 'image/jpeg'),
            'confirm_submission' => '1',
        ];
    }
}
