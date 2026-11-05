<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Admin\RoleSyncService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InstructorPersonaIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'instructor_application.enabled' => true,
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-only',
            'persona.templates.govid_selfie' => 'itmpl_instructor_govid_selfie',
        ]);
        Notification::fake();
        Http::preventStrayRequests();
    }

    public function test_instructor_start_uses_server_selected_template_and_reuses_the_current_attempt(): void
    {
        $user = $this->applicant();
        $createCalls = 0;
        Http::fake(function (HttpRequest $request) use (&$createCalls) {
            if ($request->method() === 'POST' && str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/inquiries')) {
                $createCalls++;

                return Http::response(['data' => [
                    'id' => 'inq_instructor_1',
                    'attributes' => ['status' => 'created'],
                ]]);
            }

            if (str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/resume')) {
                return Http::response(['meta' => ['session-token' => 'session-instructor']]);
            }

            return Http::response([], 404);
        });

        $this->actingAs($user)->post(route('instructor-application.identity.persona.start'), [
            'government_id_type' => 'national_id',
            'template_id' => 'attacker-template',
            'flow_type' => 'adult_government',
            'status' => 'approved',
        ])->assertRedirect();

        $attempt = PersonaInquiry::query()->firstOrFail();
        $case = InstructorIdentityVerification::query()->firstOrFail();
        $this->assertSame(PersonaInquiry::FLOW_INSTRUCTOR_GOVERNMENT, $attempt->flow_type);
        $this->assertSame($case->id, $attempt->instructor_identity_verification_id);
        $this->assertNull($attempt->learner_identity_verification_id);
        $this->assertSame('itmpl_instructor_govid_selfie', $attempt->template_id);
        $this->assertSame('national_id', $attempt->local_government_id_type);
        $this->assertSame('id', config('persona.government_id_classes.national_id'));
        $this->assertSame(InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS, $case->status);

        $idempotencyKey = $attempt->idempotency_key;
        $referenceId = $attempt->reference_id;

        $this->actingAs($user)->post(route('instructor-application.identity.persona.start'), [
            'government_id_type' => 'national_id',
        ])->assertRedirect();

        $this->assertSame(1, $createCalls);
        $this->assertSame($idempotencyKey, $attempt->fresh()->idempotency_key);
        $this->assertSame($referenceId, $attempt->fresh()->reference_id);
        $this->assertDatabaseCount('persona_inquiries', 1);
        Http::assertSent(fn (HttpRequest $request): bool => $request->method() === 'POST'
            && str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/inquiries')
            && data_get($request->data(), 'data.attributes.inquiry-template-id') === 'itmpl_instructor_govid_selfie'
            && data_get($request->data(), 'data.attributes.reference-id') === $referenceId);
    }

    public function test_forged_return_id_cannot_unlock_the_case_and_authoritative_completion_only_enters_review(): void
    {
        $user = $this->applicant();
        $attempt = $this->startAttempt($user);

        $this->actingAs($user)->get(route('instructor-application.identity.persona.return', [
            'inquiry-id' => 'inq_forged',
        ]))->assertNotFound();
        $this->assertSame(
            InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS,
            $attempt->instructorIdentityVerification->fresh()->status,
        );

        $this->fakeCompletedInquiry();
        $this->actingAs($user)->get(route('instructor-application.identity.persona.return', [
            'inquiry-id' => $attempt->persona_inquiry_id,
        ]))->assertRedirect(route('instructor-application.dashboard'));

        $case = $attempt->instructorIdentityVerification->fresh();
        $this->assertSame('ver_gov', $attempt->fresh()->government_id_verification_id);
        $this->assertSame('ver_selfie', $attempt->fresh()->selfie_verification_id);
        $this->assertSame('PH', $attempt->fresh()->country_code);
        $this->assertSame('id', $attempt->fresh()->id_class);
        $this->assertSame(PersonaInquiry::OUTCOME_ELIGIBLE, $attempt->fresh()->verification_outcome);
        $this->assertSame(InstructorIdentityVerification::STATUS_PENDING_REVIEW, $case->status);
        $this->assertNotSame(InstructorIdentityVerification::STATUS_APPROVED, $case->status);
        $summary = $case->comparison_summary;
        $this->assertArrayNotHasKey('document-number', $summary);
        $this->assertArrayNotHasKey('name-first', $summary);
        $this->assertArrayNotHasKey('raw', $summary);
        $this->assertSame('v1', $summary['normalization_version']);
    }

    public function test_failed_or_retryable_provider_results_require_action_without_a_fraud_label(): void
    {
        $user = $this->applicant();
        $attempt = $this->startAttempt($user);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake([
            '*/inquiries/*' => Http::response(['data' => [
                'attributes' => ['status' => 'failed'],
                'relationships' => ['verifications' => ['data' => []]],
            ]]),
        ]);

        $this->actingAs($user)->post(route('instructor-application.identity.persona.refresh'))
            ->assertRedirect(route('instructor-application.dashboard'));

        $case = $attempt->instructorIdentityVerification->fresh();
        $this->assertSame(InstructorIdentityVerification::STATUS_ACTION_REQUIRED, $case->status);
        $this->assertNotSame('fraud', $case->reason_code);
        $this->assertStringNotContainsString('fraud', (string) $case->reason_note);
    }

    public function test_requested_correction_is_included_in_the_replacement_persona_attempt(): void
    {
        $user = $this->applicant();
        $user->update(['middle_initial' => 'WRONG']);
        $attempt = $this->startAttempt($user);
        $case = $attempt->instructorIdentityVerification;
        $case->update([
            'status' => InstructorIdentityVerification::STATUS_ACTION_REQUIRED,
            'resubmission_allowed' => true,
            'requested_action_codes' => ['clarify_personal_information'],
        ]);
        $this->actingAs($user)->get(route('instructor-application.dashboard'))
            ->assertOk()->assertSee('Correct personal information');

        $user->update(['middle_initial' => null]);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(fn (HttpRequest $request) => str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/inquiries')
            ? Http::response(['data' => ['id' => 'inq_replacement', 'attributes' => ['status' => 'created']]])
            : Http::response(['meta' => ['session-token' => 'session-replacement']]));

        $this->post(route('instructor-application.identity.persona.replace'), ['government_id_type' => 'national_id'])
            ->assertRedirect();

        $replacement = $user->instructorIdentityVerifications()->current()->firstOrFail();
        $this->assertSame(2, $replacement->submission_round);
        $this->assertNull($replacement->declared_snapshot['middle_initial']);
        $this->assertSame('WRONG', $case->fresh()->declared_snapshot['middle_initial']);
        $this->assertNotNull($case->fresh()->superseded_at);
        $this->assertSame('inq_replacement', $replacement->personaInquiries()->firstOrFail()->persona_inquiry_id);
        $this->assertSame(InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS, $replacement->status);
    }

    private function applicant(): User
    {
        $user = User::factory()->create([
            'role' => User::ACCOUNT_TYPE_APPLICANT,
            'account_type' => User::ACCOUNT_TYPE_APPLICANT,
            'birthdate' => now()->subYears(24)->format('Y-m-d'),
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, User::ACCOUNT_TYPE_APPLICANT);

        return $user->fresh();
    }

    private function startAttempt(User $user): PersonaInquiry
    {
        Http::fake(function (HttpRequest $request) {
            if (str_ends_with((string) parse_url($request->url(), PHP_URL_PATH), '/inquiries')) {
                return Http::response(['data' => [
                    'id' => 'inq_instructor_1',
                    'attributes' => ['status' => 'created'],
                ]]);
            }

            return Http::response(['meta' => ['session-token' => 'session-instructor']]);
        });

        $this->actingAs($user)->post(route('instructor-application.identity.persona.start'), [
            'government_id_type' => 'national_id',
        ])->assertRedirect();

        return PersonaInquiry::query()->firstOrFail();
    }

    private function fakeCompletedInquiry(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake([
            '*/inquiries/*' => Http::response(['data' => [
                'type' => 'inquiry',
                'id' => 'inq_instructor_1',
                'attributes' => ['status' => 'completed'],
                'relationships' => ['verifications' => ['data' => [
                    ['type' => 'verification/government-id', 'id' => 'ver_gov'],
                    ['type' => 'verification/selfie', 'id' => 'ver_selfie'],
                ]]],
            ], 'included' => []]),
            '*/verification/government-ids/*' => Http::response(['data' => [
                'type' => 'verification/government-id', 'id' => 'ver_gov',
                'attributes' => [
                    'status' => 'passed', 'country-code' => 'PH', 'id-class' => 'id',
                    'name-first' => 'Test', 'name-middle' => 'A', 'name-last' => 'Applicant',
                    'birthdate' => '2002-01-01', 'address-city' => 'Cavite City',
                    'address-subdivision' => 'Barangay Test', 'document-number' => 'never-store',
                ],
            ]]),
            '*/verification/selfies/*' => Http::response(['data' => [
                'type' => 'verification/selfie', 'id' => 'ver_selfie',
                'attributes' => ['status' => 'passed'],
            ]]),
        ]);
    }
}
