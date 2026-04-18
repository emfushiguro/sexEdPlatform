<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayMongoPaymentLinkService
 *
 * Encapsulates all communication with the PayMongo REST API (v1).
 * This service is the SINGLE point of entry for every outbound PayMongo
 * request in the application — controllers and other services MUST NOT
 * call PayMongo directly.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PAYMENT FLOW OVERVIEW
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  1. User selects a plan → SubscriptionController::subscribe()
 *  2. SubscriptionService::create() persists a Subscription + Payment [pending]
 *  3. PaymentController::process() calls createPaymentLink()
 *  4. User is redirected to PayMongo's hosted checkout page
 *  5. On success:  success_url → PaymentController::paymongoSuccess()
 *     On failure:  failed_url  → PaymentController::paymongoFailed()
 *  6. Simultaneously, PayMongo fires a webhook → PaymentController::webhook()
 *     Webhook is verified by VerifyPayMongoWebhook middleware before hitting the controller.
 *  7. PaymentObserver::updated() triggers SubscriptionService::activate()
 *  8. SubscriptionCreated event queues welcome email + invoice generation
 *
 * ─────────────────────────────────────────────────────────────────────────
 * CONFIGURATION
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  All PayMongo credentials are stored in .env and read through config/paymongo.php:
 *
 *   PAYMONGO_SECRET_KEY=sk_test_...   (never commit real keys)
 *   PAYMONGO_PUBLIC_KEY=pk_test_...
 *   PAYMONGO_WEBHOOK_SECRET=whsec_...
 *   PAYMONGO_API_BASE_URL=https://api.paymongo.com/v1  (optional override)
 *
 * ─────────────────────────────────────────────────────────────────────────
 * HARDCODED VALUE POLICY
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  Payment amounts are NEVER hardcoded in this service.
 *  Callers MUST pass the exact amount sourced from the SubscriptionPlan model
 *  (plan->monthly_price or plan->annual_price). This ensures that changing a
 *  plan's price in the admin panel is reflected everywhere with zero code changes.
 *
 * @see \App\Models\SubscriptionPlan
 * @see \App\Services\SubscriptionService
 * @see \App\Http\Controllers\PaymentController
 * @see \App\Http\Middleware\VerifyPayMongoWebhook
 */
class PayMongoPaymentLinkService
{
    protected string $secretKey;
    protected string $apiBaseUrl;
    protected string $mode;

    public function __construct()
    {
        $secretKey = (string) config('paymongo.secret_key', '');
        $publicKey = (string) config('paymongo.public_key', '');
        $apiBaseUrl = (string) config('paymongo.api_base_url', '');

        if (empty($secretKey)) {
            throw new \Exception('PayMongo Secret Key is not configured. Please set PAYMONGO_SECRET_KEY in your .env file.');
        }

        $enforceTestMode = (bool) config('paymongo.enforce_test_mode', false) || app()->environment(['local', 'testing']);

        if ($enforceTestMode) {
            if (!str_starts_with($secretKey, 'sk_test_')) {
                throw new \Exception('Sandbox mode enforced: PAYMONGO_SECRET_KEY must use a sk_test_ key in this environment.');
            }

            if ($publicKey !== '' && !str_starts_with($publicKey, 'pk_test_')) {
                throw new \Exception('Sandbox mode enforced: PAYMONGO_PUBLIC_KEY must use a pk_test_ key in this environment.');
            }
        }

        $this->secretKey = $secretKey;
        $this->apiBaseUrl = $apiBaseUrl;
        $this->mode = str_starts_with($secretKey, 'sk_test_')
            ? 'sandbox'
            : (str_starts_with($secretKey, 'sk_live_') ? 'live' : 'unknown');

        Log::info('PayMongo mode initialized', [
            'mode' => $this->mode,
            'enforce_test_mode' => $enforceTestMode,
            'api_base_url' => $this->apiBaseUrl,
        ]);
    }

    public function currentMode(): string
    {
        return $this->mode;
    }

    public function isSandboxMode(): bool
    {
        return $this->mode === 'sandbox';
    }

    /**
     * Convert a PHP major-unit amount (e.g. 79.99) into minor units (centavos).
     * Uses half-up rounding to avoid floating-point truncation (e.g. 79.99 -> 7999).
     */
    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Create a PayMongo Payment Link
     *
     * @param float $amount Amount in PHP (e.g., 299.00)
     * @param string $description Description of the payment
     * @param string|null $remarks Additional remarks
     * @param array $metadata Additional metadata to attach
     * @param string|null $successUrl URL to redirect after successful payment
     * @param string|null $failedUrl URL to redirect after failed payment
     * @return array Response from PayMongo API
     * @throws \Exception
     */
    public function createPaymentLink(
        float $amount,
        string $description,
        ?string $remarks = null,
        array $metadata = [],
        ?string $successUrl = null,
        ?string $failedUrl = null,
        ?string $preferredPaymentMethod = null,
        array $allowedPaymentMethods = [],
    ): array {
        try {
            // Convert amount to centavos (PayMongo expects amount in centavos)
            $amountInCentavos = $this->toMinorUnits($amount);
            $paymentMethodTypes = $this->resolvePaymentMethodTypes($preferredPaymentMethod, $allowedPaymentMethods);

            $payload = [
                'data' => [
                    'attributes' => [
                        'amount' => $amountInCentavos,
                        'description' => config('paymongo.payment_link.description_prefix') . $description,
                        'remarks' => $remarks ?? $description,
                        'payment_method_types' => $paymentMethodTypes,
                    ]
                ]
            ];

            // Add success and failed URLs for automatic callback
            if ($successUrl) {
                $payload['data']['attributes']['success_url'] = $successUrl;
            }
            if ($failedUrl) {
                $payload['data']['attributes']['failed_url'] = $failedUrl;
            }

            // Add metadata if provided
            if (!empty($metadata)) {
                $payload['data']['attributes']['metadata'] = $metadata;
            }

            Log::info('Creating PayMongo Payment Link', [
                'amount' => $amount,
                'amount_centavos' => $amountInCentavos,
                'description' => $description,
                'mode' => $this->mode,
                'payment_method_types' => $paymentMethodTypes,
            ]);

            /** @var Response $response */
            $response = Http::withBasicAuth($this->secretKey, '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->apiBaseUrl}/links", $payload);

            if (!$response->successful()) {
                $error = $response->json();
                Log::error('PayMongo Payment Link creation failed', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                throw new \Exception(
                    $error['errors'][0]['detail'] ?? 'Failed to create payment link'
                );
            }

            $data = $response->json();

            Log::info('PayMongo Payment Link created successfully', [
                'link_id' => $data['data']['id'] ?? null,
                'checkout_url' => $data['data']['attributes']['checkout_url'] ?? null,
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error('PayMongo Payment Link Service Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a PayMongo Checkout Session.
     *
     * This is the preferred integration for learner checkout so the hosted
     * page can present full payment method options with line items.
     *
     * @throws \Exception
     */
    public function createCheckoutSession(
        float $amount,
        string $description,
        ?string $remarks = null,
        array $metadata = [],
        ?string $successUrl = null,
        ?string $cancelUrl = null,
        ?string $preferredPaymentMethod = null,
        array $allowedPaymentMethods = [],
        ?string $lineItemName = null,
        int $quantity = 1,
    ): array {
        try {
            $amountInCentavos = $this->toMinorUnits($amount);
            $paymentMethodTypes = $this->resolvePaymentMethodTypes($preferredPaymentMethod, $allowedPaymentMethods);

            $payload = [
                'data' => [
                    'attributes' => [
                        'description' => config('paymongo.payment_link.description_prefix') . $description,
                        'line_items' => [[
                            'currency' => 'PHP',
                            'amount' => $amountInCentavos,
                            'name' => $lineItemName ?: $description,
                            'quantity' => max(1, $quantity),
                        ]],
                        'payment_method_types' => $paymentMethodTypes,
                    ],
                ],
            ];

            if ($successUrl) {
                $payload['data']['attributes']['success_url'] = $successUrl;
            }

            if ($cancelUrl) {
                $payload['data']['attributes']['cancel_url'] = $cancelUrl;
            }

            if (!empty($metadata)) {
                $payload['data']['attributes']['metadata'] = $metadata;
            }

            Log::info('Creating PayMongo Checkout Session', [
                'amount' => $amount,
                'amount_centavos' => $amountInCentavos,
                'description' => $description,
                'mode' => $this->mode,
                'payment_method_types' => $paymentMethodTypes,
            ]);

            /** @var Response $response */
            $response = Http::withBasicAuth($this->secretKey, '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->apiBaseUrl}/checkout_sessions", $payload);

            if (!$response->successful()) {
                $error = $response->json();
                Log::error('PayMongo Checkout Session creation failed', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                throw new \Exception(
                    $error['errors'][0]['detail'] ?? 'Failed to create checkout session'
                );
            }

            $data = $response->json();

            Log::info('PayMongo Checkout Session created successfully', [
                'session_id' => $data['data']['id'] ?? null,
                'checkout_url' => $data['data']['attributes']['checkout_url'] ?? null,
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('PayMongo Checkout Session Service Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a single Payment Link for a subscription plan.
     * All billing-cycle complexity has been removed — one plan, one price.
     *
     * @param int    $userId         User ID
     * @param int    $subscriptionId Subscription ID
     * @param float  $price          Price from SubscriptionPlan::price
     * @param string $planName       Human-readable plan name for the description
     * @return string Checkout URL
     */
    public function createSubscriptionLink(int $userId, int $subscriptionId, float $price, string $planName = 'Premium'): string
    {
        $response = $this->createPaymentLink(
            amount: $price,
            description: "{$planName} Subscription",
            remarks: "{$planName} — Full Access",
            metadata: [
                'user_id'         => $userId,
                'subscription_id' => $subscriptionId,
                'plan'            => $planName,
            ],
            successUrl: route('payment.paymongo.success', ['subscription' => $subscriptionId]),
            failedUrl:  route('payment.paymongo.failed',  ['subscription' => $subscriptionId]),
        );

        return $response['data']['attributes']['checkout_url'];
    }

    /**
     * Create a Payment Link for Monthly Subscription.
     * @deprecated Use createSubscriptionLink() instead.
     */
    public function createMonthlySubscriptionLink(int $userId, int $subscriptionId, float $monthlyPrice): string
    {
        return $this->createSubscriptionLink($userId, $subscriptionId, $monthlyPrice);
    }

    /**
     * Create a Payment Link for Annual Subscription.
     * @deprecated Use createSubscriptionLink() instead.
     */
    public function createAnnualSubscriptionLink(int $userId, int $subscriptionId, float $annualPrice): string
    {
        return $this->createSubscriptionLink($userId, $subscriptionId, $annualPrice);
    }

    /**
     * Retrieve a Payment Link by ID
     *
     * @param string $linkId Payment Link ID
     * @return array
     */
    public function retrievePaymentLink(string $linkId): array
    {
        try {
            /** @var Response $response */
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get("{$this->apiBaseUrl}/links/{$linkId}");

            if (!$response->successful()) {
                throw new \Exception('Failed to retrieve payment link');
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Failed to retrieve PayMongo Payment Link', [
                'link_id' => $linkId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Retrieve a PayMongo checkout session by ID.
     *
     * @throws \Exception
     */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        try {
            /** @var Response $response */
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get("{$this->apiBaseUrl}/checkout_sessions/{$sessionId}");

            if (!$response->successful()) {
                throw new \Exception('Failed to retrieve checkout session');
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Failed to retrieve PayMongo Checkout Session', [
                'checkout_session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve the actual PayMongo payment object ID (pay_xxxxx) from a payment link.
     *
     * The PayMongo Refunds API requires an actual payment object ID, not a link ID.
     * Payment links store their completed payments in data.attributes.payments[].
     * This method fetches the link and returns the first payment's ID.
     *
     * @param string $linkId  Payment Link ID (link_xxxxx)
     * @return string|null    PayMongo payment object ID (pay_xxxxx) or null if not found
     */
    public function getActualPaymentIdFromLink(string $linkId): ?string
    {
        try {
            /** @var Response $response */
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get("{$this->apiBaseUrl}/links/{$linkId}");

            if (!$response->successful()) {
                Log::warning('PayMongo: Could not retrieve link to resolve payment ID', [
                    'link_id' => $linkId,
                    'status'  => $response->status(),
                ]);
                return null;
            }

            $data     = $response->json();
            $payments = $data['data']['attributes']['payments'] ?? [];

            if (empty($payments)) {
                Log::warning('PayMongo: Link has no associated payments yet', [
                    'link_id' => $linkId,
                ]);
                return null;
            }

            // Return the ID of the first (and usually only) payment object
            $paymentId = $payments[0]['id'] ?? null;

            Log::info('PayMongo: Resolved actual payment ID from link', [
                'link_id'    => $linkId,
                'payment_id' => $paymentId,
            ]);

            return $paymentId;

        } catch (\Exception $e) {
            Log::error('PayMongo: Failed to resolve payment ID from link', [
                'link_id' => $linkId,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Resolve pay_xxxxx from a checkout session payload.
     */
    public function getActualPaymentIdFromCheckoutSession(string $sessionId): ?string
    {
        try {
            $data = $this->retrieveCheckoutSession($sessionId);
            $payments = $data['data']['attributes']['payments'] ?? [];

            if (empty($payments)) {
                Log::warning('PayMongo: Checkout session has no associated payments yet', [
                    'checkout_session_id' => $sessionId,
                ]);
                return null;
            }

            $paymentId = $payments[0]['id'] ?? null;

            Log::info('PayMongo: Resolved actual payment ID from checkout session', [
                'checkout_session_id' => $sessionId,
                'payment_id' => $paymentId,
            ]);

            return $paymentId;
        } catch (\Exception $e) {
            Log::error('PayMongo: Failed to resolve payment ID from checkout session', [
                'checkout_session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Archive (disable) a Payment Link
     *
     * @param string $linkId Payment Link ID
     * @return array
     */
    public function archivePaymentLink(string $linkId): array
    {
        try {
            /** @var Response $response */
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post("{$this->apiBaseUrl}/links/{$linkId}/archive");

            if (!$response->successful()) {
                throw new \Exception('Failed to archive payment link');
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Failed to archive PayMongo Payment Link', [
                'link_id' => $linkId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolvePaymentMethodTypes(?string $preferredPaymentMethod, array $allowedPaymentMethods): array
    {
        $configured = config('paymongo.payment_link.allowed_payment_method_types', ['gcash', 'paymaya', 'grab_pay', 'card']);

        $allowed = !empty($allowedPaymentMethods)
            ? $allowedPaymentMethods
            : (is_array($configured) ? $configured : []);

        $normalized = collect($allowed)
            ->map(fn ($method) => $this->normalizePaymentMethod($method))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($normalized === []) {
            $normalized = ['gcash', 'paymaya', 'grab_pay', 'card'];
        }

        $preferred = $this->normalizePaymentMethod($preferredPaymentMethod);
        if ($preferred && in_array($preferred, $normalized, true)) {
            $normalized = array_values(array_unique(array_merge([$preferred], $normalized)));
        } elseif (in_array('card', $normalized, true)) {
            // If no method was chosen in-app, prioritize card first so checkout opens with a full selector flow.
            $normalized = array_values(array_unique(array_merge(['card'], $normalized)));
        }

        return $normalized;
    }

    private function normalizePaymentMethod(mixed $method): ?string
    {
        if (!is_string($method) || trim($method) === '') {
            return null;
        }

        return match (strtolower(trim($method))) {
            'gcash' => 'gcash',
            'paymaya', 'maya' => 'paymaya',
            'grab_pay', 'grabpay' => 'grab_pay',
            'card' => 'card',
            default => null,
        };
    }
}
