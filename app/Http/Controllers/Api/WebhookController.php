<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\ModulePurchaseService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected ModulePurchaseService $modulePurchaseService,
    ) {}

    /**
     * Handle PayMongo webhook.
     *
     * Handles payment link payment events from PayMongo.
     * Captures the actual payment method (GCash, PayMaya, etc.)
     */
    public function paymongo(Request $request)
    {
        try {
            // 1. Verify PayMongo webhook signature (SECURITY)
            $signature = $request->header('Paymongo-Signature');
            $webhookSecret = config('paymongo.webhook_secret');

            if ($webhookSecret && !$this->verifyWebhookSignature($request->getContent(), $signature, $webhookSecret)) {
                Log::error('PayMongo Webhook: Invalid signature', [
                    'signature_provided' => $signature ? 'yes' : 'no',
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            Log::info('PayMongo Webhook Received', [
                'payload' => $request->all()
            ]);

            $event = $request->input('data');

            if (!$event) {
                Log::error('PayMongo Webhook: No event data');
                return response()->json(['error' => 'No event data'], 400);
            }

            // 2. Check for duplicate webhook processing (IDEMPOTENCY)
            $eventId = $event['id'] ?? null;
            if ($eventId) {
                $cacheKey = "webhook_processed_{$eventId}";
                if (Cache::has($cacheKey)) {
                    Log::info("Duplicate webhook ignored: {$eventId}");
                    return response()->json(['success' => true, 'already_processed' => true]);
                }
                // Mark as processed for 24 hours
                Cache::put($cacheKey, true, now()->addDay());
            }

            $eventType = $event['attributes']['type'] ?? null;

            // Handle payment.paid event (when payment link is paid)
            if ($eventType === 'link.payment.paid') {
                $this->handlePaymentPaid($event);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('PayMongo Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function handlePaymentPaid(array $event): void
    {
        $paymentData = $event['attributes']['data'] ?? [];
        $attributes = $paymentData['attributes'] ?? [];

        // Extract and normalize payment source type to an enum-safe value.
        $source = $attributes['source'] ?? [];
        $paymentMethod = $this->normalizePaymentMethod($source['type'] ?? null);

        // Get metadata for subscription lookup
        $metadata = $attributes['metadata'] ?? [];
        $paymentScope = $metadata['payment_scope'] ?? null;
        $paymentId = $metadata['payment_id'] ?? null;
        $subscriptionId = $metadata['subscription_id'] ?? null;
        $userId = $metadata['user_id'] ?? null;

        Log::info('PayMongo Payment Paid', [
            'payment_scope' => $paymentScope,
            'payment_id' => $paymentId,
            'subscription_id' => $subscriptionId,
            'user_id' => $userId,
            'payment_method' => $paymentMethod,
            'amount' => $attributes['amount'] ?? 0,
        ]);

        if ($paymentScope === 'module_purchase') {
            $payment = $paymentId
                ? Payment::query()->find($paymentId)
                : Payment::query()
                    ->where('user_id', $userId)
                    ->where('status', PaymentStatus::Pending)
                    ->where('payment_details->payment_scope', 'module_purchase')
                    ->latest('id')
                    ->first();

            if (!$payment) {
                return;
            }

            $this->modulePurchaseService->completePayment(
                payment: $payment,
                paymentMethod: $paymentMethod,
                paymongoPaymentId: (string) ($paymentData['id'] ?? ''),
            );

            return;
        }

        if (!$subscriptionId) {
            return;
        }

        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return;
        }

        // Find the most recent pending/paymongo payment for this subscription
        $payment = Payment::where('subscription_id', $subscription->id)
            ->where(function ($query) {
                $query->whereNull('method')
                    ->orWhere('method', 'paymongo');
            })
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])
            ->orderByDesc('id')
            ->first();

        // Wrap payment completion + subscription activation in a single transaction.
        DB::transaction(function () use ($payment, $subscription, $paymentData, $paymentMethod) {
            if ($payment) {
                $payment->update([
                    'status' => PaymentStatus::Completed,
                    'method' => $paymentMethod,
                    'paid_at' => now(),
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'paymongo_payment_id' => $paymentData['id'] ?? null,
                        'source_type' => $paymentMethod,
                        'webhook_received_at' => now()->toDateTimeString(),
                    ]),
                ]);

                Log::info('PayMongo Payment Completed', [
                    'payment_id' => $payment->id,
                    'method' => $paymentMethod,
                ]);
            }

            $this->subscriptionService->activate($subscription);
        });
    }

    /**
     * Verify PayMongo webhook signature.
     * Returns false when the secret is not configured, so that
     * unsigned requests are always rejected.
     */
    private function verifyWebhookSignature(string $payload, ?string $signature, string $secret): bool
    {
        if (empty($secret)) {
            Log::error('Webhook secret (PAYMONGO_WEBHOOK_SECRET) is not configured. Request rejected.');
            return false;
        }

        if (empty($signature)) {
            return false;
        }

        // PayMongo standard header format: t=<timestamp>,te=<test_sig>,li=<live_sig>
        if (str_contains($signature, 't=')) {
            $timestamp = null;
            $candidates = [];

            foreach (explode(',', $signature) as $part) {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
                if ($key === 't') {
                    $timestamp = $value;
                }

                if (in_array($key, ['te', 'li'], true) && is_string($value) && $value !== '') {
                    $candidates[] = $value;
                }
            }

            if (!$timestamp || $candidates === []) {
                return false;
            }

            $computed = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

            foreach ($candidates as $candidate) {
                if (hash_equals($computed, $candidate)) {
                    return true;
                }
            }

            return false;
        }

        // Backward-compatible fallback for legacy local tests that send a raw hash.
        $computedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computedSignature, $signature);
    }

    private function normalizePaymentMethod(?string $sourceType): string
    {
        return match ($sourceType) {
            'gcash', 'paymaya', 'grab_pay', 'card', 'bank_transfer' => $sourceType,
            default => 'paymongo',
        };
    }
}
