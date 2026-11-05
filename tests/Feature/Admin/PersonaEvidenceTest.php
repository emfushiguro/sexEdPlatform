<?php

namespace Tests\Feature\Admin;

use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonaEvidenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-persona-api-key',
            'persona.media_hosts' => ['files.withpersona.com'],
        ]);
    }

    public function test_only_an_admin_can_view_semantic_persona_evidence(): void
    {
        $attempt = $this->guardianAttempt([
            'selfie_verification_id' => 'selfie_123',
        ]);
        $route = route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie']);

        foreach (['selfie', 'selfie-left', 'selfie-right'] as $artifact) {
            $this->get(route('admin.parent-verifications.persona.evidence', [$attempt, $artifact]))->assertRedirect(route('login'));
        }
        $this->actingAs($attempt->user);
        foreach (['selfie', 'selfie-left', 'selfie-right'] as $artifact) {
            $this->get(route('admin.parent-verifications.persona.evidence', [$attempt, $artifact]))->assertForbidden();
        }

        Http::fake([
            'https://api.withpersona.com/api/v1/verification/selfies/selfie_123' => Http::response([
                'data' => ['attributes' => ['center-photo-url' => 'https://files.withpersona.com/selfie.jpg']],
            ]),
            'https://files.withpersona.com/selfie.jpg' => Http::response(
                'image-bytes',
                200,
                ['Content-Type' => 'image/jpeg', 'Content-Length' => '11'],
            ),
        ]);

        $admin = $this->admin();
        $response = $this->actingAs($admin)->get($route);

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee('image-bytes');
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        Storage::disk('local')->assertMissing('persona');
    }

    public function test_government_artifact_uses_the_exact_returned_page_and_never_accepts_input_urls(): void
    {
        $attempt = $this->guardianAttempt([
            'government_id_verification_id' => 'gov_123',
        ]);
        $admin = $this->admin();

        Http::fake([
            'https://api.withpersona.com/api/v1/verification/government-ids/gov_123' => Http::response([
                'data' => ['attributes' => [
                    'photo-urls' => [
                        ['page' => 'back', 'url' => 'https://files.withpersona.com/back.jpg'],
                        ['page' => 'front', 'url' => 'https://files.withpersona.com/front.jpg'],
                    ],
                ]],
            ]),
            'https://files.withpersona.com/front.jpg' => Http::response(
                'front-bytes',
                200,
                ['Content-Type' => 'image/png', 'Content-Length' => '11'],
            ),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-front']).'?url=https://evil.example/leak')
            ->assertOk()
            ->assertSee('front-bytes');

        $this->actingAs($admin)
            ->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie']))
            ->assertNotFound();
    }

    public function test_missing_or_mismatched_persona_artifact_is_a_generic_not_found(): void
    {
        $attempt = $this->guardianAttempt([
            'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL,
            'selfie_verification_id' => 'selfie_123',
            'government_id_verification_id' => null,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-front']))
            ->assertNotFound();
    }

    public function test_each_selfie_pose_uses_its_own_returned_url_and_is_never_stored(): void
    {
        $attempt = $this->guardianAttempt(['selfie_verification_id' => 'selfie_poses']);
        $this->actingAs($this->admin());
        Http::preventStrayRequests();
        Http::fake([
            'https://api.withpersona.com/api/v1/verification/selfies/selfie_poses' => Http::response([
                'data' => ['attributes' => [
                    'center-photo-url' => 'https://files.withpersona.com/center.jpg',
                    'left-photo-url' => 'https://files.withpersona.com/left.jpg',
                    'right-photo-url' => 'https://files.withpersona.com/right.jpg',
                ]],
            ]),
            'https://files.withpersona.com/*' => fn ($request) => Http::response(
                basename($request->url()), 200, ['Content-Type' => 'image/jpeg', 'Content-Length' => (string) strlen(basename($request->url()))],
            ),
        ]);

        foreach (['selfie' => 'center', 'selfie-left' => 'left', 'selfie-right' => 'right'] as $artifact => $pose) {
            $response = $this->get(route('admin.parent-verifications.persona.evidence', [$attempt, $artifact]).'?url=https://evil.example/ignored');
            $response->assertOk()->assertContent($pose.'.jpg')->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_legacy_center_only_selfie_does_not_substitute_center_for_missing_poses(): void
    {
        $attempt = $this->guardianAttempt(['selfie_verification_id' => 'selfie_legacy']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.withpersona.com/api/v1/verification/selfies/selfie_legacy' => Http::response([
                'data' => ['attributes' => ['center-photo-url' => 'https://files.withpersona.com/center.jpg']],
            ]),
            'https://files.withpersona.com/center.jpg' => Http::response('center', 200, ['Content-Type' => 'image/jpeg', 'Content-Length' => '6']),
        ]);
        $this->actingAs($this->admin());
        $this->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie']))->assertOk()->assertContent('center');
        foreach (['selfie-left', 'selfie-right'] as $artifact) {
            $this->get(route('admin.parent-verifications.persona.evidence', [$attempt, $artifact]))->assertNotFound();
        }
    }

    public function test_side_pose_media_still_rejects_untrusted_hosts_and_superseded_attempts(): void
    {
        $attempt = $this->guardianAttempt(['selfie_verification_id' => 'selfie_untrusted']);
        Http::preventStrayRequests();
        Http::fake([
            'https://api.withpersona.com/api/v1/verification/selfies/selfie_untrusted' => Http::response([
                'data' => ['attributes' => ['left-photo-url' => 'https://evil.example/left.jpg']],
            ]),
        ]);
        $this->actingAs($this->admin())
            ->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie-left']))->assertStatus(503);
        Http::assertNotSent(fn ($request) => str_starts_with($request->url(), 'https://evil.example/'));

        $attempt->update(['superseded_at' => now()]);
        Http::fake();
        $this->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie-right']))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_retained_school_selfie_can_be_viewed_in_a_later_round_but_stays_owned_and_school_only(): void
    {
        $learner = User::factory()->create(['role' => 'learner', 'is_parent_registration' => false]);
        $case = LearnerIdentityVerification::create([
            'user_id' => $learner->id, 'pathway' => 'teen', 'document_type' => 'school_id',
            'status' => 'pending', 'submission_round' => 2,
        ]);
        $attempt = $this->guardianAttempt([
            'user_id' => $learner->id, 'learner_identity_verification_id' => $case->id,
            'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL, 'local_document_type' => 'school_id',
            'local_government_id_type' => null, 'government_id_verification_id' => null,
            'template_key' => PersonaInquiry::TEMPLATE_SELFIE,
            'country_policy_status' => PersonaInquiry::POLICY_NOT_APPLICABLE,
            'document_policy_status' => PersonaInquiry::POLICY_NOT_APPLICABLE,
            'selfie_verification_id' => 'selfie_retained',
        ]);
        $case->forceFill(['persona_evidence_attempt_id' => $attempt->id, 'evidence_source' => 'persona_selfie'])->save();
        Http::preventStrayRequests();
        Http::fake([
            'https://api.withpersona.com/api/v1/verification/selfies/selfie_retained' => Http::response([
                'data' => ['attributes' => ['left-photo-url' => 'https://files.withpersona.com/retained.jpg']],
            ]),
            'https://files.withpersona.com/retained.jpg' => Http::response('retained', 200, ['Content-Type' => 'image/jpeg', 'Content-Length' => '8']),
        ]);
        $route = route('admin.parent-verifications.persona.evidence', [$attempt, 'selfie-left']);
        $this->actingAs($this->admin())->get($route)->assertOk()->assertContent('retained');

        foreach ([
            ['flow_type' => PersonaInquiry::FLOW_ADULT_GOVERNMENT],
            ['user_id' => User::factory()->create(['role' => 'learner'])->id],
            ['superseded_at' => now()],
        ] as $change) {
            $original = $attempt->only(array_keys($change));
            $attempt->update($change);
            $this->get($route)->assertNotFound();
            $attempt->update($original);
        }
        $case->forceFill(['status' => 'rejected', 'resubmission_allowed' => true, 'requested_corrections' => ['selfie']])->save();
        $this->get($route)->assertNotFound();
        $case->forceFill(['requested_corrections' => ['identity_front']])->save();
        $this->get($route)->assertOk()->assertContent('retained');
        $case->forceFill(['persona_evidence_attempt_id' => null])->save();
        $this->get($route)->assertNotFound();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function guardianAttempt(array $overrides = []): PersonaInquiry
    {
        $guardian = User::factory()->create([
            'role' => 'learner',
            'is_parent_registration' => true,
            'email_verified_at' => now(),
            'parent_verification_status' => 'pending',
        ]);
        $guardian->assignRole('learner');

        return PersonaInquiry::create(array_merge([
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
            'persona_inquiry_id' => 'inq_guardian_'.str()->random(10),
            'persona_status' => 'completed',
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_code' => 'PH',
            'id_class' => 'pp',
            'country_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'document_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'is_sandbox' => true,
        ], $overrides));
    }
}
