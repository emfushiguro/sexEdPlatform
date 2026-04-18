<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\WebhookController;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PayMongoPaymentLinkService;
use App\Enums\PaymentStatus;
use App\Services\ModulePurchaseService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected ModulePurchaseService $modulePurchaseService,
    ) {}

    public function webhook(Request $request, WebhookController $webhookController)
    {
        return $webhookController->paymongo($request);
    }

    /**
     * Show payment form
     */
    public function create(Subscription $subscription)
    {
        // Verify subscription belongs to authenticated user
        if ($subscription->user_id !== Auth::id()) {
            abort(403);
        }

        $routeContextRedirect = $this->resolveSubscriptionRouteAudienceRedirect($subscription);
        if ($routeContextRedirect !== null) {
            return $routeContextRedirect;
        }

        $isInstructorContext = $this->resolveSubscriptionRouteContext($subscription) === 'instructor';
        $checkoutSummaryRoute = $isInstructorContext
            ? 'instructor.payments.checkout.summary'
            : 'payment.checkout.summary';
        $checkoutProceedRoute = $isInstructorContext
            ? 'instructor.payments.checkout.proceed'
            : 'payment.checkout.proceed';

        if ((bool) config('billing.features.learner_checkout_refinement', false)
            && request()->routeIs('payment.create')
            && !$isInstructorContext) {
            return redirect()->route($checkoutSummaryRoute, ['subscription' => $subscription->id]);
        }

        // Eager-load the plan so getPlanLabel() returns the correct name (not "Free")
        $subscription->load('plan');

        // Get amount from subscription
        $amount = $subscription->getAmount();

        $secretKey = (string) config('paymongo.secret_key', '');
        $paymongoMode = str_starts_with($secretKey, 'sk_test_')
            ? 'sandbox'
            : (str_starts_with($secretKey, 'sk_live_') ? 'live' : 'unknown');

        return view($isInstructorContext ? 'instructor.payments.checkout-summary' : 'payments.checkout-summary', [
            'scope' => 'subscription',
            'subscription' => $subscription,
            'amount' => (float) $amount,
            'paymongoMode' => $paymongoMode,
            'submitUrl' => route($checkoutProceedRoute, $subscription),
            'backUrl' => route($isInstructorContext ? 'instructor.subscriptions.index' : 'subscription.index'),
        ]);
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

        $routeContextRedirect = $this->resolveSubscriptionRouteAudienceRedirect($subscription);
        if ($routeContextRedirect !== null) {
            return $routeContextRedirect;
        }

        $isInstructorContext = $this->resolveSubscriptionRouteContext($subscription) === 'instructor';
        $pendingRoute = $isInstructorContext ? 'instructor.payments.pending' : 'payment.pending';
        $successRoute = $isInstructorContext ? 'instructor.payments.paymongo.success' : 'payment.paymongo.success';
        $failedRoute = $isInstructorContext ? 'instructor.payments.paymongo.failed' : 'payment.paymongo.failed';

        DB::beginTransaction();

        try {
            $billingName = (string) ($request->input('billing_name') ?: (Auth::user()?->name ?? ''));
            $billingEmail = (string) ($request->input('billing_email') ?: (Auth::user()?->email ?? ''));
            $billingPhone = (string) ($request->input('billing_phone') ?: '');
            $selectedMethod = (string) ($request->input('payment_method') ?: 'paymongo');

            // Get the pending payment for this subscription
            $payment = Payment::where('subscription_id', $subscription->id)
                ->where('status', PaymentStatus::Pending->value)
                ->orderByDesc('id')
                ->first();
            
            if (!$payment) {
                // Create new payment if none exists
                $payment = $subscription->payments()->create([
                    'user_id' => Auth::id(),
                    'amount' => $subscription->getAmount(),
                    'method' => $selectedMethod,
                    'status' => PaymentStatus::Pending,
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'payment_details' => [
                        'payment_scope' => 'subscription',
                        'role_context' => $isInstructorContext ? 'instructor' : 'learner',
                        'plan' => $subscription->plan,
                        'payment_method' => $selectedMethod,
                        'billing' => [
                            'name' => $billingName,
                            'email' => $billingEmail,
                            'phone' => $billingPhone,
                        ],
                    ],
                ]);
            } else {
                // Update existing pending payment with selected method
                $payment->update([
                    'method' => $selectedMethod,
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'payment_scope' => 'subscription',
                        'role_context' => $isInstructorContext ? 'instructor' : 'learner',
                        'payment_method' => $selectedMethod,
                        'billing' => [
                            'name' => $billingName,
                            'email' => $billingEmail,
                            'phone' => $billingPhone,
                        ],
                    ]),
                ]);
            }

            // Create PayMongo payment link
            try {
                $paymongoService = app(PayMongoPaymentLinkService::class);
                
                $planName = $subscription->plan_id
                    ? (\App\Models\SubscriptionPlan::find($subscription->plan_id)?->name ?? 'Premium Subscription')
                    : ucfirst($subscription->plan) . ' Subscription';
                
                $response = $paymongoService->createCheckoutSession(
                    amount: $subscription->getAmount(),
                    description: $planName,
                    remarks: "Subscription for " . Auth::user()->name,
                    metadata: [
                        'payment_scope' => 'subscription',
                        'role_context' => $isInstructorContext ? 'instructor' : 'learner',
                        'user_id' => Auth::id(),
                        'subscription_id' => $subscription->id,
                        'payment_id' => $payment->id,
                        'plan' => $subscription->plan,
                        'billing_name' => $billingName,
                        'billing_email' => $billingEmail,
                    ],
                    successUrl: route($successRoute, ['subscription' => $subscription->id]),
                    cancelUrl: route($failedRoute, ['subscription' => $subscription->id]),
                    preferredPaymentMethod: $selectedMethod,
                    lineItemName: $planName,
                );

                $checkoutUrl = $response['data']['attributes']['checkout_url'] ?? null;
                $checkoutSessionId = $response['data']['id'] ?? null;

                if (!$checkoutUrl) {
                    throw new \Exception('Failed to get checkout URL from PayMongo');
                }

                // Update payment with PayMongo reference
                $payment->update([
                    'paymongo_payment_id' => $checkoutSessionId,
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'paymongo_checkout_session_id' => $checkoutSessionId,
                        'checkout_url' => $checkoutUrl,
                    ]),
                ]);

                DB::commit();

                // Redirect to pending page — it will auto-forward to PayMongo.
                // This keeps the page (and its polling JS) active the whole time,
                // so auto-activation fires even if the success_url redirect fails.
                return redirect()->route($pendingRoute, ['payment' => $payment->id])
                    ->with('paymongo_checkout_url', $checkoutUrl);

            } catch (\Exception $e) {
                Log::error('PayMongo integration error', [
                    'error' => $e->getMessage(),
                    'subscription_id' => $subscription->id,
                ]);
                
                // If PayMongo fails, redirect to pending page with simulation option
                DB::commit();

                return redirect()->route($pendingRoute, ['payment' => $payment->id])
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
        $isInstructorContext = $this->isInstructorPaymentContext($payment);
        $isModulePayment = $payment->isModulePurchase();

        if ($isModulePayment) {
            if ($payment->user_id !== Auth::id()) {
                abort(403);
            }
        } else {
            // Guard: orphaned payment (no subscription linked)
            if (!$payment->subscription) {
                return redirect()->route('payment.history')
                    ->with('error', 'This payment record has no associated subscription.');
            }

            if ($payment->subscription->user_id !== Auth::id()) {
                abort(403);
            }
        }

        // Auto-activate if PayMongo already processed this payment
        if ($payment->status === PaymentStatus::Pending) {
            if ($isModulePayment) {
                $completed = $this->modulePurchaseService->verifyAndCompletePendingPayment($payment);
                if ($completed) {
                    return redirect($this->resolvePaymentRedirectUrl($payment))
                        ->with('success', 'Payment confirmed! Your module access is now available.');
                }
            } else {
                $activated = $this->verifyAndActivateIfPaid($payment);
                if ($activated) {
                    return redirect($this->resolvePaymentRedirectUrl($payment))
                        ->with('success', 'Payment confirmed. Your subscription is now active.');
                }
            }
        }

        $paymentContext = $isModulePayment ? 'module' : 'subscription';
        $redirectUrl = $this->resolvePaymentRedirectUrl($payment);
        $statusUrl = route($isInstructorContext ? 'instructor.payments.status' : 'payment.status', $payment);
        $historyUrl = route($isInstructorContext ? 'instructor.payments.history' : 'payment.history');

        return view($isInstructorContext ? 'instructor.payments.pending' : 'payments.pending', compact(
            'payment',
            'paymentContext',
            'redirectUrl',
            'statusUrl',
            'historyUrl'
        ));
    }

    /**
     * JSON endpoint polled by the pending page to check if payment was confirmed.
     * Calls PayMongo API directly — works even without webhooks or success_url redirect.
     */
    public function checkStatus(Payment $payment)
    {
        $isModulePayment = $payment->isModulePurchase();

        if ($isModulePayment) {
            if ($payment->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            if (!$payment->subscription) {
                return response()->json(['error' => 'No subscription linked to this payment'], 422);
            }

            if ($payment->subscription->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        // Already completed in DB
        if ($payment->status === PaymentStatus::Completed) {
            return response()->json(['status' => 'completed', 'redirect' => $this->resolvePaymentRedirectUrl($payment)]);
        }

        if ($isModulePayment) {
            $completed = $this->modulePurchaseService->verifyAndCompletePendingPayment($payment);
            if ($completed) {
                return response()->json(['status' => 'completed', 'redirect' => $this->resolvePaymentRedirectUrl($payment)]);
            }

            return response()->json(['status' => (string) $payment->status->value]);
        }

        // Ask PayMongo API for subscription payments
        $activated = $this->verifyAndActivateIfPaid($payment);

        if ($activated) {
            return response()->json(['status' => 'completed', 'redirect' => $this->resolvePaymentRedirectUrl($payment)]);
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
            $sessionId = (string) data_get($payment->payment_details, 'paymongo_checkout_session_id', '');
            $linkId = (string) data_get($payment->payment_details, 'paymongo_link_id', '');

            if ($sessionId === '' && $linkId === '') {
                return false;
            }

            $paymongoService = app(PayMongoPaymentLinkService::class);
            $response = $sessionId !== ''
                ? $paymongoService->retrieveCheckoutSession($sessionId)
                : $paymongoService->retrievePaymentLink($linkId);

            if (!$this->paymongoResponseIndicatesPaid($response)) {
                return false;
            }

            // Resolve the actual pay_xxxxx ID from the link so refunds work later.
            // The links/{id} endpoint includes data.attributes.payments[].
            $actualPaymentId = $sessionId !== ''
                ? $paymongoService->getActualPaymentIdFromCheckoutSession($sessionId)
                : $paymongoService->getActualPaymentIdFromLink($linkId);

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
        $allowedEnvs = (array) config('billing.payment.simulation_enabled_envs', ['local', 'testing', 'staging']);
        $currentEnv = (string) config('app.env', app()->environment());

        if (!in_array($currentEnv, $allowedEnvs, true)) {
            abort(404);
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status'  => PaymentStatus::Completed,
                'paid_at' => now(),
            ]);

            if ($payment->isModulePurchase()) {
                $this->modulePurchaseService->completePayment($payment, 'paymongo', null);
            } else {
                $this->subscriptionService->activate($payment->subscription);
            }
        });

        return redirect($this->resolvePaymentRedirectUrl($payment))
            ->with('success', 'Payment simulated successfully.');
    }

    /**
     * Show payment history
     */
    public function history()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $isInstructorContext = request()->routeIs('instructor.payments.history');

        $pendingPaymentsQuery = $user->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->latest('id')
            ->limit(20);

        if ($isInstructorContext) {
            $pendingPaymentsQuery->whereNotNull('subscription_id')
                ->whereHas('subscription.plan', function ($query) {
                    $query->where('plan_audience', 'instructor');
                });
        }

        // Reconcile stale pending records before rendering history table.
        $pendingPayments = $pendingPaymentsQuery->get();

        foreach ($pendingPayments as $pendingPayment) {
            if ($pendingPayment->isModulePurchase()) {
                $this->modulePurchaseService->verifyAndCompletePendingPayment($pendingPayment);
                continue;
            }

            $this->verifyAndActivateIfPaid($pendingPayment);
        }

        $paymentsQuery = $user->payments()
            ->with(['subscription.plan', 'modulePurchase.module'])
            ->latest();

        if ($isInstructorContext) {
            $paymentsQuery->whereNotNull('subscription_id')
                ->whereHas('subscription.plan', function ($query) {
                    $query->where('plan_audience', 'instructor');
                });
        }

        $payments = $paymentsQuery->paginate(10);

        return view($isInstructorContext ? 'instructor.payments.history' : 'payments.history', compact('payments'));
    }

    /**
     * Show payment receipt
     */
    public function receipt(Payment $payment)
    {
        $isInstructorContext = request()->routeIs('instructor.payments.receipt');

        if ($payment->isModulePurchase()) {
            if ($payment->user_id !== Auth::id()) {
                abort(403);
            }
            if ($isInstructorContext) {
                abort(403);
            }
        } else {
            // Verify payment belongs to authenticated user
            if (!$payment->subscription || $payment->subscription->user_id !== Auth::id()) {
                abort(403);
            }

            if ($isInstructorContext) {
                $payment->load('subscription.plan');
                $subscriptionPlan = $this->resolveSubscriptionPlan($payment->subscription);
                if ((string) ($subscriptionPlan?->plan_audience ?? '') !== 'instructor') {
                    abort(403);
                }
            }
        }

        // Eager-load the plan relationship for the subscription
        $payment->load(['subscription.plan', 'modulePurchase.module']);

        return view($isInstructorContext ? 'instructor.payments.receipt' : 'payments.receipt', compact('payment'));
    }

    /**
     * Handle PayMongo payment success callback
     */
    public function paymongoSuccess(Request $request, $subscriptionId)
    {
        try {
            $subscription = Subscription::findOrFail($subscriptionId);
            $isInstructorContext = $this->resolveSubscriptionRouteContext($subscription) === 'instructor';

            // Verify user owns this subscription
            if ($subscription->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access to subscription');
            }

            // Mark the most recent pending payment as completed.
            // PaymentObserver will call SubscriptionService::activate() automatically.
            $payment = Payment::where('subscription_id', $subscription->id)
                ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Processing->value])
                ->orderByDesc('id')
                ->first();

            $activated = false;
            if ($payment) {
                $payment->update([
                    'payment_details' => array_merge($payment->payment_details ?? [], [
                        'paymongo_callback_received' => true,
                        'callback_received_at'       => now()->toDateTimeString(),
                    ]),
                ]);

                $activated = $this->verifyAndActivateIfPaid($payment->fresh());
            }

            $completedPayment = Payment::where('subscription_id', $subscription->id)
                ->where('status', PaymentStatus::Completed->value)
                ->latest('id')
                ->first();

            if ($completedPayment) {
                $this->subscriptionService->activate($subscription->fresh());
                $activated = true;
            }

            if (!$activated) {
                $pendingRoute = $isInstructorContext ? 'instructor.payments.pending' : 'payment.pending';
                $pendingPayment = Payment::where('subscription_id', $subscription->id)
                    ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Processing->value])
                    ->orderByDesc('id')
                    ->first();

                if ($pendingPayment) {
                    return redirect()->route($pendingRoute, ['payment' => $pendingPayment->id])
                        ->with('warning', 'Payment is still pending confirmation. We will activate your subscription once PayMongo confirms it.');
                }

                return redirect()->route($isInstructorContext ? 'instructor.subscriptions.index' : 'subscription.index')
                    ->with('warning', 'Payment confirmation is still pending. Please check your payment history in a moment.');
            }

            return redirect()->route($isInstructorContext ? 'instructor.subscriptions.index' : 'subscription.index')
                ->with('success', 'Payment successful. Your subscription is now active.');

        } catch (\Exception $e) {
            Log::error('PayMongo Success Callback Error', [
                'subscription_id' => $subscriptionId,
                'error'           => $e->getMessage(),
            ]);

            $fallbackRoute = request()->routeIs('instructor.payments.paymongo.success')
                ? 'instructor.subscriptions.index'
                : 'subscription.index';

            return redirect()->route($fallbackRoute)
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
            $isInstructorContext = $this->resolveSubscriptionRouteContext($subscription) === 'instructor';
            
            // Verify user owns this subscription
            if ($subscription->user_id !== Auth::id()) {
                abort(403, 'Unauthorized access to subscription');
            }

            Log::warning('PayMongo Payment Failed', [
                'subscription_id' => $subscriptionId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route(
                $isInstructorContext ? 'instructor.payments.checkout.summary' : 'payment.checkout.summary',
                ['subscription' => $subscription->id]
            )
                ->with('error', 'Payment was cancelled or failed. Review your checkout details and try again.');

        } catch (\Exception $e) {
            Log::error('PayMongo Failed Callback Error', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            $fallbackRoute = request()->routeIs('instructor.payments.paymongo.failed')
                ? 'instructor.subscriptions.index'
                : 'subscription.index';

            return redirect()->route($fallbackRoute)
                ->with('error', 'An error occurred. Please contact support.');
        }
    }

    /**
     * Show payment success page
     */
    public function success()
    {
        $scope = (string) request('scope', 'subscription');
        $moduleId = (int) request('module_id', 0);
        $paymentId = (int) request('payment_id', 0);
        $receiptUrl = route('payment.history');

        if ($scope === 'subscription' && Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            $pendingQuery = $user->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->where(function ($query) {
                    $query->where('payment_details->payment_scope', 'subscription')
                        ->orWhereNotNull('subscription_id');
                })
                ->latest('id');

            if ($paymentId > 0) {
                $pendingQuery->where('id', $paymentId);
            }

            $pendingPayment = $pendingQuery->first();

            if ($pendingPayment && $this->verifyAndActivateIfPaid($pendingPayment)) {
                return redirect()->route('subscription.index')
                    ->with('success', 'Payment confirmed. Your subscription is now active.');
            }

            if ($paymentId > 0) {
                $completedPayment = $user->payments()
                    ->where('status', PaymentStatus::Completed->value)
                    ->whereNotNull('subscription_id')
                    ->where('id', $paymentId)
                    ->latest('id')
                    ->first();

                if ($completedPayment?->subscription) {
                    $this->subscriptionService->activate($completedPayment->subscription);

                    return redirect()->route('subscription.index')
                        ->with('success', 'Your subscription is active.');
                }
            }
        }

        $module = null;
        if ($scope === 'module_purchase' && $moduleId > 0) {
            $module = \App\Models\Module::query()->find($moduleId);

            if (Auth::check()) {
                /** @var \App\Models\User $user */
                $user = Auth::user();

                $pendingQuery = $user->payments()
                    ->where('status', PaymentStatus::Pending->value)
                    ->where('payment_details->payment_scope', 'module_purchase')
                    ->where('payment_details->module_id', $moduleId)
                    ->latest('id');

                if ($paymentId > 0) {
                    $pendingQuery->where('id', $paymentId);
                }

                $pendingPayment = $pendingQuery->first();
                if ($pendingPayment) {
                    $this->modulePurchaseService->verifyAndCompletePendingPayment($pendingPayment);
                }
            }
        }

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $receiptPayment = $this->resolveSuccessReceiptPayment($user, $scope, $moduleId, $paymentId);
            if ($receiptPayment) {
                $receiptUrl = route('payment.receipt', $receiptPayment);
            }
        }

        return view('payments.success', [
            'scope' => $scope,
            'module' => $module,
            'receiptUrl' => $receiptUrl,
        ]);
    }

    private function resolveSuccessReceiptPayment(User $user, string $scope, int $moduleId, int $paymentId): ?Payment
    {
        if ($paymentId > 0) {
            $directPayment = $this->findScopedReceiptPayment($user, $scope, $moduleId, $paymentId);
            if ($directPayment) {
                return $directPayment;
            }
        }

        return $this->findLatestScopedReceiptPayment($user, $scope, $moduleId);
    }

    private function findScopedReceiptPayment(User $user, string $scope, int $moduleId, int $paymentId): ?Payment
    {
        $payment = $user->payments()
            ->where('status', PaymentStatus::Completed->value)
            ->where('id', $paymentId)
            ->first();

        if (!$payment) {
            return null;
        }

        if ($scope === 'module_purchase') {
            if (!$payment->isModulePurchase()) {
                return null;
            }

            if ($moduleId > 0 && (int) data_get($payment->payment_details, 'module_id') !== $moduleId) {
                return null;
            }

            return $payment;
        }

        if ($payment->isModulePurchase()) {
            return null;
        }

        return $payment;
    }

    private function findLatestScopedReceiptPayment(User $user, string $scope, int $moduleId): ?Payment
    {
        $query = $user->payments()
            ->where('status', PaymentStatus::Completed->value)
            ->latest('id');

        if ($scope === 'module_purchase') {
            $query->where('payment_details->payment_scope', 'module_purchase');

            if ($moduleId > 0) {
                $query->where('payment_details->module_id', $moduleId);
            }

            return $query->first();
        }

        return $query
            ->where(function ($scopeQuery) {
                $scopeQuery->where('payment_details->payment_scope', 'subscription')
                    ->orWhereNotNull('subscription_id');
            })
            ->first();
    }

    /**
     * Show payment cancel page
     */
    public function cancel()
    {
        $scope = (string) request('scope', 'subscription');
        $moduleId = (int) request('module_id', 0);

        $module = null;
        if ($scope === 'module_purchase' && $moduleId > 0) {
            $module = \App\Models\Module::query()->find($moduleId);
        }

        $premiumPlan = \App\Models\SubscriptionPlan::where('is_active', true)
            ->where('price', '>', 0)
            ->orderBy('sort_order')
            ->first();

        return view('payments.cancel', [
            'premiumPlan' => $premiumPlan,
            'scope' => $scope,
            'module' => $module,
        ]);
    }

    private function resolvePaymentRedirectUrl(Payment $payment): string
    {
        if ($payment->isModulePurchase()) {
            $moduleId = (int) data_get($payment->payment_details, 'module_id');
            if ($moduleId > 0) {
                return route('learner.modules.show', $moduleId);
            }

            return route('learner.modules.index');
        }

        if ($this->isInstructorPaymentContext($payment)) {
            return route('instructor.subscriptions.index');
        }

        return route('subscription.index');
    }

    private function resolveSubscriptionRouteContext(Subscription $subscription): string
    {
        $subscriptionPlan = $this->resolveSubscriptionPlan($subscription);
        $planAudience = strtolower(trim((string) ($subscriptionPlan?->plan_audience ?? '')));
        if ($planAudience === 'instructor') {
            return 'instructor';
        }

        if ($planAudience === 'learner') {
            return 'learner';
        }

        $latestRoleContext = strtolower((string) data_get(
            $subscription->payments()->latest('id')->first(),
            'payment_details.role_context',
            ''
        ));

        if (in_array($latestRoleContext, ['instructor', 'learner'], true)) {
            return $latestRoleContext;
        }

        $subscription->loadMissing('user.roles');
        if ($subscription->user?->hasRole('instructor')) {
            return 'instructor';
        }

        return 'learner';
    }

    private function resolveSubscriptionRouteAudienceRedirect(Subscription $subscription): ?RedirectResponse
    {
        $context = $this->resolveSubscriptionRouteContext($subscription);
        $isInstructorPaymentRoute = request()->is('instructor/payments/*');
        $isLearnerPaymentRoute = request()->is('payment/*');

        if ($isInstructorPaymentRoute && $context !== 'instructor') {
            return redirect()
                ->route('instructor.subscriptions.index')
                ->with('error', 'This checkout link is not valid for instructor subscriptions. Please choose an instructor plan and try again.');
        }

        if ($isLearnerPaymentRoute && $context !== 'learner') {
            return redirect()
                ->route('subscription.index')
                ->with('error', 'This checkout link is not valid for learner subscriptions. Please choose a learner plan and try again.');
        }

        return null;
    }

    private function paymongoResponseIndicatesPaid(array $response): bool
    {
        $status = strtolower((string) data_get($response, 'data.attributes.status', ''));
        if (in_array($status, ['paid', 'completed', 'succeeded', 'successful'], true)) {
            return true;
        }

        $payments = data_get($response, 'data.attributes.payments', []);
        if (!is_array($payments) || empty($payments)) {
            return false;
        }

        foreach ($payments as $item) {
            $paymentStatus = strtolower((string) data_get($item, 'attributes.status', data_get($item, 'status', '')));
            if (in_array($paymentStatus, ['paid', 'completed', 'succeeded', 'successful'], true)) {
                return true;
            }
        }

        return false;
    }

    private function isInstructorPaymentContext(Payment $payment): bool
    {
        if ((string) data_get($payment->payment_details, 'role_context') === 'instructor') {
            return true;
        }

        if (!$payment->subscription_id) {
            return false;
        }

        $payment->loadMissing('subscription.plan');

        return (string) ($this->resolveSubscriptionPlan($payment->subscription)?->plan_audience ?? '') === 'instructor';
    }

    private function resolveSubscriptionPlan(?Subscription $subscription): ?SubscriptionPlan
    {
        if (!$subscription) {
            return null;
        }

        $subscription->loadMissing('plan');
        $plan = $subscription->getRelation('plan');

        return $plan instanceof SubscriptionPlan ? $plan : null;
    }
}
