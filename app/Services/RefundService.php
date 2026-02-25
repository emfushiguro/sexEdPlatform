<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RefundService
 *
 * Handles the full lifecycle of a payment refund request, from eligibility
 * validation through PayMongo API interaction and database state updates.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * 3-DAY REFUND POLICY
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  Per the platform's Terms of Service (Section 12.5), users may request a
 *  refund within N calendar days of payment. The window is defined in:
 *
 *    config/billing.php → subscription.refund_window_days   (default: 3)
 *
 *  Changing that single value propagates to:
 *    • This service (enforcement)
 *    • SubscriptionController (user-facing error message)
 *    • resources/views/legal/terms.blade.php (displayed policy text)
 *
 * ─────────────────────────────────────────────────────────────────────────
 * REFUND FLOW
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  A. AUTOMATIC (PayMongo API available):
 *     1. User submits refund from Account → Subscription → Request Refund
 *     2. SubscriptionController::requestRefund() calls processRefund()
 *     3. Eligibility checked: payment.status === 'completed' AND within window
 *     4. Idempotency check: no existing pending/completed refund on this payment
 *     5. Refund record created [status=pending]
 *     6. PayMongo Refunds API called with the actual payment ID
 *     7. On success: Refund [status=completed], Payment [status=refunded],
 *        Subscription [status=cancelled]
 *
 *  B. ACADEMIC / MANUAL (no real PayMongo API call):
 *     When the payment_details JSON contains no 'paymongo_payment_id' AND
 *     no 'paymongo_link_id' (e.g., test payments), the refund is created with
 *     status='manual_processing' and an admin must approve it manually via
 *     the admin dashboard.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * DATABASE STRUCTURE
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  Using a dedicated 'refunds' table (Option 1) is the correct architecture:
 *
 *   refunds
 *   ├── id
 *   ├── payment_id         FK → payments.id  (cascade delete)
 *   ├── user_id            FK → users.id     (cascade delete)
 *   ├── processed_by       FK → users.id (nullable, set null on delete)
 *   ├── amount             decimal(10,2)
 *   ├── status             enum: pending | completed | failed | manual_processing
 *   ├── refund_id          unique string (e.g. REF-ABC123)  — internal reference
 *   ├── paymongo_refund_id string nullable  — PayMongo's own refund object ID
 *   ├── reason             string nullable
 *   ├── admin_notes        text nullable
 *   ├── refund_details     json nullable     — full PayMongo API response
 *   ├── processed_at       timestamp nullable
 *   └── timestamps
 *
 *  Keeping refunds in a separate table (vs adding columns to payments) gives:
 *   • Clean audit trail (multiple partial refunds per payment are trackable)
 *   • No null-padding of the payments table with rarely-used columns
 *   • Easy admin reporting and filtering
 *
 * @see \App\Models\Refund
 * @see \App\Models\Payment
 * @see \App\Http\Controllers\SubscriptionController::requestRefund()
 * @see \App\Http\Controllers\Admin\PaymentAdminController
 */
class RefundService
{
    protected string $secretKey;
    protected string $apiBaseUrl;

    public function __construct()
    {
        $this->secretKey = config('paymongo.secret_key');
        $this->apiBaseUrl = config('paymongo.api_base_url');
    }

    public function processRefund(
        Payment $payment, 
        float $amount = null, 
        string $reason = null,
        string $adminNotes = null,
        bool $bypassTimeLimit = false
    ): Refund {
        $refundAmount = $amount ?? $payment->amount;
        
        // Validate refund amount
        if ($refundAmount > $payment->amount) {
            throw new \InvalidArgumentException('Refund amount cannot exceed payment amount');
        }

        if ($payment->status !== 'completed') {
            throw new \InvalidArgumentException('Can only refund completed payments');
        }

        // Enforce configurable refund window (default 3 days, set in config/billing.php).
        if (!$bypassTimeLimit && $payment->paid_at) {
            $windowDays        = (int) config('billing.subscription.refund_window_days', 3);
            $daysSincePayment  = now()->diffInDays($payment->paid_at);
            if ($daysSincePayment > $windowDays) {
                throw new \InvalidArgumentException(
                    "Refund not allowed. Payment was made {$daysSincePayment} days ago. " .
                    "Refunds are only allowed within {$windowDays} days of payment."
                );
            }
        }

        // Resolve the PayMongo payment identifier.
        // PaymentController::process() stores the link ID under 'paymongo_link_id'.
        // Webhook handler stores the actual payment object ID under 'paymongo_payment_id'.
        // We check both keys so refunds work regardless of which path completed the payment.
        $paymongo_payment_id = $payment->payment_details['paymongo_payment_id']
            ?? $payment->payment_details['paymongo_link_id']
            ?? null;

        // Idempotency: prevent duplicate refunds on the same payment
        $existingRefund = Refund::where('payment_id', $payment->id)
            ->whereIn('status', ['pending', 'completed', 'manual_processing'])
            ->first();

        if ($existingRefund) {
            Log::warning('Duplicate refund attempt blocked', [
                'payment_id'      => $payment->id,
                'existing_refund' => $existingRefund->refund_id,
                'existing_status' => $existingRefund->status,
            ]);
            throw new \RuntimeException(
                "A refund for this payment already exists (ID: {$existingRefund->refund_id}, status: {$existingRefund->status}). " .
                "Contact support if you believe this is an error."
            );
        }
        
        $refund = Refund::create([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'amount' => $refundAmount,
            'reason' => $reason,
            'admin_notes' => $adminNotes,
            'status' => 'pending',
            'refund_id' => 'REF-' . strtoupper(uniqid()),
            'processed_by' => optional(auth())->id(),
        ]);

        try {
            if ($paymongo_payment_id) {
                // Process refund through PayMongo API
                $response = $this->createPayMongoRefund($paymongo_payment_id, $refundAmount, $reason);
                
                $refund->update([
                    'status' => 'completed',
                    'paymongo_refund_id' => $response['data']['id'] ?? null,
                    'processed_at' => now(),
                    'refund_details' => $response
                ]);
            } else {
                // Manual refund (for old payments or other methods)
                $refund->update([
                    'status' => 'manual_processing',
                    'processed_at' => now(),
                    'refund_details' => ['type' => 'manual', 'reason' => 'No PayMongo payment ID found']
                ]);
            }

            // Update payment status if fully refunded
            $totalRefunded = $payment->refunds()->where('status', 'completed')->sum('amount');
            if ($totalRefunded >= $payment->amount) {
                $payment->update(['status' => 'refunded']);
                
                // Cancel associated subscription if needed
                if ($payment->subscription) {
                    $payment->subscription->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancellation_reason' => 'Payment refunded'
                    ]);
                }
            }

            Log::info('Refund processed successfully', [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'amount' => $refundAmount
            ]);

            return $refund;

        } catch (\Exception $e) {
            $refund->update([
                'status' => 'failed',
                'refund_details' => ['error' => $e->getMessage()]
            ]);

            Log::error('Refund processing failed', [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function createPayMongoRefund(string $paymentId, float $amount, string $reason = null): array
    {
        $amountInCentavos = (int) ($amount * 100);

        $payload = [
            'data' => [
                'attributes' => [
                    'amount' => $amountInCentavos,
                    'payment_id' => $paymentId,
                    'reason' => $reason ?? 'requested_by_customer'
                ]
            ]
        ];

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withBasicAuth($this->secretKey, '')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->apiBaseUrl}/refunds", $payload);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception(
                $error['errors'][0]['detail'] ?? 'Failed to create refund'
            );
        }

        return $response->json();
    }

    /**
     * Check if a payment is eligible for refund based on the 3-day policy
     */
    public function isRefundEligible(Payment $payment): array
    {
        if ($payment->status !== 'completed') {
            return [
                'eligible' => false,
                'reason' => 'Payment is not completed',
                'days_since_payment' => null,
            ];
        }

        if (!$payment->paid_at) {
            return [
                'eligible' => false,
                'reason' => 'Payment date not recorded',
                'days_since_payment' => null,
            ];
        }

        $daysSincePayment = now()->diffInDays($payment->paid_at);
        $isEligible = $daysSincePayment <= 3;

        return [
            'eligible' => $isEligible,
            'reason' => $isEligible 
                ? "Refund allowed ({$daysSincePayment} days since payment)" 
                : "Refund period expired ({$daysSincePayment} days since payment, max 3 days)",
            'days_since_payment' => $daysSincePayment,
            'refund_deadline' => $payment->paid_at->copy()->addDays(3)->format('Y-m-d H:i'),
        ];
    }

    /**
     * Get the remaining time for refund eligibility
     */
    public function getRefundDeadline(Payment $payment): ?\Carbon\Carbon
    {
        if (!$payment->paid_at || $payment->status !== 'completed') {
            return null;
        }

        return $payment->paid_at->copy()->addDays(3);
    }
}