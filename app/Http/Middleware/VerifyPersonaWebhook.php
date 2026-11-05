<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPersonaWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secrets = array_values(array_filter((array) config('persona.webhook_secrets', [])));
        if ($secrets === [] && is_string(config('persona.webhook_secret')) && config('persona.webhook_secret') !== '') {
            $secrets = [(string) config('persona.webhook_secret')];
        }

        if ($secrets === []) {
            return response('Webhook not configured', 500);
        }

        $rawBody = $request->getContent();
        $header = (string) $request->header('Persona-Signature', '');
        $tolerance = (int) config('persona.webhook_tolerance_seconds', 300);
        $now = now()->timestamp;

        foreach (preg_split('/\s+/', trim($header), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $signatureSet) {
            $parts = explode(',', $signatureSet);
            if (count($parts) !== 2) {
                continue;
            }

            $values = [];
            $validSet = true;
            foreach ($parts as $part) {
                $pair = explode('=', trim($part), 2);
                if (count($pair) !== 2 || isset($values[$pair[0]])) {
                    $validSet = false;
                    break;
                }
                $values[$pair[0]] = $pair[1];
            }

            $timestamp = $values['t'] ?? null;
            $signature = $values['v1'] ?? null;
            if (! $validSet
                || count($values) !== 2
                || ! is_string($timestamp)
                || ! ctype_digit($timestamp)
                || ! is_string($signature)
                || ! preg_match('/\A[0-9a-fA-F]{64}\z/', $signature)
                || abs($now - (int) $timestamp) > $tolerance) {
                continue;
            }

            $signedMessage = $timestamp.'.'.$rawBody;
            foreach ($secrets as $secret) {
                if (hash_equals(hash_hmac('sha256', $signedMessage, (string) $secret), strtolower($signature))) {
                    return $next($request);
                }
            }
        }

        return response('Unauthorized', 401);
    }
}
