<?php

namespace App\Services\Persona;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PersonaClient
{
    public function createInquiry(
        string $templateId,
        string $idempotencyKey,
        ?string $referenceId = null,
    ): array {
        $attributes = ['inquiry-template-id' => $templateId];
        if ($referenceId !== null) {
            $attributes['reference-id'] = $referenceId;
        }

        $response = $this->requestWithRetry(
            fn (PendingRequest $request): Response => $request->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
            ])->post($this->url('/inquiries'), [
                'data' => ['attributes' => $attributes],
            ]),
        );

        return [
            'id' => data_get($response, 'data.id'),
            'status' => data_get($response, 'data.attributes.status'),
            'session_token' => data_get($response, 'meta.session-token'),
        ];
    }

    public function resumeInquiry(string $inquiryId, string $idempotencyKey): array
    {
        $response = $this->requestWithRetry(
            fn (PendingRequest $request): Response => $request->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
            ])->post($this->url('/inquiries/'.rawurlencode($inquiryId).'/resume')),
        );

        return [
            'session_token' => data_get($response, 'meta.session-token'),
        ];
    }

    public function retrieveInquiry(string $inquiryId): array
    {
        return $this->requestWithRetry(
            fn (PendingRequest $request): Response => $request->get(
                $this->url('/inquiries/'.rawurlencode($inquiryId)),
                ['include' => 'verifications'],
            ),
        );
    }

    public function retrieveGovernmentId(string $verificationId): array
    {
        return $this->requestWithRetry(
            fn (PendingRequest $request): Response => $request->get(
                $this->url('/verification/government-ids/'.rawurlencode($verificationId)),
            ),
        );
    }

    public function retrieveSelfie(string $verificationId): array
    {
        return $this->requestWithRetry(
            fn (PendingRequest $request): Response => $request->get(
                $this->url('/verification/selfies/'.rawurlencode($verificationId)),
            ),
        );
    }

    /** @return array{content_type: string, bytes: string} */
    public function downloadMedia(string $url): array
    {
        if (! $this->isAllowedMediaUrl($url)) {
            throw new RuntimeException('Persona media is unavailable.', 503);
        }

        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->connectTimeout((int) config('persona.connect_timeout_seconds', 5))
                ->timeout((int) config('persona.timeout_seconds', 15))
                ->get($url);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Persona media is unavailable.', 503, $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Persona media is unavailable.', 503);
        }

        $contentLength = $response->header('Content-Length');
        $maxBytes = (int) config('persona.media_max_bytes', 10 * 1024 * 1024);
        if ($contentLength === null || ! ctype_digit((string) $contentLength) || (int) $contentLength > $maxBytes) {
            throw new RuntimeException('Persona media is unavailable.', 503);
        }

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        if (! in_array($contentType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Persona media is unavailable.', 503);
        }

        $bytes = $response->body();
        if (strlen($bytes) > $maxBytes || strlen($bytes) !== (int) $contentLength) {
            throw new RuntimeException('Persona media is unavailable.', 503);
        }

        return ['content_type' => $contentType, 'bytes' => $bytes];
    }

    private function requestWithRetry(callable $callback): array
    {
        $this->assertConfigured();
        $attempts = 0;

        while (true) {
            try {
                $response = $callback($this->authenticatedRequest());
            } catch (ConnectionException $exception) {
                if ($attempts++ < 2) {
                    continue;
                }

                throw new RuntimeException('Persona service is temporarily unavailable.', 503, $exception);
            }

            if ($response->successful()) {
                return $response->json();
            }

            $status = $response->status();
            if (in_array($status, [429, 500, 502, 503, 504], true) && $attempts++ < 2) {
                continue;
            }

            if (in_array($status, [401, 403], true)) {
                throw new RuntimeException('Persona authentication failed.', $status);
            }

            if ($status === 429 || $status >= 500) {
                throw new RuntimeException('Persona service is temporarily unavailable.', 503);
            }

            throw new RuntimeException('Persona request was rejected.', 502);
        }
    }

    private function authenticatedRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken((string) config('persona.api_key'))
            ->withHeaders([
                'Persona-Version' => (string) config('persona.api_version', '2026-09-29'),
                'Key-Inflection' => 'kebab',
            ])
            ->connectTimeout((int) config('persona.connect_timeout_seconds', 5))
            ->timeout((int) config('persona.timeout_seconds', 15));
    }

    private function assertConfigured(): void
    {
        if (! config('persona.enabled', false)
            || config('persona.environment', 'sandbox') !== 'sandbox'
            || blank(config('persona.api_key'))) {
            throw new RuntimeException('Persona integration is not available.', 503);
        }
    }

    private function url(string $path): string
    {
        return rtrim((string) config('persona.api_base_url'), '/').$path;
    }

    private function isAllowedMediaUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || isset($parts['user'], $parts['pass'], $parts['fragment'])
            || isset($parts['port']) && (int) $parts['port'] !== 443) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $allowedHosts = array_map('strtolower', (array) config('persona.media_hosts', []));

        return $host !== '' && in_array($host, $allowedHosts, true);
    }
}
