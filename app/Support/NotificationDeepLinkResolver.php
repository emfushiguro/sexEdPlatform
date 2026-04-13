<?php

namespace App\Support;

class NotificationDeepLinkResolver
{
    public function __construct(
        private readonly NotificationPayloadNormalizer $payloadNormalizer,
    ) {
    }

    public function resolve(array $payload, string $fallbackUrl): string
    {
        $candidate = $this->payloadNormalizer->resolveActionUrl($payload);

        if ($candidate === null) {
            return $fallbackUrl;
        }

        return $this->isSafeInternalTarget($candidate) ? $candidate : $fallbackUrl;
    }

    private function isSafeInternalTarget(string $target): bool
    {
        if (str_starts_with($target, '/')) {
            return true;
        }

        if (!filter_var($target, FILTER_VALIDATE_URL)) {
            return false;
        }

        $targetHost = parse_url($target, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (!is_string($targetHost) || !is_string($appHost)) {
            return false;
        }

        return strcasecmp($targetHost, $appHost) === 0;
    }
}
