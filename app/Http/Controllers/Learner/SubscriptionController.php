<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelSubscriptionRequest;
use App\Http\Requests\SubscribeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PayMongoPaymentLinkService;
use App\Services\RefundService;
use App\Services\SubscriptionService;
use App\Services\EntitlementService;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Support\SubscriptionFeatureKeys;
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
        /** @var User $user */
        $user = Auth::user();
        $this->subscriptionService->reconcileLifecycleForUser($user);

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
                ->where('status', PaymentStatus::Completed->value)
                ->first();

            if ($completedPayment) {
                try {
                    $this->subscriptionService->activate($pendingSubscription);
                    session()->flash('success', 'Your subscription has been activated. Welcome to premium access.');
                } catch (\Exception $e) {
                    Log::warning('Auto-activation failed', ['error' => $e->getMessage()]);
                }
            } else {
                // Second check: ask PayMongo API directly if the link was paid
                $pendingPayment = $pendingSubscription->payments()
                    ->where('status', PaymentStatus::Pending->value)
                    ->whereNotNull('payment_details')
                    ->latest()
                    ->first();

                if ($pendingPayment) {
                    try {
                        $sessionId = (string) data_get($pendingPayment->payment_details, 'paymongo_checkout_session_id', '');
                        $linkId = (string) data_get($pendingPayment->payment_details, 'paymongo_link_id', '');

                        if ($sessionId !== '' || $linkId !== '') {
                            $paymongoService = app(PayMongoPaymentLinkService::class);
                            $response = $sessionId !== ''
                                ? $paymongoService->retrieveCheckoutSession($sessionId)
                                : $paymongoService->retrievePaymentLink($linkId);

                            $status = strtolower((string) data_get($response, 'data.attributes.status', ''));
                            $hasPayments = !empty(data_get($response, 'data.attributes.payments', []));

                            if ($status === 'paid' || $status === 'completed' || $hasPayments) {
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
                                session()->flash('success', 'Your subscription has been activated. Welcome to premium access.');
                            }
                        }
                    } catch (\Exception $e) {
                        // Non-critical — just means PayMongo API is unavailable or link not found
                        Log::info('PayMongo API subscription check skipped', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        $latestSubscription = $user->subscriptions()->latest('id')->first();
        if ($latestSubscription) {
            $latestSubscription->load('plan');
        }

        $subscription = $latestSubscription;
        $activeSubscription = $subscription && $subscription->status === SubscriptionStatus::Active
            ? $subscription
            : null;

        // Compute refund eligibility in the controller — keeps Blade free of DB queries.
        $canRequestRefund  = false;
        $latestPaidPayment = null;
        if ($activeSubscription) {
            $refundWindowDays  = config('billing.subscription.refund_window_days', 3);
            $latestPaidPayment = $activeSubscription->payments()
                ->where('status', PaymentStatus::Completed->value)
                ->whereNotNull('paid_at')
                ->latest('paid_at')
                ->first();
            $canRequestRefund = $latestPaidPayment
                && now()->lte($latestPaidPayment->paid_at->copy()->addDays($refundWindowDays));
        }

        $subscriptionSummary = $this->entitlementService->getSubscriptionSummary($user);

        $availablePlans = SubscriptionPlan::active()
            ->ordered()
            ->with(['planPrices' => function ($q) {
                $q->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('duration_count');
            }, 'featureEntitlements.feature'])
            ->get();

        $planCards = $this->buildPlanCards($availablePlans, $activeSubscription);
        $planCards = $this->prependFreeBaselineCard($planCards, $activeSubscription);
        $comparisonFeatures = $this->buildComparisonFeatures($planCards);
        $entitlementHighlights = $this->buildEntitlementHighlights($user);
        $renewalNotice = $this->buildRenewalNotice($latestSubscription);

        return view('subscriptions.index', compact(
            'subscription',
            'canRequestRefund',
            'latestPaidPayment',
            'subscriptionSummary',
            'planCards',
            'comparisonFeatures',
            'entitlementHighlights',
            'renewalNotice'
        ));
    }

    /**
     * Show the upgrade page with available plans
     */
    public function upgrade()
    {
        /** @var User $user */
        $user = Auth::user();

        // Allow all authenticated users to browse plans (for comparison/awareness).
        // The subscribe() method still blocks active subscribers from subscribing again.
        // Previously this redirected premium users away, which caused confusion.
        $currentSubscription = $user->subscription;
        $availablePlans      = SubscriptionPlan::active()->ordered()->with(['planPrices' => function ($q) {
            $q->where('is_active', true)->orderByDesc('is_default')->orderBy('duration_count');
        }, 'featureEntitlements.feature'])->get();
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
        /** @var User $user */
        $user = Auth::user();

        if ($this->subscriptionService->isUserPremium($user)) {
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
        /** @var User $user */
        $user = Auth::user();

        if ($this->subscriptionService->isUserPremium($user)) {
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
        /** @var User $user */
        $user = Auth::user();

        if ($this->subscriptionService->isUserPremium($user)) {
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
        /** @var User $user */
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
        /** @var User $user */
        $user         = Auth::user();
        $subscription = $user->subscription;

        if (!$subscription || $subscription->status !== SubscriptionStatus::Active) {
            return redirect()->back()->with('error', 'No active subscription to refund.');
        }

        $payment = $subscription->payments()->where('status', PaymentStatus::Completed->value)->latest('paid_at')->first();

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
        /** @var User $user */
        $user         = Auth::user();
        $subscription = $user->subscriptions()->latest('id')->first();

        if (!$subscription || !$this->subscriptionService->isRenewableNow($subscription)) {
            return redirect()->route('subscription.index')
                ->with('error', 'Your current subscription is not yet eligible for renewal.');
        }

        try {
            $this->subscriptionService->renew($subscription);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('subscription.index')
            ->with('success', 'Your subscription has been renewed successfully!');
    }

    private function buildRenewalNotice(?Subscription $subscription): ?array
    {
        if (!$subscription) {
            return null;
        }

        $status = $this->subscriptionService->getRenewalStatus($subscription);
        if (!(bool) ($status['is_renewable_now'] ?? false)) {
            return null;
        }

        $effectiveEnd = $status['effective_end_at'] ?? null;

        if ($status['is_expired']) {
            return [
                'tone' => 'expired',
                'title' => 'Your premium access has expired',
                'message' => 'Renew now to restore premium access immediately.',
                'ends_at' => $effectiveEnd,
            ];
        }

        return [
            'tone' => 'expiring',
            'title' => 'Your premium access is expiring soon',
            'message' => 'Renew early to keep your remaining paid time and extend your access.',
            'ends_at' => $effectiveEnd,
            'days_remaining' => $status['days_remaining'] ?? null,
            'warning_days' => $status['warning_days'] ?? null,
        ];
    }

    /**
     * Check subscription status (API endpoint)
     */
    public function checkStatus()
    {
        /** @var User $user */
        $user = Auth::user();
        $subscription = $user->subscription;

        return response()->json([
            'has_subscription' => $subscription !== null,
            'is_premium' => $user->isPremium(),
            'plan' => $subscription?->plan,
            'plan_name' => $subscription?->getPlanLabel(),
            'status' => $subscription?->status,
            'end_date' => $subscription?->ends_at ?? $subscription?->end_date,
            'days_remaining' => $subscription?->daysUntilExpiry(),
        ]);
    }

    /**
     * Build normalized plan cards payload for the learner subscription UI.
     */
    private function buildPlanCards($plans, ?Subscription $currentSubscription): array
    {
        $labelMap = config('subscription_features.labels', []);
        $hiddenKeys = config('subscription_features.hidden', ['test_mode', 'duration_minutes']);
        $hasActiveSubscription = $currentSubscription && $currentSubscription->status === SubscriptionStatus::Active;
        $today = now()->startOfDay();

        $cards = collect($plans)->map(function ($plan) use ($currentSubscription, $hasActiveSubscription, $today, $labelMap, $hiddenKeys) {
            $isCurrentPlan = $currentSubscription?->plan_id === $plan->id;

            $prices = collect($plan->planPrices ?? [])->map(function ($price) {
                $label = $price->duration_label
                    ?: ucfirst((string) ($price->duration_unit ?? 'Plan'));

                $amountMinor = (int) ($price->amount_minor ?? 0);

                return [
                    'id' => $price->id,
                    'label' => $label,
                    'duration_unit' => $price->duration_unit,
                    'duration_count' => (int) ($price->duration_count ?? 1),
                    'amount_minor' => $amountMinor,
                    'amount_display' => number_format($amountMinor / 100, 2),
                    'is_default' => (bool) ($price->is_default ?? false),
                ];
            })->values()->all();

            $defaultPrice = collect($prices)->firstWhere('is_default', true)
                ?? (count($prices) > 0 ? $prices[0] : null);

            $featureLabels = $this->extractEntitlementFeatureLabels($plan, $labelMap, $hiddenKeys);

            if (empty($featureLabels)) {
                $featureLabels = $this->flattenFeatureLabels((array) ($plan->features ?? []), $labelMap, $hiddenKeys);
            }

            if ($plan->isFree()) {
                $featureLabels = $this->defaultFreePlanFeatureLabels();
            }

            $isEligible = true;
            $ineligibleReason = null;

            if ($plan->availability_starts_on && $today->lt($plan->availability_starts_on->copy()->startOfDay())) {
                $isEligible = false;
                $ineligibleReason = 'Available on ' . $plan->availability_starts_on->format('M d, Y');
            }

            if ($plan->availability_ends_on && $today->gt($plan->availability_ends_on->copy()->endOfDay())) {
                $isEligible = false;
                $ineligibleReason = 'Enrollment period ended';
            }

            if ($isCurrentPlan) {
                $isEligible = false;
                $ineligibleReason = 'Current plan';
            } elseif ($hasActiveSubscription) {
                $isEligible = false;
                $ineligibleReason = 'Cancel current plan to switch';
            }

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'is_free' => $plan->isFree(),
                'is_current' => $isCurrentPlan,
                'is_eligible' => $isEligible,
                'ineligible_reason' => $ineligibleReason,
                'prices' => $prices,
                'default_price' => $defaultPrice,
                'feature_labels' => $featureLabels,
                'feature_keys' => array_keys($featureLabels),
                'is_baseline' => false,
            ];
        })->values();

        $recommendedPlanId = $cards
            ->first(fn ($card) => !$card['is_free'] && !$card['is_current'] && $card['is_eligible'])['id'] ?? null;

        return $cards->map(function ($card) use ($recommendedPlanId) {
            $card['is_recommended'] = $recommendedPlanId !== null && $card['id'] === $recommendedPlanId;
            return $card;
        })->all();
    }

    private function prependFreeBaselineCard(array $planCards, ?Subscription $currentSubscription): array
    {
        $hasFreePlan = collect($planCards)->contains(fn ($card) => (bool) ($card['is_free'] ?? false));

        if ($hasFreePlan) {
            return $planCards;
        }

        $hasPremiumSubscription = $currentSubscription && $currentSubscription->status === SubscriptionStatus::Active;

        $freeFeatureLabels = $this->defaultFreePlanFeatureLabels();

        $baselineCard = [
            'id' => -1,
            'name' => 'Free Plan',
            'slug' => 'free-baseline',
            'description' => 'Baseline learner access available to all accounts with no checkout required.',
            'is_free' => true,
            'is_current' => !$hasPremiumSubscription,
            'is_eligible' => false,
            'ineligible_reason' => !$hasPremiumSubscription ? 'Current baseline access' : 'Premium plan currently active',
            'prices' => [],
            'default_price' => null,
            'feature_labels' => $freeFeatureLabels,
            'feature_keys' => array_keys($freeFeatureLabels),
            'is_recommended' => false,
            'is_baseline' => true,
        ];

        return array_values(array_merge([$baselineCard], $planCards));
    }

    private function defaultFreePlanFeatureLabels(): array
    {
        return [
            'free_core_learning' => 'Access to core age-appropriate learning modules',
            'free_quiz_shields' => '3 quiz shields per day',
            'free_username_changes' => 'Username change every 7 days',
            'free_progress_tracking' => 'Track module progress and quiz completion history',
            'free_upgrade_anytime' => 'Upgrade any time for premium controls',
        ];
    }

    private function buildEntitlementHighlights(User $user): array
    {
        $hasUnlimitedUsernameChanges = $this->entitlementService->canAccessFeature(
            $user,
            SubscriptionFeatureKeys::UNLIMITED_USERNAME_CHANGE
        );

        $hasUnlimitedQuizShields = $this->entitlementService->canAccessFeature(
            $user,
            SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS
        );

        $canDownloadCertificates = $this->entitlementService->canAccessFeature(
            $user,
            SubscriptionFeatureKeys::DOWNLOADABLE_CERTIFICATES
        );

        return [
            [
                'key' => 'username_changes',
                'label' => 'Username changes',
                'value' => $hasUnlimitedUsernameChanges ? 'Unlimited' : 'Every 7 days',
                'description' => $hasUnlimitedUsernameChanges
                    ? 'Current plan removes username cooldown.'
                    : 'Free baseline applies one change every 7 days.',
                'is_enabled' => $hasUnlimitedUsernameChanges,
            ],
            [
                'key' => 'quiz_shields',
                'label' => 'Quiz shields',
                'value' => $hasUnlimitedQuizShields ? 'Unlimited' : '3 per day',
                'description' => $hasUnlimitedQuizShields
                    ? 'Retry quizzes without consuming daily shields.'
                    : 'Free baseline includes 3 shields and resets daily.',
                'is_enabled' => $hasUnlimitedQuizShields,
            ],
            [
                'key' => 'certificates',
                'label' => 'Certificate downloads',
                'value' => $canDownloadCertificates ? 'Included' : 'Upgrade required',
                'description' => $canDownloadCertificates
                    ? 'Download your completion certificates any time.'
                    : 'Certificate downloads unlock on supported premium plans.',
                'is_enabled' => $canDownloadCertificates,
            ],
        ];
    }

    /**
     * Flatten mixed feature formats to key => label map for rendering.
     */
    private function flattenFeatureLabels(array $features, array $labelMap, array $hiddenKeys): array
    {
        $output = [];

        foreach ($features as $groupOrIndex => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $innerValue) {
                    if (in_array($key, $hiddenKeys, true)) {
                        continue;
                    }

                    if ($innerValue === true || (is_numeric($innerValue) && (int) $innerValue > 0) || (is_string($innerValue) && !in_array(strtolower($innerValue), ['false', '0', 'off', ''], true))) {
                        $output[$key] = $labelMap[$key] ?? ucwords(str_replace('_', ' ', $key));
                    }
                }
                continue;
            }

            if (is_string($groupOrIndex) && !in_array($groupOrIndex, $hiddenKeys, true) && ($value === true || $value === 1 || $value === '1')) {
                $output[$groupOrIndex] = $labelMap[$groupOrIndex] ?? ucwords(str_replace('_', ' ', $groupOrIndex));
                continue;
            }

            if (is_int($groupOrIndex) && is_string($value) && !in_array($value, $hiddenKeys, true)) {
                $output[$value] = $labelMap[$value] ?? ucwords(str_replace('_', ' ', $value));
            }
        }

        return $output;
    }

    private function extractEntitlementFeatureLabels(SubscriptionPlan $plan, array $labelMap, array $hiddenKeys): array
    {
        $output = [];

        foreach (($plan->featureEntitlements ?? []) as $entitlement) {
            if (!(bool) ($entitlement->is_enabled ?? false)) {
                continue;
            }

            $feature = $entitlement->feature;
            $key = (string) ($feature?->key ?? '');

            if ($key === '' || in_array($key, $hiddenKeys, true)) {
                continue;
            }

            $name = (string) ($feature?->name ?? '');
            $label = $labelMap[$key] ?? ($name !== '' ? $name : $this->humanizeFeatureKey($key));

            $output[$key] = $label;
        }

        return $output;
    }

    private function humanizeFeatureKey(string $key): string
    {
        return ucwords(str_replace(['_', '-'], ' ', trim($key)));
    }

    /**
     * Build comparison row metadata for the feature matrix.
     */
    private function buildComparisonFeatures(array $planCards): array
    {
        $features = [];

        foreach ($planCards as $card) {
            foreach (($card['feature_labels'] ?? []) as $featureKey => $featureLabel) {
                if (!isset($features[$featureKey])) {
                    $features[$featureKey] = [
                        'key' => $featureKey,
                        'label' => $featureLabel,
                    ];
                }
            }
        }

        $priorityFeatures = [
            SubscriptionFeatureKeys::UNLIMITED_USERNAME_CHANGE => 'Unlimited Username Changes',
            SubscriptionFeatureKeys::UNLIMITED_QUIZ_SHIELDS => 'Unlimited Quiz Shields',
            SubscriptionFeatureKeys::DOWNLOADABLE_CERTIFICATES => 'Downloadable Certificates',
        ];

        foreach ($priorityFeatures as $featureKey => $featureLabel) {
            $availableInPaidPlans = collect($planCards)->contains(function ($card) use ($featureKey) {
                return !($card['is_free'] ?? false)
                    && in_array($featureKey, $card['feature_keys'] ?? [], true);
            });

            if ($availableInPaidPlans && !isset($features[$featureKey])) {
                $features[$featureKey] = [
                    'key' => $featureKey,
                    'label' => $featureLabel,
                ];
            }
        }

        return array_values($features);
    }
}
