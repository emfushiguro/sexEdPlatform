<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\PayMongoPaymentLinkService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Show payment form
     */
    public function create(Subscription $subscription)
    {
        // Verify subscription belongs to authenticated user
        if ($subscription->user_id !== Auth::id()) {
            abort(403);
        }

        // Eager-load the plan so getPlanLabel() returns the correct name (not "Free")
        $subscription->load('plan');

        // Get amount from subscription
        $amount = $subscription->getAmount();

        return view('payments.create', compact('subscription', 'amount'));
    }

    /**
     * Process payment via Paymongo
     */
    public function process(ProcessPaymentRequest $request, Subscription $subscription)
    {
        // Verify subscription belongs to authenticated user
        if ($subscription->user_id !== Auth::id()) {
            abort(403);
        }

        DB::beginTransaction();

        try {
            // Get the pending payment for this subscription
            $payment = Payment::where('subscription_id', $subscription->id)
                ->where('status', 'pending')
                ->orderByDesc('id')
                ->first();
            
            if (!$payment) {
                // Create new payment if none exists
                $payment = $subscription->payments()->create([
                    'user_id' => Auth::id(),
                    'amount' => $subscription->getAmount(),
                    'method' => $request->payment_method,
                    'status' => 'pending',
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'payment_details' => [
                        'plan' => $subscription->plan,
                        'payment_method' => $request->payment_method,
                    ],
                ]);
            } else {
                // Update existing pending payment with selected method
                $payment->update([
                    'method' => $request->payment_method,
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'payment_method' => $request->payment_method,
                    ]),
                ]);
            }

            // Create PayMongo payment link
            try {
                $paymongoService = app(PayMongoPaymentLinkService::class);
                
                $planName = $subscription->plan_id
                    ? (\App\Models\SubscriptionPlan::find($subscription->plan_id)?->name ?? 'Premium Subscription')
                    : ucfirst($subscription->plan) . ' Subscription';
                
                $response = $paymongoService->createPaymentLink(
                    amount: $subscription->getAmount(),
                    description: $planName,
                    remarks: "Subscription for " . Auth::user()->name,
                    metadata: [
                        'user_id' => Auth::id(),
                        'subscription_id' => $subscription->id,
                        'payment_id' => $payment->id,
                        'plan' => $subscription->plan,
                    ],
                    successUrl: route('payment.paymongo.success', ['subscription' => $subscription->id]),
                    failedUrl: route('payment.paymongo.failed', ['subscription' => $subscription->id])
                );

                $checkoutUrl = $response['data']['attributes']['checkout_url'] ?? null;
                $paymentLinkId = $response['data']['id'] ?? null;

                if (!$checkoutUrl) {
                    throw new \Exception('Failed to get checkout URL from PayMongo');
                }

                // Update payment with PayMongo reference
                $payment->update([
                    'paymongo_payment_id' => $paymentLinkId,
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'paymongo_link_id' => $paymentLinkId,
                        'checkout_url' => $checkoutUrl,
                    ]),
                ]);

                DB::commit();

                // Redirect to pending page — it will auto-forward to PayMongo.
                // This keeps the page (and its polling JS) active the whole time,
                // so auto-activation fires even if the success_url redirect fails.
                return redirect()->route('payment.pending', ['payment' => $payment->id])
                    ->with('paymongo_checkout_url', $checkoutUrl);

            } catch (\Exception $e) {
                Log::error('PayMongo integration error', [
                    'error' => $e->getMessage(),
                    'subscription_id' => $subscription->id,
                ]);
                
                // If PayMongo fails, redirect to pending page with simulation option
                DB::commit();

                return redirect()->route('payment.pending', ['payment' => $payment->id])
                    ->with('warning', 'Payment gateway is being configured. Use the simulation button for testing.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing error', ['error' => $e->getMessage()]);
            
            return redirect()->back()
                ->with('error', 'Failed to process payment. Please try again.');
        }
    }

    /**
     * Show pending payment status.
     * Automatically checks PayMongo API to see if the payment was already paid —
     * so the user doesn't need to do anything manually even if the redirect failed.
     */
    public function pending(Payment $payment)
    {
        // Guard: orphaned payment (no subscription linked)
        if (!$payment->subscription) {
            return redirect()->route('payment.history')
                ->with('error', 'This payment record has no associated subscription.');
        }

        // Verify payment belongs to authenticated user
        if ($payment->subscription->user_id !== Auth::id()) {
            abort(403);
        }

        // Auto-activate if PayMongo already processed this payment
        if ($payment->status === 'pending') {
            $activated = $this->verifyAndActivateIfPaid($payment);
            if ($activated) {
                return redirect()->route('subscription.index')
                    ->with('success', 'Payment confirmed! Your subscription is now active. 🎉');
            }
        }

        return view('payments.pending', compact('payment'));
    }

    /**
     * JSON endpoint polled by the pending page to check if payment was confirmed.
     * Calls PayMongo API directly — works even without webhooks or success_url redirect.
     */
    public function checkStatus(Payment $payment)
    {
        if (!$payment->subscription) {
            return response()->json(['error' => 'No subscription linked to this payment'], 422);
        }

        if ($payment->subscription->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Already completed in DB
        if ($payment->status === 'completed') {
            return response()->json(['status' => 'completed', 'redirect' => route('subscription.index')]);
        }

        // Ask PayMongo API
        $activated = $this->verifyAndActivateIfPaid($payment);

        if ($activated) {
            return response()->json(['status' => 'completed', 'redirect' => route('subscription.index')]);
        }

        return response()->json(['status' => $payment->status]);
    }

    /**
     * Check PayMongo API to see if the payment link was already paid.
     * If paid, mark payment as completed and activate the subscription.
     * Returns true if subscription was activated.
     */
    private function verifyAndActivateIfPaid(Payment $payment): bool
    {
        try {
            $linkId = $payment->payment_details['paymongo_link_id'] ?? null;

            if (!$linkId) {
                return false;
            }

            $paymongoService = app(PayMongoPaymentLinkService::class);
            $response = $paymongoService->retrievePaymentLink($linkId);

            $status = $response['data']['attributes']['status'] ?? null;

            // PayMongo marks a paid link as 'paid'
            if ($status !== 'paid') {
                return false;
            }

            // Step 1: mark payment completed in its own transaction.
            // PaymentObserver fires inside this transaction and calls
            // SubscriptionService::activate() — fully atomic.
            DB::transaction(function () use ($payment) {
                $payment->update([
                    'status'  => 'completed',
                    'paid_at' => now(),
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'verified_via_api' => true,
                        'verified_at'      => now()->toDateTimeString(),
                    ]),
                ]);
            });

            // Step 2: Safety net — activate via service on a FRESH subscription instance.
            // PaymentObserver already handled activation above, but this ensures
            // the subscription is active even if the observer was skipped for any reason.
            $subscription = Subscription::find($payment->subscription_id);
            if ($subscription) {
                $this->subscriptionService->activate($subscription); // idempotent
            }

            Log::info('Payment auto-activated via PayMongo API verification', [
                'payment_id'      => $payment->id,
                'subscription_id' => $payment->subscription_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::warning('PayMongo API verification failed (non-critical)', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Simulate payment success (for development only)
     * In production, this would be handled by Paymongo webhook
     */
    public function simulateSuccess(Payment $payment)
    {
        // Only allow in development
        if (!app()->environment('local')) {
            abort(404);
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status'  => 'completed',
                'paid_at' => now(),
            ]);

            $this->subscriptionService->activate($payment->subscription);
        });

        return redirect()->route('subscription.index')
            ->with('success', 'Payment simulated! Your subscription is now active.');
    }

    /**
     * Payment webhook handler for PayMongo.
     *
     * IMPORTANT: By the time this method runs, the VerifyPayMongoWebhook middleware
     * has already validated the Paymongo-Signature HMAC header. The inline signature
     * check below is retained as a second-layer defence (defence-in-depth) in case
     * the route middleware is ever accidentally removed.
     *
     * Handles payment link payment events from PayMongo.
     * Captures the actual payment method (GCash, PayMaya, etc.)
     */
    public function webhook(Request $request)
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
                $paymentData = $event['attributes']['data'] ?? [];
                $attributes = $paymentData['attributes'] ?? [];
                
                // Extract payment source type (gcash, grab_pay, paymaya, etc.)
                $source = $attributes['source'] ?? [];
                $paymentMethod = $source['type'] ?? 'paymongo'; // e.g., "gcash", "grab_pay", "paymaya"
                
                // Get metadata for subscription lookup
                $metadata = $attributes['metadata'] ?? [];
                $subscriptionId = $metadata['subscription_id'] ?? null;
                $userId = $metadata['user_id'] ?? null;
                
                Log::info('PayMongo Payment Paid', [
                    'subscription_id' => $subscriptionId,
                    'user_id' => $userId,
                    'payment_method' => $paymentMethod,
                    'amount' => $attributes['amount'] ?? 0,
                ]);

                if ($subscriptionId) {
                    // Find the subscription
                    $subscription = Subscription::find($subscriptionId);
                    
                    if ($subscription) {
                        // Find the most recent pending/paymongo payment for this subscription
                        $payment = Payment::where('subscription_id', $subscription->id)
                            ->whereIn('method', ['paymongo', 'pending'])
                            ->whereIn('status', ['pending', 'processing'])
                            ->orderByDesc('id')
                            ->first();
                        
                        // Wrap payment completion + subscription activation in a single transaction.
                        // If the server crashes between the two writes, the whole block rolls back —
                        // preventing the "paid but no access" scenario.
                        DB::transaction(function () use ($payment, $subscription, $paymentData, $paymentMethod) {
                            if ($payment) {
                                // Update payment status and method with actual source type
                                $payment->update([
                                    'status' => 'completed',
                                    'method' => $paymentMethod, // GCash, PayMaya, etc.
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

                            // Delegate to SubscriptionService::activate() which:
                            // - Guards against double-activation (idempotent status check)
                            // - Wraps subscription update in its own nested transaction
                            // - Invalidates the premium cache
                            // - Fires SubscriptionCreated event (invoice + welcome email queued)
                            $this->subscriptionService->activate($subscription);
                        });
                    }
                }
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

    /**
     * Verify PayMongo webhook signature.
     * Returns false (never true) when the secret is not configured, so that
     * unsigned requests are always rejected rather than silently accepted.
     */
    private function verifyWebhookSignature($payload, $signature, $secret): bool
    {
        if (empty($secret)) {
            Log::error('Webhook secret (PAYMONGO_WEBHOOK_SECRET) is not configured. Request rejected.');
            return false; // Reject all requests when secret is missing
        }

        if (empty($signature)) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computedSignature, $signature);
    }

    /**
     * Show payment history
     */
    public function history()
    {
        $payments = Auth::user()->payments()
            ->with(['subscription.plan'])
            ->latest()
            ->paginate(10);

        return view('payments.history', compact('payments'));
    }

    /**
     * Show payment receipt
     */
    public function receipt(Payment $payment)
    {
        // Verify payment belongs to authenticated user
        if ($payment->subscription->user_id !== Auth::id()) {
            abort(403);
        }

        // Eager-load the plan relationship for the subscription
        $payment->load(['subscription.plan']);

        return view('payments.receipt', compact('payment'));
    }

    /**
     * Handle PayMongo payment success callback
     */
    public function paymongoSuccess(Request $request, $subscriptionId)
    {
        try {
            $subscription = Subscription::findOrFail($subscriptionId);

            // Verify user owns this subscription
            if ($subscription->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access to subscription');
            }

            // Mark the most recent pending payment as completed.
            // PaymentObserver will call SubscriptionService::activate() automatically.
            $payment = Payment::where('subscription_id', $subscription->id)
                ->whereIn('status', ['pending', 'processing'])
                ->orderByDesc('id')
                ->first();

            if ($payment) {
                $payment->update([
                    'status'  => 'completed',
                    'paid_at' => now(),
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'paymongo_callback_received' => true,
                        'callback_received_at'       => now()->toDateTimeString(),
                    ]),
                ]);

                Log::info('PayMongo Payment Completed via Callback', [
                    'payment_id'      => $payment->id,
                    'subscription_id' => $subscription->id,
                ]);
            }

            // Safety net: ensure the subscription is active regardless of whether
            // PaymentObserver already handled it (activate() is idempotent).
            $subscription->refresh();
            $this->subscriptionService->activate($subscription);

            return redirect()->route('subscription.index')
                ->with('success', 'Payment successful! Your subscription is now active. 🎉');

        } catch (\Exception $e) {
            Log::error('PayMongo Success Callback Error', [
                'subscription_id' => $subscriptionId,
                'error'           => $e->getMessage(),
            ]);

            return redirect()->route('subscription.index')
                ->with('error', 'Payment processing error. Please contact support if your subscription is not activated.');
        }
    }

    /**
     * Handle PayMongo payment failed callback
     */
    public function paymongoFailed(Request $request, $subscriptionId)
    {
        try {
            $subscription = Subscription::findOrFail($subscriptionId);
            
            // Verify user owns this subscription
            if ($subscription->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access to subscription');
            }

            Log::warning('PayMongo Payment Failed', [
                'subscription_id' => $subscriptionId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('subscription.upgrade')
                ->with('error', 'Payment was cancelled or failed. Please try again or contact support if you need help.');

        } catch (\Exception $e) {
            Log::error('PayMongo Failed Callback Error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('subscription.index')
                ->with('error', 'An error occurred. Please contact support.');
        }
    }
}
