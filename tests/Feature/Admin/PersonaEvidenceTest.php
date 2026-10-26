<?php

namespace Tests\Feature\Admin;

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

        $this->get($route)->assertRedirect(route('login'));
        $this->actingAs($attempt->user)->get($route)->assertForbidden();

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
