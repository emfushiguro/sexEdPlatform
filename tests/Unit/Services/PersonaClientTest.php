<?php

namespace Tests\Unit\Services;

use App\Services\Persona\PersonaClient;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PersonaClientTest extends TestCase
{
    private PersonaClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-persona-api-key',
            'persona.api_base_url' => 'https://api.withpersona.com/api/v1',
            'persona.api_version' => '2026-09-29',
            'persona.timeout_seconds' => 15,
            'persona.connect_timeout_seconds' => 5,
            'persona.media_hosts' => ['files.withpersona.com'],
            'persona.media_max_bytes' => 10,
        ]);

        $this->client = app(PersonaClient::class);
    }

    public function test_create_uses_the_pinned_authenticated_contract(): void
    {
        Http::fake([
            'https://api.withpersona.com/api/v1/inquiries' => Http::response([
                'data' => [
                    'id' => 'inq_123',
                    'attributes' => ['status' => 'created'],
                ],
                'meta' => ['session-token' => 'session_123'],
            ], 201),
        ]);

        $result = $this->client->createInquiry('itmpl_selfie', 'idempotency-123');

        $this->assertSame('inq_123', $result['id']);
        $this->assertSame('session_123', $result['session_token']);
        Http::assertSent(function ($request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.withpersona.com/api/v1/inquiries'
                && $request->header('Authorization') === ['Bearer test-persona-api-key']
                && $request->header('Persona-Version') === ['2026-09-29']
                && $request->header('Idempotency-Key') === ['idempotency-123']
                && data_get($request->data(), 'data.attributes.inquiry-template-id') === 'itmpl_selfie'
                && count(data_get($request->data(), 'data.attributes', [])) === 1;
        });
    }

    public function test_resume_retrieve_and_verification_calls_use_the_documented_paths(): void
    {
        Http::fake([
            'https://api.withpersona.com/api/v1/inquiries/inq_123/resume' => Http::response([
                'meta' => ['session-token' => 'session_456'],
            ]),
            'https://api.withpersona.com/api/v1/inquiries/inq_123*' => Http::response([
                'data' => ['id' => 'inq_123'],
            ]),
            'https://api.withpersona.com/api/v1/verification/government-ids/gov_123' => Http::response([
                'data' => ['id' => 'gov_123'],
            ]),
            'https://api.withpersona.com/api/v1/verification/selfies/selfie_123' => Http::response([
                'data' => ['id' => 'selfie_123'],
            ]),
        ]);

        $this->assertSame('session_456', $this->client->resumeInquiry('inq_123', 'resume-key')['session_token']);
        $this->client->retrieveInquiry('inq_123');
        $this->client->retrieveGovernmentId('gov_123');
        $this->client->retrieveSelfie('selfie_123');

        Http::assertSent(fn ($request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.withpersona.com/api/v1/inquiries/inq_123?include=verifications');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.withpersona.com/api/v1/verification/government-ids/gov_123');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.withpersona.com/api/v1/verification/selfies/selfie_123');
    }

    public function test_transient_failures_are_retried_but_auth_failures_are_not(): void
    {
        Http::fakeSequence('https://api.withpersona.com/api/v1/inquiries')
            ->pushStatus(503)
            ->push(['data' => ['id' => 'inq_retry']], 201);

        $this->assertSame('inq_retry', $this->client->createInquiry('itmpl_selfie', 'retry-key')['id']);
        Http::assertSentCount(2);
    }

    public function test_auth_failures_are_not_retried_or_leaked(): void
    {
        Http::fake([
            '*' => Http::response([
                'secret' => 'test-persona-api-key',
                'body' => 'provider response with session_123',
            ], 401),
        ]);

        try {
            $this->client->createInquiry('itmpl_selfie', 'auth-key');
            $this->fail('Expected Persona authentication failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Persona authentication failed.', $exception->getMessage());
            $this->assertStringNotContainsString('test-persona-api-key', $exception->getMessage());
            $this->assertStringNotContainsString('session_123', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_media_is_https_allowlisted_bounded_and_non_redirecting(): void
    {
        Http::fake([
            'https://files.withpersona.com/selfie.jpg' => Http::response(
                '123456',
                200,
                ['Content-Type' => 'image/jpeg', 'Content-Length' => '6'],
            ),
        ]);

        $result = $this->client->downloadMedia('https://files.withpersona.com/selfie.jpg');

        $this->assertSame(['content_type' => 'image/jpeg', 'bytes' => '123456'], $result);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://files.withpersona.com/selfie.jpg');
    }

    public function test_media_rejects_unsafe_urls_and_responses_without_leaking_them(): void
    {
        foreach ([
            'http://files.withpersona.com/selfie.jpg',
            'https://user:pass@files.withpersona.com/selfie.jpg',
            'https://evil.files.withpersona.com/selfie.jpg',
            'https://files.withpersona.com/selfie.jpg#fragment',
        ] as $url) {
            try {
                $this->client->downloadMedia($url);
                $this->fail('Expected unsafe media URL to be rejected.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Persona media is unavailable.', $exception->getMessage());
                $this->assertStringNotContainsString($url, $exception->getMessage());
            }
        }

        Http::fake([
            'https://files.withpersona.com/*' => Http::response('', 302, [
                'Location' => 'https://evil.example/image.jpg',
            ]),
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Persona media is unavailable.');
        $this->client->downloadMedia('https://files.withpersona.com/redirect.jpg');
    }

    public function test_media_rejects_missing_length_bad_type_and_oversized_body(): void
    {
        Http::fake([
            'https://files.withpersona.com/missing.jpg' => Http::response('123', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
            'https://files.withpersona.com/type.jpg' => Http::response('123', 200, [
                'Content-Type' => 'text/plain',
                'Content-Length' => '3',
            ]),
            'https://files.withpersona.com/large.jpg' => Http::response('12345678901', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '11',
            ]),
        ]);

        foreach (['missing.jpg', 'type.jpg', 'large.jpg'] as $file) {
            try {
                $this->client->downloadMedia('https://files.withpersona.com/'.$file);
                $this->fail('Expected invalid media response to be rejected.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Persona media is unavailable.', $exception->getMessage());
            }
        }
    }
}
