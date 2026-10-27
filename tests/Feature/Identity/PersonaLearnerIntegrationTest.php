<?php

namespace Tests\Feature\Identity;

use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonaLearnerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-persona-api-key',
            'persona.templates.selfie' => 'tmpl_selfie_test',
            'persona.templates.govid_selfie' => 'tmpl_govid_test',
            'persona.government_id_classes' => ['passport' => 'pp'],
        ]);
    }

    public function test_new_learner_sees_persona_before_any_local_file_input(): void
    {
        [$teen] = $this->learner('2010-01-01');

        $this->actingAs($teen)->get(route('learner.identity.create'))
            ->assertOk()
            ->assertSee('Identity evidence step')
            ->assertSee('Simulated Sandbox result')
            ->assertDontSee('name="identity_front"', false)
            ->assertDontSee('name="identity_back"', false);

        $this->post(route('learner.identity.document.store'), [
            'id_selection' => 'school_id',
            'identity_front' => $this->image('front.png'),
        ])->assertSessionHasErrors('persona');
        $this->assertNull(session('learner_identity_document_draft'));
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_eligible_government_attempt_does_not_request_duplicate_local_images(): void
    {
        [$adult, $case] = $this->learner('2000-01-01');
        $attempt = $this->attempt($adult, $case, [
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'document_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
        ]);

        $this->actingAs($adult)->get(route('learner.identity.create'))
            ->assertOk()
            ->assertSee('Persona evidence is eligible')
            ->assertSee('No additional ID or selfie upload is required.')
            ->assertDontSee('name="identity_front"', false)
            ->assertDontSee('<select id="id_selection"', false);

        $this->assertSame($case->id, $attempt->learner_identity_verification_id);
    }

    public function test_school_persona_flow_submits_without_a_local_selfie(): void
    {
        [$teen, $case] = $this->learner('2010-01-01');
        $this->attempt($teen, $case, [
            'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL,
            'local_document_type' => 'school_id',
            'local_government_id_type' => null,
            'template_key' => PersonaInquiry::TEMPLATE_SELFIE,
            'template_id' => 'tmpl_selfie_test',
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_policy_status' => PersonaInquiry::POLICY_NOT_APPLICABLE,
            'document_policy_status' => PersonaInquiry::POLICY_NOT_APPLICABLE,
        ]);

        $this->actingAs($teen)->post(route('learner.identity.document.store'), [
            'id_selection' => 'school_id',
            'identity_front' => $this->image('school-front.png'),
        ])->assertRedirect(route('learner.identity.selfie.create'));

        $this->get(route('learner.identity.selfie.create'))
            ->assertOk()
            ->assertSee('Persona selfie evidence is already collected')
            ->assertDontSee('name="selfie"', false);

        $this->post(route('learner.identity.store'), [
            'confirm_submission' => '1',
        ])->assertRedirect(route('learner.identity.status'));

        $this->assertSame('pending', $case->fresh()->status);
        $this->assertEqualsCanonicalizing(['identity_front'], $case->fresh()->evidence->pluck('slot')->all());
    }

    public function test_government_persona_flow_rejects_duplicate_local_uploads(): void
    {
        [$adult, $case] = $this->learner('2000-01-01');
        $this->attempt($adult, $case, [
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'document_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
        ]);

        $this->actingAs($adult)->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:passport',
            'identity_front' => $this->image('front.png'),
            'identity_back' => $this->image('back.png'),
        ])->assertSessionHasErrors('persona');
        $this->assertNull(session('learner_identity_document_draft'));
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertNull($case->fresh()->status);
    }

    public function test_wrong_round_type_or_superseded_attempt_cannot_submit_local_evidence(): void
    {
        foreach ([
            'wrong type' => ['local_document_type' => 'government_id', 'local_government_id_type' => 'drivers_license'],
            'wrong round' => ['target_submission_round' => 2],
            'superseded' => ['superseded_at' => now()],
        ] as $overrides) {
            [$adult, $case] = $this->learner('2000-01-01');
            $this->attempt($adult, $case, array_merge([
                'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
                'country_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
                'document_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            ], $overrides));

            $this->actingAs($adult)->post(route('learner.identity.document.store'), [
                'id_selection' => 'government_id:passport',
                'identity_front' => $this->image('front.png'),
                'identity_back' => $this->image('back.png'),
            ])->assertSessionHasErrors('persona');
            $this->assertNull(session('learner_identity_document_draft'));
        }
    }

    public function test_rejected_case_requires_the_next_persona_round_but_pending_and_approved_are_grandfathered(): void
    {
        [$rejected, $case] = $this->learner('2000-01-01');
        $case->forceFill(['status' => 'rejected', 'submission_round' => 1])->save();
        $this->attempt($rejected, $case, [
            'target_submission_round' => 1,
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'document_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
        ]);
        $this->actingAs($rejected)->get(route('learner.identity.create'))
            ->assertOk()
            ->assertDontSee('name="identity_front"', false);

        [$pending, $pendingCase] = $this->learner('2000-01-01');
        $pendingCase->forceFill(['status' => 'pending'])->save();
        $this->actingAs($pending)->get(route('learner.identity.create'))
            ->assertRedirect(route('learner.identity.status'));

        [$approved, $approvedCase] = $this->learner('2000-01-01');
        $approvedCase->forceFill(['status' => 'approved'])->save();
        $this->actingAs($approved)->get(route('learner.identity.create'))
            ->assertRedirect(route('profile.complete'));
    }

    private function learner(string $birthdate): array
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'birthdate' => $birthdate,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('learner');

        return [$user, app(LearnerIdentityRequirement::class)->createForNewLearner($user)];
    }

    private function attempt(User $user, $case, array $overrides = []): PersonaInquiry
    {
        return PersonaInquiry::create(array_merge([
            'user_id' => $user->id,
            'learner_identity_verification_id' => $case->id,
            'flow_type' => PersonaInquiry::FLOW_ADULT_GOVERNMENT,
            'target_submission_round' => 1,
            'attempt_number' => 1,
            'local_document_type' => 'government_id',
            'local_government_id_type' => 'passport',
            'template_key' => PersonaInquiry::TEMPLATE_GOVID_SELFIE,
            'template_id' => 'tmpl_govid_test',
            'reference_id' => (string) str()->uuid(),
            'idempotency_key' => (string) str()->uuid(),
            'persona_inquiry_id' => 'inq_'.str()->random(12),
            'persona_status' => 'completed',
            'verification_outcome' => PersonaInquiry::OUTCOME_NOT_READY,
            'is_sandbox' => true,
        ], $overrides));
    }

    private function image(string $name): UploadedFile
    {
        $chunk = static function (string $type, string $data): string {
            return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        };
        $raw = str_repeat("\x00".str_repeat("\x00\x00\x00", 800), 600);
        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', 800, 600, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress($raw))
            .$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
