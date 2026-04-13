<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\FeatureCatalog;
use App\Models\Payment;
use App\Models\PlanPrice;
use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Events\SubscriptionCreated;
use App\Services\PayMongoPaymentLinkService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SubscriptionService
 *
 * Acts as the single authoritative layer for all subscription state mutations.
 * Controllers and webhook handlers delegate to this service; they never write
 * subscription or payment records directly.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * KEY DESIGN DECISIONS
 * ─────────────────────────────────────────────────────────────────────────
 *
 *  1. IDEMPOTENT ACTIVATION
 *     activate() is safe to call multiple times. If the subscription is already
 *     'active', it returns immediately without side effects. This is critical
 *     because activation can be triggered from three different code paths:
 *       • PaymentObserver::updated()  (immediate, DB transaction)
 *       • PaymentController::webhook() (PayMongo webhook event)
 *       • SubscriptionController::index() (API-polling fallback on return)
 *
 *  2. EVENT-DRIVEN POST-ACTIVATION
 *     After activation, a SubscriptionCreated event is dispatched. Listeners:
 *       • HandleSubscriptionCreated → queues SendSubscriptionWelcomeEmail
 *       • HandlePaymentSuccessful   → queues GenerateInvoiceJob
 *     This keeps the service thin and avoids deeply coupled notifications.
 *
 *  3. NO HARDCODED PRICES
 *     All monetary values are sourced from the SubscriptionPlan model at runtime.
 *     The only exception is createLegacy() which falls back to config/billing.php
 *     when no SubscriptionPlan record exists.
 *
 *  4. DOUBLE-PAYMENT PREVENTION
 *     create() cancels all dangling 'pending' subscriptions for the same user
 *     before creating a new one, preventing duplicate transaction records.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * SUBSCRIPTION STATES
 * ─────────────────────────────────────────────────────────────────────────
 *
 *   pending    → active    (payment confirmed)
 *   active     → cancelled (user request or refund)
 *   active     → expired   (end_date passed, via ExpireSubscriptions command)
 *   active     → past_due  (payment failed on renewal, grace period starts)
 *   cancelled  → active    (user renews within billing period)
 *
 * @see \App\Models\Subscription
 * @see \App\Models\SubscriptionPlan
 * @see \App\Observers\PaymentObserver
 * @see \App\Events\SubscriptionCreated
 * @see \App\Console\Commands\ExpireSubscriptions
 */
class SubscriptionService
{
    // No constructor dependencies — slow operations delegated to queued jobs

    // ─────────────────────────────────────────────────────────────────────────
    // QUERIES
    // ─────────────────────────────────────────────────────────────────────────

    public function getActiveSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->latest()
            ->first();
    }

    public function getCurrentPlan(User $user): ?SubscriptionPlan
    {
        $subscription = $this->getActiveSubscription($user);

        if ($subscription && $subscription->plan_id) {
            return SubscriptionPlan::find($subscription->plan_id);
        }

        return null;
    }

    public function isUserPremium(User $user): bool
    {
        return $this->getEligibleSubscriptionForEntitlements($user) !== null;
    }

    public function resolveEffectiveEndTimestamp(Subscription $subscription): ?CarbonInterface
    {
        $normalized = $subscription->ends_at;
        if ($normalized instanceof CarbonInterface) {
            return $normalized;
        }

        $legacy = $subscription->end_date;
        if ($legacy instanceof CarbonInterface) {
            return $legacy;
        }

        if ($legacy) {
            return Carbon::parse((string) $legacy);
        }

        return null;
    }

    public function reconcileLifecycleForUser(User $user, ?CarbonInterface $referenceTime = null): void
    {
        $referenceTime ??= now();

        $user->subscriptions()
            ->whereIn('status', [
                SubscriptionStatus::Active->value,
                SubscriptionStatus::GracePeriod->value,
                SubscriptionStatus::ScheduledCancel->value,
            ])
            ->orderByDesc('id')
            ->get()
            ->each(fn (Subscription $subscription) => $this->reconcileLifecycleState($subscription, $referenceTime));
    }

    public function reconcileLifecycleState(Subscription $subscription, ?CarbonInterface $referenceTime = null): Subscription
    {
        $referenceTime ??= now();
        $status = (string) $subscription->status->value;
        $effectiveEnd = $this->resolveEffectiveEndTimestamp($subscription);
        $graceEndsAt = $subscription->grace_ends_at ?? $subscription->grace_period_ends;

        if (
            in_array($status, [SubscriptionStatus::Active->value, SubscriptionStatus::ScheduledCancel->value], true)
            && $effectiveEnd
            && $effectiveEnd->lte($referenceTime)
        ) {
            $subscription->update([
                'status' => SubscriptionStatus::Expired,
                'auto_renew' => false,
            ]);

            return $subscription->fresh();
        }

        if ($status === SubscriptionStatus::GracePeriod->value && $graceEndsAt && $graceEndsAt->lte($referenceTime)) {
            $subscription->update([
                'status' => SubscriptionStatus::Expired,
                'auto_renew' => false,
                'grace_ends_at' => null,
                'grace_period_ends' => null,
            ]);

            return $subscription->fresh();
        }

        return $subscription;
    }

    public function getRenewalWarningDays(Subscription $subscription): int
    {
        $plan = $this->resolvePlanForSubscription($subscription);
        $configured = $plan?->renewal_warning_days;
        if (is_numeric($configured)) {
            return max(0, (int) $configured);
        }

        return max(0, (int) config('billing.subscription.renewal_warning_days', 7));
    }

    public function getRenewalStatus(Subscription $subscription, ?CarbonInterface $referenceTime = null): array
    {
        $referenceTime ??= now();
        $subscription = $this->reconcileLifecycleState($subscription, $referenceTime);
        $effectiveEnd = $this->resolveEffectiveEndTimestamp($subscription);
        $warningDays = $this->getRenewalWarningDays($subscription);

        if (!$effectiveEnd) {
            return [
                'is_expired' => false,
                'is_expiring_soon' => false,
                'is_renewable_now' => false,
                'warning_days' => $warningDays,
                'days_remaining' => null,
                'effective_end_at' => null,
            ];
        }

        $isExpired = $effectiveEnd->lte($referenceTime);
        $isExpiringSoon = !$isExpired && $effectiveEnd->lte($referenceTime->copy()->addDays($warningDays));

        return [
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'is_renewable_now' => $this->isRenewableNow($subscription, $referenceTime),
            'warning_days' => $warningDays,
            'days_remaining' => max(0, (int) $referenceTime->diffInDays($effectiveEnd, false)),
            'effective_end_at' => $effectiveEnd,
        ];
    }

    public function isRenewableNow(Subscription $subscription, ?CarbonInterface $referenceTime = null): bool
    {
        $referenceTime ??= now();
        $subscription = $this->reconcileLifecycleState($subscription, $referenceTime);
        $status = (string) $subscription->status->value;
        $effectiveEnd = $this->resolveEffectiveEndTimestamp($subscription);

        if (!$effectiveEnd) {
            return false;
        }

        $warningDays = $this->getRenewalWarningDays($subscription);

        $isExpired = $effectiveEnd->lte($referenceTime);
        if ($isExpired) {
            return in_array($status, [
                SubscriptionStatus::Expired->value,
                SubscriptionStatus::Cancelled->value,
                SubscriptionStatus::Active->value,
                SubscriptionStatus::ScheduledCancel->value,
            ], true);
        }

        $isExpiringSoon = $effectiveEnd->lte($referenceTime->copy()->addDays($warningDays));
        if ($isExpiringSoon) {
            return in_array($status, [
                SubscriptionStatus::Active->value,
                SubscriptionStatus::ScheduledCancel->value,
                SubscriptionStatus::Cancelled->value,
            ], true);
        }

        return false;
    }

    public function getEligibleSubscriptionForEntitlements(User $user): ?Subscription
    {
        $referenceTime = now();
        $this->reconcileLifecycleForUser($user, $referenceTime);

        $candidates = $user->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::GracePeriod->value])
            ->orderByDesc('id')
            ->get();

        foreach ($candidates as $candidate) {
            $candidate = $this->reconcileLifecycleState($candidate, $referenceTime);

            if ((string) $candidate->status->value === SubscriptionStatus::GracePeriod->value) {
                $graceEndsAt = $candidate->grace_ends_at ?? $candidate->grace_period_ends;
                if ($graceEndsAt && $graceEndsAt->gt($referenceTime)) {
                    return $candidate;
                }

                continue;
            }

            $effectiveEnd = $this->resolveEffectiveEndTimestamp($candidate);
            if (!$effectiveEnd || $effectiveEnd->gt($referenceTime)) {
                return $candidate;
            }
        }

        return null;
    }

    public function hasFeature(User $user, string $featureKey): bool
    {
        $entitlement = $this->resolveEntitlement($user, $featureKey);

        if (!$entitlement || !$entitlement->is_enabled) {
            return false;
        }

        if ($entitlement->is_unlimited) {
            return true;
        }

        $feature = $entitlement->feature;
        if ($feature?->value_type === 'quota') {
            return (int) ($entitlement->quota_value ?? 0) > 0;
        }

        return true;
    }

    public function getFeatureQuota(User $user, string $featureKey): ?int
    {
        $entitlement = $this->resolveEntitlement($user, $featureKey);

        if (!$entitlement || !$entitlement->is_enabled) {
            return null;
        }

        if ($entitlement->is_unlimited) {
            return null;
        }

        if ($entitlement->feature?->value_type !== 'quota') {
            return null;
        }

        return $entitlement->quota_value;
    }

    private function resolveEntitlement(User $user, string $featureKey): ?PlanFeatureEntitlement
    {
        $subscription = $this->getEligibleSubscriptionForEntitlements($user);

        if (!$subscription || !$subscription->plan_id) {
            return null;
        }

        $featureKeys = $this->featureAliases($featureKey);

        $featureIds = FeatureCatalog::query()
            ->whereIn('key', $featureKeys)
            ->where('is_active', true)
            ->pluck('id');

        if ($featureIds->isEmpty()) {
            return null;
        }

        return PlanFeatureEntitlement::query()
            ->with('feature')
            ->where('plan_id', $subscription->plan_id)
            ->whereIn('feature_id', $featureIds)
            ->where('is_enabled', true)
            ->orderByDesc('is_unlimited')
            ->orderByDesc('quota_value')
            ->first();
    }

    private function featureAliases(string $featureKey): array
    {
        $aliases = [
            'unlimited_username_change' => [
                'unlimited_username_change',
                'unlimited_username_changes',
            ],
            'unlimited_quiz_shields' => [
                'unlimited_quiz_shields',
                'unlimited_shields',
                'unlimited_quiz_retaking',
                'unlimited_quizzes',
            ],
            'downloadable_certificates' => [
                'downloadable_certificates',
                'certificate_pdf_download_access',
                'certificate_pdf_download',
                'certificates',
            ],
        ];

        return $aliases[$featureKey] ?? [$featureKey];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MUTATIONS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a new pending subscription + payment record.
     * The subscription stays 'pending' until payment is confirmed via webhook.
     *
     * @throws \RuntimeException
     */
    public function create(User $user, SubscriptionPlan $plan): Subscription
    {
        if (!$plan->is_active) {
            throw new \RuntimeException("Plan '{$plan->name}' is not available.");
        }

        // Cancel any dangling pending subscriptions to avoid orphans
        $user->subscriptions()->where('status', 'pending')->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => 'Superseded by new subscription attempt',
        ]);

        $defaultPrice = $plan->defaultPlanPrice()->first()
            ?? $plan->planPrices()->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->first();

        $price = $defaultPrice ? ((int) $defaultPrice->amount_minor) / 100 : $plan->getPrice();
        $startDate = now();
        $trialDays = (int) ($plan->trial_days ?? 0);
        $trialEnds = $this->resolveTrialEndDate($plan, $startDate);

        // End date — support short-duration test plans via 'duration_minutes' feature
        if ($defaultPrice) {
            $endDate = $this->resolvePlanEndDate(
                $startDate,
                (string) $defaultPrice->duration_unit,
                (int) $defaultPrice->duration_count
            );
        } elseif ($plan->hasFeature('duration_minutes')) {
            $durationMinutes = (int) $plan->getFeatureValue('duration_minutes', 10);
            $endDate = $startDate->copy()->addMinutes($durationMinutes);
        } elseif ($trialDays > 0) {
            $endDate = $startDate->copy()->addDays($trialDays);
        } else {
            $endDate = $startDate->copy()->addMonth();
        }

        DB::beginTransaction();

        try {
            $subscription = Subscription::create([
                'user_id'       => $user->id,
                'plan_id'       => $plan->id,
                'plan_price_id' => $defaultPrice?->id,
                'plan'          => $plan->slug,
                'status'        => 'pending',
                'start_date'    => $startDate,
                'end_date'      => $endDate,
                'starts_at'     => $startDate,
                'ends_at'       => $endDate,
                'next_billing_at' => $endDate,
                'trial_ends_at' => $trialEnds,
                'price_paid'    => $price,
                'auto_renew'    => true,
            ]);

            $subscription->payments()->create([
                'user_id'         => $user->id,
                'amount'          => $price,
                // Method is selected later in PaymentController::process().
                'method'          => null,
                'status'          => 'pending',
                'transaction_id'  => 'TXN-' . strtoupper(uniqid()),
                'payment_details' => [
                    'payment_scope' => 'subscription',
                    'plan_id'     => $plan->id,
                    'plan_name'   => $plan->name,
                    'created_via' => 'subscription_service',
                ],
            ]);

            DB::commit();

            Log::info('Subscription created', [
                'subscription_id' => $subscription->id,
                'user_id'         => $user->id,
                'plan'            => $plan->slug,
            ]);

            return $subscription;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubscriptionService::create failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to create subscription: ' . $e->getMessage(), 0, $e);
        }
    }

    private function resolvePlanEndDate(CarbonInterface $startDate, string $durationUnit, int $durationCount): CarbonInterface
    {
        $count = max(1, $durationCount);
        $normalizedDurationUnit = $this->normalizeDurationUnit($durationUnit);

        return match ($normalizedDurationUnit) {
            'minute' => $startDate->copy()->addMinutes($count),
            'hour' => $startDate->copy()->addHours($count),
            'day' => $startDate->copy()->addDays($count),
            'week' => $startDate->copy()->addWeeks($count),
            'year' => $startDate->copy()->addYears($count),
            default => $startDate->copy()->addMonths($count),
        };
    }

    private function normalizeDurationUnit(string $durationUnit): string
    {
        $unit = strtolower(trim($durationUnit));

        return match ($unit) {
            'minutes' => 'minute',
            'hours' => 'hour',
            'days' => 'day',
            'weeks' => 'week',
            'months' => 'month',
            'years' => 'year',
            default => $unit !== '' ? rtrim($unit, 's') : 'month',
        };
    }

    private function resolveTrialEndDate(?SubscriptionPlan $plan, CarbonInterface $startDate): ?CarbonInterface
    {
        if (!$plan) {
            return null;
        }

        $trialDays = (int) ($plan->trial_days ?? 0);

        if ($trialDays <= 0) {
            return null;
        }

        return $startDate->copy()->addDays($trialDays);
    }

    private function resolvePlanForSubscription(Subscription $subscription): ?SubscriptionPlan
    {
        if ($subscription->relationLoaded('plan')) {
            $loadedPlan = $subscription->getRelation('plan');
            if ($loadedPlan instanceof SubscriptionPlan) {
                return $loadedPlan;
            }
        }

        if (!$subscription->plan_id) {
            return null;
        }

        return SubscriptionPlan::find($subscription->plan_id);
    }

    private function resolvePlanPriceForSubscription(Subscription $subscription): ?PlanPrice
    {
        if ($subscription->relationLoaded('planPrice') && $subscription->planPrice) {
            return $subscription->planPrice;
        }

        if ($subscription->plan_price_id) {
            $linkedPrice = $subscription->planPrice()->first();
            if ($linkedPrice) {
                return $linkedPrice;
            }
        }

        $plan = $this->resolvePlanForSubscription($subscription);

        if (!$plan) {
            return null;
        }

        return $plan->defaultPlanPrice()->first()
            ?? $plan->planPrices()->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->first();
    }

    private function resolveSubscriptionEndDate(Subscription $subscription, CarbonInterface $startDate): CarbonInterface
    {
        $planPrice = $this->resolvePlanPriceForSubscription($subscription);

        if ($planPrice) {
            return $this->resolvePlanEndDate(
                $startDate,
                (string) $planPrice->duration_unit,
                (int) $planPrice->duration_count
            );
        }

        $plan = $this->resolvePlanForSubscription($subscription);

        if ($plan?->hasFeature('duration_minutes')) {
            $durationMinutes = (int) $plan->getFeatureValue('duration_minutes', 10);
            return $startDate->copy()->addMinutes(max(1, $durationMinutes));
        }

        $trialEndDate = $this->resolveTrialEndDate($plan, $startDate);
        if ($trialEndDate) {
            return $trialEndDate;
        }

        return $startDate->copy()->addMonth();
    }

    private function resolveRenewalChargeAmount(Subscription $subscription, ?PlanPrice $planPrice = null): float
    {
        $resolvedPlanPrice = $planPrice ?? $this->resolvePlanPriceForSubscription($subscription);

        if ($resolvedPlanPrice) {
            return round(((int) $resolvedPlanPrice->amount_minor) / 100, 2);
        }

        $plan = $this->resolvePlanForSubscription($subscription);
        if ($plan) {
            return round((float) $plan->getPrice(), 2);
        }

        return round((float) ($subscription->price_paid ?? 0), 2);
    }

    /**
     * Activate a pending subscription and fire the SubscriptionCreated event.
     * Invoice generation is handled asynchronously via the HandlePaymentSuccessful listener.
     *
     * @throws \RuntimeException
     */
    public function activate(Subscription $subscription): void
    {
        if ($subscription->status === SubscriptionStatus::Active) {
            return; // idempotent
        }

        $activationDate = now();
        $endDate = $this->resolveSubscriptionEndDate($subscription, $activationDate);
        $plan = $this->resolvePlanForSubscription($subscription);
        $planPrice = $this->resolvePlanPriceForSubscription($subscription);
        $trialEndsAt = $this->resolveTrialEndDate($plan, $activationDate);

        DB::beginTransaction();

        try {
            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'plan_price_id' => $planPrice?->id ?? $subscription->plan_price_id,
                'start_date' => $activationDate,
                'end_date' => $endDate,
                'starts_at' => $activationDate,
                'ends_at' => $endDate,
                'next_billing_at' => $endDate,
                'trial_ends_at' => $trialEndsAt,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'auto_renew' => true,
            ]);

            DB::commit();

            event(new SubscriptionCreated($subscription));

            Log::info('Subscription activated', [
                'subscription_id' => $subscription->id,
                'user_id'         => $subscription->user_id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubscriptionService::activate failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to activate subscription: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Cancel an active subscription.
     *
     * @throws \RuntimeException
     */
    public function cancel(Subscription $subscription, ?string $reason = null): void
    {
        if (!$subscription->canCancel()) {
            throw new \RuntimeException('This subscription cannot be cancelled.');
        }

        DB::beginTransaction();

        try {
            $subscription->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => $reason,
                'auto_renew'          => false,
            ]);

            DB::commit();

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'user_id'         => $subscription->user_id,
                'reason'          => $reason,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubscriptionService::cancel failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to cancel subscription: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Renew a cancelled subscription (reactivate in place).
     *
     * @throws \RuntimeException
     */
    public function renew(Subscription $subscription): void
    {
        $subscription = $this->reconcileLifecycleState($subscription->fresh());

        if (!$this->isRenewableNow($subscription)) {
            throw new \RuntimeException('This subscription cannot be renewed right now.');
        }

        $renewedAt = now();
        $existingEndDate = $this->resolveEffectiveEndTimestamp($subscription);
        $extensionAnchor = $existingEndDate && $existingEndDate->isFuture()
            ? $existingEndDate->copy()
            : $renewedAt->copy();
        $newEndDate = $this->resolveSubscriptionEndDate($subscription, $extensionAnchor);
        $planPrice = $this->resolvePlanPriceForSubscription($subscription);
        $renewalAmount = $this->resolveRenewalChargeAmount($subscription, $planPrice);
        $renewalReference = 'REN-' . strtoupper(uniqid());
        $renewedFromStatus = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status->value
            : (string) $subscription->status;

        DB::beginTransaction();

        try {
            $subscription->update([
                'status'              => 'active',
                'cancelled_at'        => null,
                'cancellation_reason' => null,
                'start_date'          => $renewedAt,
                'end_date'            => $newEndDate,
                'starts_at'           => $renewedAt,
                'ends_at'             => $newEndDate,
                'next_billing_at'     => $newEndDate,
                'plan_price_id'       => $planPrice?->id ?? $subscription->plan_price_id,
                'price_paid'          => $renewalAmount,
                'trial_ends_at'       => null,
                'auto_renew'          => true,
                'source_provider'     => 'manual_renewal',
                'source_reference'    => $renewalReference,
            ]);

            $renewalPayment = $subscription->payments()->create([
                'user_id' => $subscription->user_id,
                'amount' => $renewalAmount,
                'method' => null,
                'status' => PaymentStatus::Completed,
                'transaction_id' => $renewalReference,
                'payment_details' => [
                    'payment_scope' => 'subscription',
                    'lifecycle_action' => 'renewal',
                    'renewed_from_status' => $renewedFromStatus,
                    'renewed_at' => $renewedAt->toDateTimeString(),
                    'created_via' => 'subscription_service',
                ],
                'paid_at' => $renewedAt,
            ]);

            DB::commit();

            Log::info('Subscription renewed', [
                'subscription_id' => $subscription->id,
                'user_id'         => $subscription->user_id,
                'new_end_date'    => $newEndDate,
                'payment_id'      => $renewalPayment->id,
                'renewal_amount'  => $renewalAmount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubscriptionService::renew failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to renew subscription: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Expire an active subscription that has passed its end date.
     *
     * @throws \RuntimeException
     */
    public function expire(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            return;
        }

        DB::beginTransaction();

        try {
            $subscription->update(['status' => SubscriptionStatus::Expired]);

            DB::commit();

            Log::info('Subscription expired', [
                'subscription_id' => $subscription->id,
                'user_id'         => $subscription->user_id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubscriptionService::expire failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to expire subscription: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Mark an active subscription to cancel at the end of the current period.
     */
    public function scheduleCancelAtPeriodEnd(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            throw new \RuntimeException('Only active subscriptions can be scheduled for cancellation.');
        }

        $cancelAt = $subscription->ends_at ?? $subscription->end_date ?? now();

        $subscription->update([
            'status' => SubscriptionStatus::ScheduledCancel,
            'cancel_at' => $cancelAt,
            'auto_renew' => false,
        ]);
    }

    /**
     * Move an active subscription into grace period after a failed renewal.
     */
    public function moveToGracePeriod(Subscription $subscription, ?CarbonInterface $graceEndsAt = null): void
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            throw new \RuntimeException('Only active subscriptions can enter grace period.');
        }

        $graceEndsAt ??= now()->addDays(7);

        $subscription->update([
            'status' => SubscriptionStatus::GracePeriod,
            'grace_ends_at' => $graceEndsAt,
            'grace_period_ends' => $graceEndsAt,
        ]);
    }

    /**
     * Recover a grace-period subscription back to active after successful payment.
     */
    public function recoverFromGracePeriod(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::GracePeriod) {
            throw new \RuntimeException('Only grace period subscriptions can be recovered.');
        }

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'grace_ends_at' => null,
            'grace_period_ends' => null,
        ]);
    }

    /**
     * Switch an active subscription to a different plan.
     * Cancels the current subscription and creates a new pending one.
     *
     * @throws \RuntimeException
     */
    public function switchPlan(Subscription $current, SubscriptionPlan $newPlan): Subscription
    {
        if (!$current->isActive()) {
            throw new \RuntimeException('Only active subscriptions can be switched.');
        }

        DB::beginTransaction();

        try {
            // Cancel current subscription immediately
            $current->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => "Switched to plan: {$newPlan->name}",
                'auto_renew'          => false,
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \RuntimeException('Failed to cancel existing plan during switch: ' . $e->getMessage(), 0, $e);
        }

        // Create the new subscription (has its own transaction internally)
        $user = $current->user;
        return $this->create($user, $newPlan);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAYMONGO INTEGRATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a pending subscription and attach a PayMongo payment link in one step.
     * Delegates core creation to create(), then patches the payment record with the
     * checkout URL so the controller stays as a thin HTTP layer.
     *
     * @return array{subscription: Subscription, checkout_url: string}
     * @throws \RuntimeException
     */
    public function createWithPayMongoLink(
        User $user,
        SubscriptionPlan $plan,
        PayMongoPaymentLinkService $paymongoService
    ): array {
        $subscription = $this->create($user, $plan);

        /** @var Payment $payment */
        $payment = $subscription->payments()->latest()->first();

        $checkoutUrl = $paymongoService->createSubscriptionLink(
            $user->id,
            $subscription->id,
            (float) $plan->price,
            $plan->name
        );

        $payment->update([
            'method'          => 'paymongo',
            'payment_details' => array_merge((array) $payment->payment_details, [
                'checkout_url' => $checkoutUrl,
                'created_via'  => 'paymongo_payment_link',
            ]),
        ]);

        Log::info('PayMongo payment link created', [
            'subscription_id' => $subscription->id,
            'user_id'         => $user->id,
        ]);

        return ['subscription' => $subscription, 'checkout_url' => $checkoutUrl];
    }

    /**
     * Legacy path: no SubscriptionPlan record exists.
     * Creates an immediately-active subscription with a PayMongo payment link.
     *
     * @param string $planType 'monthly' | 'annual'
     * @return array{subscription: Subscription, checkout_url: string}
     * @throws \RuntimeException
     */
    public function createLegacy(
        User $user,
        PayMongoPaymentLinkService $paymongoService
    ): array {
        // Prices read from config so no value is hardcoded in source code.
        $plans  = config('billing.subscription.plans', []);
        $amount = (float) ($plans['premium']['price'] ?? 299.00);
        $endDate = now()->addMonth();

        DB::beginTransaction();

        try {
            $startDate = now();

            $subscription = Subscription::create([
                'user_id'    => $user->id,
                'plan'       => 'premium',
                'status'     => 'active',
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'starts_at'  => $startDate,
                'ends_at'    => $endDate,
                'next_billing_at' => $endDate,
                'price_paid' => $amount,
            ]);

            $checkoutUrl = $paymongoService->createSubscriptionLink(
                $user->id, $subscription->id, $amount, 'Premium'
            );

            $subscription->payments()->create([
                'user_id'         => $user->id,
                'amount'          => $amount,
                'method'          => 'paymongo',
                'status'          => 'completed',
                'transaction_id'  => 'PL-' . strtoupper(uniqid()),
                'payment_details' => [
                    'payment_scope'       => 'subscription',
                    'created_via'         => 'paymongo_payment_link',
                    'legacy_subscription' => true,
                    'checkout_url'        => $checkoutUrl,
                ],
                'paid_at' => now(),
            ]);

            DB::commit();

            Log::info('Legacy subscription created', [
                'subscription_id' => $subscription->id,
                'user_id'         => $user->id,
            ]);

            return ['subscription' => $subscription, 'checkout_url' => $checkoutUrl];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubscriptionService::createLegacy failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to create legacy subscription: ' . $e->getMessage(), 0, $e);
        }
    }
}
