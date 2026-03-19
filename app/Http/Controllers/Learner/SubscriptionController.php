<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelSubscriptionRequest;
use App\Http\Requests\SubscribeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PayMongoPaymentLinkService;
use App\Services\RefundService;
use App\Services\SubscriptionService;
use App\Services\EntitlementService;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected EntitlementService $entitlementService,
    ) {}

    /**
     * Display subscription plans and current status
     */
    public function index()
    {
        $user = Auth::user();

        // Check for any pending subscription and try to verify payment with PayMongo API.
        // This handles the case where the user paid but the success redirect never fired
        // (e.g. closed browser, poor connection, PayMongo didn't redirect back).
        $pendingSubscription = $user->subscriptions()
            ->where('status', 'pending')
            ->with('payments')
            ->latest()
            ->first();

        if ($pendingSubscription) {
            // First check: payment already completed in DB (e.g. by observer)
            $completedPayment = $pendingSubscription->payments()
                ->where('status', PaymentStatus::Completed)
                ->first();

            if ($completedPayment) {
                try {
                    $this->subscriptionService->activate($pendingSubscription);
                    session()->flash('success', 'Your subscription has been activated! Welcome to premium! 🎉');
                } catch (\Exception $e) {
                    Log::warning('Auto-activation failed', ['error' => $e->getMessage()]);
                }
            } else {
                // Second check: ask PayMongo API directly if the link was paid
                $pendingPayment = $pendingSubscription->payments()
                    ->where('status', PaymentStatus::Pending)
                    ->whereNotNull('payment_details')
                    ->latest()
                    ->first();

                if ($pendingPayment) {
                    try {
                        $linkId = $pendingPayment->payment_details['paymongo_link_id'] ?? null;
                        if ($linkId) {
                            $paymongoService = app(PayMongoPaymentLinkService::class);
                            $response = $paymongoService->retrievePaymentLink($linkId);
                            $status = $response['data']['attributes']['status'] ?? null;

                            if ($status === 'paid') {
                                DB::transaction(function () use ($pendingPayment, $pendingSubscription) {
                                    $pendingPayment->update([
                                        'status'  => PaymentStatus::Completed,
                                        'paid_at' => now(),
                                        'payment_details' => array_merge($pendingPayment->payment_details ?? [], [
                                            'verified_via_api' => true,
                                            'verified_at'      => now()->toDateTimeString(),
                                        ]),
                                    ]);
                                    $this->subscriptionService->activate($pendingSubscription);
                                });
                                session()->flash('success', 'Your subscription has been activated! Welcome to premium! 🎉');
                            }
                        }
                    } catch (\Exception $e) {
                        // Non-critical — just means PayMongo API is unavailable or link not found
                        Log::info('PayMongo API subscription check skipped', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        $subscription = $user->subscription;
        if ($subscription) {
            $subscription->load('plan');
        }

        // Compute refund eligibility in the controller — keeps Blade free of DB queries.
        $canRequestRefund  = false;
        $latestPaidPayment = null;
        if ($subscription && $subscription->status === SubscriptionStatus::Active) {
            $refundWindowDays  = config('billing.subscription.refund_window_days', 3);
            $latestPaidPayment = $subscription->payments()
                ->where('status', PaymentStatus::Completed)
                ->whereNotNull('paid_at')
                ->latest('paid_at')
                ->first();
            $canRequestRefund = $latestPaidPayment
                && now()->lte($latestPaidPayment->paid_at->copy()->addDays($refundWindowDays));
        }

        $subscriptionSummary = $this->entitlementService->getSubscriptionSummary($user);

        return view('subscriptions.index', compact('subscription', 'canRequestRefund', 'latestPaidPayment', 'subscriptionSummary'));
    }

    /**
     * Show the upgrade page with available plans
     */
    public function upgrade()
    {
        $user = Auth::user();

        // Allow all authenticated users to browse plans (for comparison/awareness).
        // The subscribe() method still blocks active subscribers from subscribing again.
        // Previously this redirected premium users away, which caused confusion.
        $currentSubscription = $user->subscription;
        $availablePlans      = SubscriptionPlan::active()->ordered()->with(['planPrices' => function ($q) {
            $q->where('is_active', true)->orderByDesc('is_default')->orderBy('duration_count');
        }])->get();
        $currentPlanId       = $currentSubscription?->plan_id;
        $hasActiveSubscription = $currentSubscription && $currentSubscription->status === SubscriptionStatus::Active;

        return view('subscriptions.upgrade', compact(
            'currentSubscription',
            'availablePlans',
            'currentPlanId',
            'hasActiveSubscription'
        ));
    }

    /**
     * Process subscription upgrade with dynamic plan support
     */
    public function processUpgrade(SubscribeRequest $request)
    {
        $user = Auth::user();

        if ($user->isPremium()) {
            return redirect()->route('subscription.index')
                ->with('error', 'You already have an active premium subscription.');
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            $subscription = $this->subscriptionService->create($user, $plan);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('payment.create', ['subscription' => $subscription->id])
            ->with('success', 'Please complete the payment to activate your subscription.');
    }

    /**
     * Subscribe to a specific plan (unified method)
     */
    public function subscribe(SubscribeRequest $request)
    {
        $user = Auth::user();

        if ($user->isPremium()) {
            return redirect()->route('subscription.index')
                ->with('error', 'You already have an active premium subscription. Cancel first to switch plans.');
        }

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        try {
            $subscription = $this->subscriptionService->create($user, $plan);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('payment.create', ['subscription' => $subscription->id])
            ->with('success', 'Please complete the payment to activate your subscription.');
    }

    /**
     * Legacy routes — redirect to PayMongo checkout directly.
     */
    public function subscribeMonthly(PayMongoPaymentLinkService $paymongoService)
    {
        $user = Auth::user();

        if ($user->isPremium()) {
            return redirect()->route('subscription.index')
                ->with('error', 'You already have an active premium subscription.');
        }

        $plan = SubscriptionPlan::where('slug', 'premium')
            ->orWhere('slug', 'like', 'premium-%')
            ->first();

        try {
            $result = $plan
                ? $this->subscriptionService->createWithPayMongoLink($user, $plan, $paymongoService)
                : $this->subscriptionService->createLegacy($user, $paymongoService);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect($result['checkout_url']);
    }

    public function subscribeAnnual(PayMongoPaymentLinkService $paymongoService)
    {
        // Annual billing removed — redirect to standard subscribe flow
        return redirect()->route('subscription.upgrade')
            ->with('info', 'Please select a plan below.');
    }

    /**
     * Cancel subscription
     */
    public function cancel(CancelSubscriptionRequest $request)
    {
        $user         = Auth::user();
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->canCancel()) {
            return redirect()->route('subscription.index')
                ->with('error', 'No active subscription to cancel.');
        }

        try {
            $this->subscriptionService->cancel($subscription, $request->cancellation_reason);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('subscription.index')
            ->with('success', 'Your subscription has been cancelled successfully.');
    }

    public function requestRefund(Request $request)
    {
        $user         = Auth::user();
        $subscription = $user->subscription;

        if (!$subscription || $subscription->status !== SubscriptionStatus::Active) {
            return redirect()->back()->with('error', 'No active subscription to refund.');
        }

        $payment = $subscription->payments()->where('status', PaymentStatus::Completed)->latest('paid_at')->first();

        if (!$payment || !$payment->paid_at) {
            return redirect()->back()->with('error', 'No completed payment found for this subscription.');
        }

        if (now()->gt($payment->paid_at->copy()->addDays(config('billing.subscription.refund_window_days', 3)))) {
            return redirect()->back()->with('error', 'The ' . config('billing.subscription.refund_window_days', 3) . '-day refund window has expired.');
        }

        try {
            $refundService = app(RefundService::class);
            $refundService->processRefund(
                $payment,
                null,
                $request->input('reason', 'Customer refund request'),
                null,
                false
            );

            return redirect()->route('subscription.index')
                ->with('success', 'Your refund has been submitted successfully. Your subscription has been cancelled and the refund is being processed.');
        } catch (\Exception $e) {
            Log::error('User refund request failed', [
                'user_id'    => $user->id,
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function renew()
    {
        $user         = Auth::user();
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->canRenew()) {
            return redirect()->route('subscription.index')
                ->with('error', 'No cancelled subscription to renew.');
        }

        try {
            $this->subscriptionService->renew($subscription);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('subscription.index')
            ->with('success', 'Your subscription has been renewed successfully!');
    }

    /**
     * Check subscription status (API endpoint)
     */
    public function checkStatus()
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        return response()->json([
            'has_subscription' => $subscription !== null,
            'is_premium' => $user->isPremium(),
            'plan' => $subscription?->plan,
            'plan_name' => $subscription?->getPlanLabel(),
            'status' => $subscription?->status,
            'end_date' => $subscription?->end_date,
            'days_remaining' => $subscription?->daysUntilExpiry(),
        ]);
    }
}
