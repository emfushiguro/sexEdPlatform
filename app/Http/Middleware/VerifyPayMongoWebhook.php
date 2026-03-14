<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * VerifyPayMongoWebhook
 *
 * PayMongo signs every webhook using the following algorithm:
 *
 *   Header:  Paymongo-Signature: t=<unix_timestamp>,te=<test_sig>,li=<live_sig>
 *   Message: "<timestamp>.<raw_request_body>"
 *   Algorithm: HMAC-SHA256 keyed with PAYMONGO_WEBHOOK_SECRET
 *
 * This middleware parses the header, reconstructs the signed message,
 * and rejects any request whose computed signature does not match.
 *
 * Middleware alias: 'paymongo.webhook' (registered in bootstrap/app.php)
 * Applied to: Route::post('/webhook/paymongo', ...)
 *
 * Reference: https://developers.paymongo.com/docs/webhook-signature-verification
 */
class VerifyPayMongoWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $webhookSecret = config('paymongo.webhook_secret');

        // Reject all requests when the secret is not configured.
        // A missing secret is a misconfiguration — never open the door to unsigned webhooks.
        if (empty($webhookSecret)) {
            Log::error('PayMongo webhook rejected: PAYMONGO_WEBHOOK_SECRET is not set in .env');
            return response('Webhook not configured', 500);
        }

        $signatureHeader = $request->header('Paymongo-Signature');

        if (empty($signatureHeader)) {
            Log::warning('PayMongo webhook rejected: missing Paymongo-Signature header');
            return response('Unauthorized', 401);
        }

        // Parse header: "t=1634567890,te=abc...,li=def..."
        $timestamp  = null;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', $segment, 2), 2, '');
            $key   = trim($key);
            $value = trim($value);
            if ($key === 't') {
                $timestamp = $value;
            } elseif (in_array($key, ['te', 'li'], true)) {
                $signatures[] = $value;
            }
        }

        if (empty($timestamp) || empty($signatures)) {
            Log::warning('PayMongo webhook rejected: could not parse signature header', [
                'header' => $signatureHeader,
            ]);
            return response('Unauthorized', 401);
        }

        // Reconstruct the signed message: "<unix_timestamp>.<raw_request_body>"
        $rawPayload    = $request->getContent();
        $signedMessage = "{$timestamp}.{$rawPayload}";
        $expected      = hash_hmac('sha256', $signedMessage, $webhookSecret);

        // The header may carry both a test ('te') and live ('li') signature.
        $verified = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                $verified = true;
                break;
            }
        }

        if (!$verified) {
            Log::warning('PayMongo webhook rejected: signature mismatch', [
                'timestamp'      => $timestamp,
                'payload_length' => strlen($rawPayload),
            ]);
            return response('Unauthorized', 401);
        }

        return $next($request);
    }
}