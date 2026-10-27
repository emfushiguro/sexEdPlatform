<?php

namespace Tests\Feature\Api;

use App\Jobs\Identity\SyncPersonaInquiry;
use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\PersonaWebhookEvent;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use App\Notifications\PersonaActionRequiredNotification;
use App\Notifications\PersonaReadyForLocalEvidenceNotification;
use App\Notifications\LearnerIdentitySubmittedNotification;
use Tests\TestCase;

class PersonaWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-persona-api-key',
            'persona.webhook_secret' => 'test-persona-webhook-secret',
            'persona.webhook_secrets' => ['test-persona-webhook-secret'],
            'persona.webhook_tolerance_seconds' => 300,
            'persona.templates.govid_selfie' => 'tmpl_govid_test',
            'persona.government_id_classes' => ['passport' => 'pp'],
        ]);
    }

    public function test_valid_signed_event_is_persisted_and_queued_once(): void
    {
        $attempt = $this->attempt('inq_webhook_1');
        $payload = $this->payload('evt_webhook_1', $attempt->persona_inquiry_id, 'failed');
        $raw = $this->raw($payload);
        Queue::fake();

        $response = $this->sendSigned($raw);
        $response->assertOk()->assertJson(['accepted' => true]);

        $this->assertDatabaseHas('persona_webhook_events', [
            'event_id' => 'evt_webhook_1',
            'event_type' => 'inquiry.completed',
            'persona_inquiry_id' => $attempt->persona_inquiry_id,
            'processing_status' => PersonaWebhookEvent::STATUS_RECEIVED,
        ]);
        Queue::assertPushed(SyncPersonaInquiry::class, 1);

        $this->sendSigned($raw)->assertOk()->assertJson(['duplicate' => true]);
        $this->assertDatabaseCount('persona_webhook_events', 1);
        Queue::assertPushed(SyncPersonaInquiry::class, 1);
    }

    public function test_rotating_signature_sets_accept_any_matching_secret_and_malformed_sets_fail(): void
    {
        $payload = $this->payload('evt_signature', 'inq_unknown', 'completed');
        $raw = $this->raw($payload);
        $timestamp = now()->timestamp;
        $old = hash_hmac('sha256', $timestamp.'.'.$raw, 'old-secret');
        $current = hash_hmac('sha256', $timestamp.'.'.$raw, 'test-persona-webhook-secret');
        config(['persona.webhook_secrets' => ['old-secret', 'test-persona-webhook-secret']]);

        $this->send($raw, "t={$timestamp},v1={$old} t={$timestamp},v1={$current}")
            ->assertOk();

        $this->send($raw, "t={$timestamp},v1={$current},bad=field")
            ->assertUnauthorized();
        $this->send($raw, "t={$timestamp},v1=not-hex")
            ->assertUnauthorized();
    }

    public function test_missing_altered_wrong_stale_and_future_signatures_are_rejected_without_logging_payload(): void
    {
        $payload = $this->payload('evt_rejected', 'inq_unknown', 'completed');
        $raw = $this->raw($payload);
        $timestamp = now()->timestamp;
        $valid = hash_hmac('sha256', $timestamp.'.'.$raw, 'test-persona-webhook-secret');

        $this->call('POST', route('api.webhooks.persona'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $raw)->assertUnauthorized();

        $this->send($raw.' ', "t={$timestamp},v1={$valid}")->assertUnauthorized();
        $this->send($raw, "t={$timestamp},v1=".hash_hmac('sha256', $timestamp.'.'.$raw, 'wrong-secret'))->assertUnauthorized();
        $this->send($raw, 't=' . ($timestamp - 301) . ',v1=' . $valid)->assertUnauthorized();
        $this->send($raw, 't=' . ($timestamp + 301) . ',v1=' . $valid)->assertUnauthorized();
        $this->send($raw, 't=abc,v1=' . $valid)->assertUnauthorized();
    }

    public function test_unknown_inquiry_is_ignored_and_reference_id_cannot_reassign_an_event(): void
    {
        $attempt = $this->attempt('inq_owned_by_another_event');
        $payload = $this->payload('evt_unknown', 'inq_not_owned', 'completed', $attempt->reference_id);
        Queue::fake();

        $this->sendSigned($this->raw($payload))->assertOk();

        $this->assertDatabaseHas('persona_webhook_events', [
            'event_id' => 'evt_unknown',
            'persona_inquiry_id' => 'inq_not_owned',
            'processing_status' => PersonaWebhookEvent::STATUS_IGNORED,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_synchronizer_refetches_authoritative_resources_and_applies_policy(): void
    {
        $attempt = $this->attempt('inq_sync_1');
        Http::fake(function (HttpRequest $request) {
            return match (true) {
                str_contains($request->url(), '/inquiries/inq_sync_1') => Http::response($this->inquiryResponse('completed'), 200),
                str_ends_with($request->url(), '/verification/government-ids/ver_gov_1') => Http::response($this->governmentResponse(), 200),
                str_ends_with($request->url(), '/verification/selfies/ver_selfie_1') => Http::response($this->selfieResponse(), 200),
                default => Http::response([], 404),
            };
        });

        $synced = app(\App\Services\Persona\PersonaInquirySynchronizer::class)->sync(
            $attempt,
            CarbonImmutable::parse('2026-10-26T10:00:00Z'),
        );

        $this->assertSame(PersonaInquiry::OUTCOME_ELIGIBLE, $synced->verification_outcome);
        $this->assertSame('ver_gov_1', $synced->government_id_verification_id);
        $this->assertSame('ver_selfie_1', $synced->selfie_verification_id);
        $this->assertSame('PH', $synced->country_code);
        $this->assertSame('pp', $synced->id_class);
        $this->assertSame(PersonaInquiry::POLICY_ELIGIBLE, $synced->country_policy_status);
        $this->assertSame(PersonaInquiry::POLICY_ELIGIBLE, $synced->document_policy_status);
        Http::assertSentCount(3);
    }

    public function test_older_event_cannot_regress_current_attempt(): void
    {
        $attempt = $this->attempt('inq_sync_old', [
            'persona_status' => 'completed',
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_code' => 'PH',
            'id_class' => 'pp',
            'country_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'document_policy_status' => PersonaInquiry::POLICY_ELIGIBLE,
            'persona_event_at' => CarbonImmutable::parse('2026-10-26T12:00:00Z'),
        ]);
        Http::fake(function (HttpRequest $request) {
            return match (true) {
                str_contains($request->url(), '/inquiries/inq_sync_old') => Http::response($this->inquiryResponse('failed', '2026-10-26T11:00:00Z'), 200),
                str_contains($request->url(), '/verification/government-ids/') => Http::response($this->governmentResponse(), 200),
                str_contains($request->url(), '/verification/selfies/') => Http::response($this->selfieResponse(), 200),
                default => Http::response([], 404),
            };
        });

        $synced = app(\App\Services\Persona\PersonaInquirySynchronizer::class)->sync(
            $attempt,
            CarbonImmutable::parse('2026-10-26T11:00:00Z'),
        );

        $this->assertSame(PersonaInquiry::OUTCOME_ELIGIBLE, $synced->verification_outcome);
        $this->assertSame('completed', $synced->persona_status);
    }

    public function test_transition_notifications_are_sent_once_without_provider_details(): void
    {
        Notification::fake();
        $attempt = $this->attempt('inq_notification_ready');
        Http::fake(function (HttpRequest $request) {
            return match (true) {
                str_contains($request->url(), '/inquiries/inq_notification_ready') => Http::response($this->inquiryResponse('completed'), 200),
                str_ends_with($request->url(), '/verification/government-ids/ver_gov_1') => Http::response($this->governmentResponse(), 200),
                str_ends_with($request->url(), '/verification/selfies/ver_selfie_1') => Http::response($this->selfieResponse(), 200),
                default => Http::response([], 404),
            };
        });

        $synchronizer = app(\App\Services\Persona\PersonaInquirySynchronizer::class);
        $synchronizer->sync($attempt);
        $synchronizer->sync($attempt);

        Notification::assertSentToTimes($attempt->user, LearnerIdentitySubmittedNotification::class, 1);
        Notification::assertNotSentTo($attempt->user, PersonaReadyForLocalEvidenceNotification::class);
        Notification::assertNotSentTo($attempt->user, PersonaActionRequiredNotification::class);
        $notification = Notification::sent($attempt->user, LearnerIdentitySubmittedNotification::class)->first();
        $serialized = json_encode($notification->toArray($attempt->user), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($attempt->persona_inquiry_id, $serialized);
        $this->assertStringNotContainsString('ver_gov_1', $serialized);
        $this->assertStringNotContainsString('ver_selfie_1', $serialized);
    }

    public function test_retryable_transition_sends_one_generic_action_notification(): void
    {
        Notification::fake();
        $attempt = $this->attempt('inq_notification_action');
        Http::fake([
            'https://api.withpersona.com/api/v1/inquiries/inq_notification_action*' => Http::response([
                'data' => ['attributes' => ['status' => 'failed']],
            ], 200),
        ]);

        $synchronizer = app(\App\Services\Persona\PersonaInquirySynchronizer::class);
        $synchronizer->sync($attempt);
        $synchronizer->sync($attempt);

        Notification::assertSentToTimes($attempt->user, PersonaActionRequiredNotification::class, 1);
        $notification = Notification::sent($attempt->user, PersonaActionRequiredNotification::class)->first();
        $serialized = json_encode($notification->toArray($attempt->user), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($attempt->persona_inquiry_id, $serialized);
        $this->assertStringNotContainsString('provider', strtolower($serialized));
    }

    public function test_sync_job_marks_retryable_provider_failure_without_serializing_payload(): void
    {
        $attempt = $this->attempt('inq_sync_failure');
        $event = PersonaWebhookEvent::create([
            'event_id' => 'evt_sync_failure',
            'event_type' => 'inquiry.completed',
            'persona_inquiry_id' => $attempt->persona_inquiry_id,
            'event_created_at' => now(),
        ]);
        Http::fake([
            'https://api.withpersona.com/api/v1/inquiries/*' => Http::response([], 503),
        ]);

        $this->expectException(\RuntimeException::class);
        try {
            (new SyncPersonaInquiry($event->id))->handle(app(\App\Services\Persona\PersonaInquirySynchronizer::class));
        } finally {
            $event->refresh();
            $this->assertSame(PersonaWebhookEvent::STATUS_FAILED, $event->processing_status);
            $this->assertSame('provider_unavailable', $event->failure_category);
        }
    }

    private function attempt(string $personaId, array $overrides = []): PersonaInquiry
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'birthdate' => '2000-01-01',
            'email_verified_at' => now(),
        ]);
        $user->assignRole('learner');
        $case = app(LearnerIdentityRequirement::class)->createForNewLearner($user);

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
            'persona_inquiry_id' => $personaId,
            'verification_outcome' => PersonaInquiry::OUTCOME_NOT_READY,
            'is_sandbox' => true,
        ], $overrides));
    }

    private function payload(string $eventId, string $inquiryId, string $status, ?string $referenceId = null): array
    {
        return [
            'data' => [
                'type' => 'event',
                'id' => $eventId,
                'attributes' => [
                    'name' => 'inquiry.completed',
                    'created-at' => '2026-10-26T10:00:00Z',
                    'payload' => [
                        'data' => [
                            'type' => 'inquiry',
                            'id' => $inquiryId,
                            'attributes' => [
                                'status' => $status,
                                'reference-id' => $referenceId,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function inquiryResponse(string $status, string $updatedAt = '2026-10-26T10:00:00Z'): array
    {
        return [
            'data' => [
                'type' => 'inquiry',
                'id' => 'inq_sync_1',
                'attributes' => [
                    'status' => $status,
                    'updated-at' => $updatedAt,
                    'completed-at' => $status === 'completed' ? '2026-10-26T10:00:00Z' : null,
                    'expired-at' => $status === 'expired' ? '2026-10-26T10:00:00Z' : null,
                ],
                'relationships' => [
                    'verifications' => [
                        'data' => [
                            ['type' => 'verification/government-id', 'id' => 'ver_gov_1'],
                            ['type' => 'verification/selfie', 'id' => 'ver_selfie_1'],
                        ],
                    ],
                ],
            ],
            'included' => [],
        ];
    }

    private function governmentResponse(): array
    {
        return [
            'data' => [
                'type' => 'verification/government-id',
                'id' => 'ver_gov_1',
                'attributes' => [
                    'status' => 'passed',
                    'country-code' => 'PH',
                    'id-class' => 'pp',
                    'completed-at' => '2026-10-26T10:00:00Z',
                ],
            ],
        ];
    }

    private function selfieResponse(): array
    {
        return [
            'data' => [
                'type' => 'verification/selfie',
                'id' => 'ver_selfie_1',
                'attributes' => [
                    'status' => 'passed',
                    'completed-at' => '2026-10-26T10:00:00Z',
                ],
            ],
        ];
    }

    private function raw(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function sendSigned(string $raw)
    {
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$raw, 'test-persona-webhook-secret');

        return $this->send($raw, "t={$timestamp},v1={$signature}");
    }

    private function send(string $raw, string $signature)
    {
        return $this->call('POST', route('api.webhooks.persona'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PERSONA_SIGNATURE' => $signature,
        ], $raw);
    }
}
