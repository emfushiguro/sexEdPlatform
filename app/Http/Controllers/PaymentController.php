<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\PayMongoPaymentLinkService;
use App\Enums\PaymentStatus;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                ->where('status', PaymentStatus::Pending)
                ->orderByDesc('id')
                ->first();
            
            if (!$payment) {
                // Create new payment if none exists
                $payment = $subscription->payments()->create([
                    'user_id' => Auth::id(),
                    'amount' => $subscription->getAmount(),
                    'method' => $request->payment_method,
                    'status' => PaymentStatus::Pending,
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
        if ($payment->status === PaymentStatus::Pending) {
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
        if ($payment->status === PaymentStatus::Completed) {
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

            // Resolve the actual pay_xxxxx ID from the link so refunds work later.
            // The links/{id} endpoint includes data.attributes.payments[].
            $actualPaymentId = $paymongoService->getActualPaymentIdFromLink($linkId);

            // Step 1: mark payment completed in its own transaction.
            // PaymentObserver fires inside this transaction and calls
            // SubscriptionService::activate() — fully atomic.
            DB::transaction(function () use ($payment, $actualPaymentId) {
                $payment->update([
                    'status'  => PaymentStatus::Completed,
                    'paid_at' => now(),
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'verified_via_api'    => true,
                        'verified_at'         => now()->toDateTimeString(),
                        // Store the actual pay_xxxxx so refunds can use it
                        'paymongo_payment_id' => $actualPaymentId,
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
                'status'  => PaymentStatus::Completed,
                'paid_at' => now(),
            ]);

            $this->subscriptionService->activate($payment->subscription);
        });

        return redirect()->route('subscription.index')
            ->with('success', 'Payment simulated! Your subscription is now active.');
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
                ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])
                ->orderByDesc('id')
                ->first();

            if ($payment) {
                $payment->update([
                    'status'  => PaymentStatus::Completed,
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

    /**
     * Show payment success page
     */
    public function success()
    {
        return view('payments.success');
    }

    /**
     * Show payment cancel page
     */
    public function cancel()
    {
        $premiumPlan = \App\Models\SubscriptionPlan::where('is_active', true)
            ->where('price', '>', 0)
            ->orderBy('sort_order')
            ->first();

        return view('payments.cancel', compact('premiumPlan'));
    }
}
