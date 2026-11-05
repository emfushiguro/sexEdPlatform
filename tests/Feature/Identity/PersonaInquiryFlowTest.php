<?php

namespace Tests\Feature\Identity;

use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PersonaInquiryFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-persona-api-key',
            'persona.templates.selfie' => 'tmpl_selfie_test',
            'persona.templates.govid_selfie' => 'tmpl_govid_test',
        ]);
    }

    public function test_eligible_learner_start_is_idempotent_and_redirects_to_hosted_flow(): void
    {
        [$user, $case] = $this->learner();
        $this->fakePersona();

        $first = $this->actingAs($user)->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
            'template_id' => 'attacker-controlled-template',
        ]);
        $second = $this->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
        ]);

        $first->assertRedirect();
        $second->assertRedirect();

        $location = (string) $first->headers->get('Location');
        $this->assertStringContainsString('inquiry-id=inq_test_1', $location);
        $this->assertStringContainsString('session-token=session_token_1', $location);
        $this->assertStringContainsString(rawurlencode(route('persona.identity.return')), $location);
        $this->assertStringNotContainsString('test-persona-api-key', $location);
        $this->assertStringNotContainsString('attacker-controlled-template', $location);

        $attempts = PersonaInquiry::query()->where('user_id', $user->id)->get();
        $this->assertCount(1, $attempts);
        $this->assertSame($case->id, $attempts->first()->learner_identity_verification_id);
        $this->assertSame('tmpl_govid_test', $attempts->first()->template_id);
        $this->assertSame(1, $attempts->first()->attempt_number);
        Http::assertSentCount(3);
        Http::assertSent(fn (HttpRequest $request): bool => str_ends_with($request->url(), '/inquiries'));
    }

    public function test_guardian_start_uses_owned_context_and_rejects_pending_or_approved_applications(): void
    {
        $guardian = $this->guardian();
        $this->fakePersona();

        $this->actingAs($guardian)->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
        ])->assertRedirect();

        $this->assertDatabaseHas('persona_inquiries', [
            'user_id' => $guardian->id,
            'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT,
            'target_submission_round' => 1,
        ]);

        $pending = $this->guardian();
        $pending->forceFill(['parent_verification_status' => 'pending'])->save();
        $this->actingAs($pending)->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
        ])->assertSessionHasErrors('persona');

        $approved = $this->guardian();
        $approved->forceFill(['parent_verification_status' => 'approved'])->save();
        $this->actingAs($approved)->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
        ])->assertSessionHasErrors('persona');
    }

    public function test_start_rejects_invalid_document_pair_and_does_not_accept_provider_controls(): void
    {
        [$user] = $this->learner('2010-01-01');
        $this->fakePersona();

        $this->actingAs($user)->post(route('persona.identity.start'), [
            'document_type' => 'school_id',
            'government_id_type' => 'passport',
            'template_id' => 'tmpl_govid_test',
        ])->assertSessionHasErrors('persona');

        $this->assertDatabaseCount('persona_inquiries', 0);
        Http::assertNothingSent();
    }

    public function test_return_ignores_untrusted_status_fields_and_requires_owned_current_attempt(): void
    {
        [$owner] = $this->learner();
        $this->fakePersona();
        $this->actingAs($owner)->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
        ])->assertRedirect();

        $this->get(route('persona.identity.return', [
            'inquiry-id' => 'inq_test_1',
            'status' => 'completed',
            'fields' => 'eligible',
            'reference-id' => 'attacker-reference',
        ]))->assertRedirect(route('learner.identity.status'))
            ->assertSessionHas('status', 'Persona returned to Conscious Connections. We are checking the result.');

        $other = $this->learner()[0];
        $this->actingAs($other)->get(route('persona.identity.return', [
            'inquiry-id' => 'inq_test_1',
            'status' => 'completed',
            'fields' => 'eligible',
        ]))->assertNotFound();
    }

    public function test_continue_and_refresh_require_a_current_owned_attempt(): void
    {
        [$owner] = $this->learner();
        $this->fakePersona();
        $this->actingAs($owner)->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
        ])->assertRedirect();

        $other = $this->learner()[0];
        $this->actingAs($other)->post(route('persona.identity.resume'))->assertNotFound();
        $this->post(route('persona.identity.refresh'))->assertNotFound();

        $this->actingAs($owner)->post(route('persona.identity.resume'))->assertRedirect();
        $this->post(route('persona.identity.refresh'))->assertRedirect(route('learner.identity.status'));
    }

    public function test_only_failed_or_expired_current_attempt_can_be_replaced_and_old_row_is_preserved(): void
    {
        [$user] = $this->learner();
        $this->fakePersona();
        $this->actingAs($user)->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
        ])->assertRedirect();

        $old = PersonaInquiry::query()->where('user_id', $user->id)->firstOrFail();
        $old->forceFill([
            'persona_status' => 'failed',
            'verification_outcome' => PersonaInquiry::OUTCOME_INELIGIBLE,
            'completed_at' => now(),
        ])->save();

        $this->actingAs($user)->post(route('persona.identity.replace'))->assertRedirect();

        $attempts = PersonaInquiry::query()->where('user_id', $user->id)->orderBy('attempt_number')->get();
        $this->assertCount(2, $attempts);
        $this->assertNotNull($attempts[0]->superseded_at);
        $this->assertNull($attempts[1]->superseded_at);
        $this->assertSame(2, $attempts[1]->attempt_number);
        $this->assertSame($attempts[0]->target_submission_round, $attempts[1]->target_submission_round);
        $this->assertNotSame($attempts[0]->idempotency_key, $attempts[1]->idempotency_key);
    }

    public function test_pending_learner_is_still_blocked_from_learning_area_but_persona_route_is_exempt(): void
    {
        [$user, $case] = $this->learner();
        $case->forceFill(['status' => 'pending'])->save();
        $this->fakePersona();

        $this->actingAs($user)->get(route('learner.dashboard'))
            ->assertRedirect(route('learner.identity.status'));
        $this->post(route('persona.identity.start'), [
            'document_type' => 'government_id',
            'government_id_type' => 'passport',
        ])->assertSessionHasErrors('persona');
    }

    private function learner(string $birthdate = '2000-01-01'): array
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'birthdate' => $birthdate,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('learner');

        return [$user, app(LearnerIdentityRequirement::class)->createForNewLearner($user)];
    }

    private function guardian(): User
    {
        $guardian = User::factory()->create([
            'role' => 'learner',
            'birthdate' => '1980-01-01',
            'is_parent_registration' => true,
            'email_verified_at' => now(),
            'parent_verification_status' => null,
        ]);
        $guardian->assignRole('learner');

        return $guardian;
    }

    private function fakePersona(): void
    {
        $createCount = 0;

        Http::fake(function (HttpRequest $request) use (&$createCount) {
            if (str_ends_with($request->url(), '/resume')) {
                return Http::response(['meta' => ['session-token' => 'session_token_'.$createCount]], 200);
            }

            $createCount++;

            return Http::response([
                'data' => [
                    'id' => 'inq_test_'.$createCount,
                    'type' => 'inquiry',
                    'attributes' => ['status' => 'created'],
                ],
            ], 201);
        });
    }
}
