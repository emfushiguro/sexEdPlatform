<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Events\SubscriptionCreated;
use App\Services\PayMongoPaymentLinkService;
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
        return (bool) $user->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>', now());
            })
            ->exists();
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

        $price     = $plan->getPrice();
        $startDate = now();
        $trialDays = $plan->trial_days ?? 0;
        $trialEnds = $trialDays > 0 ? $startDate->copy()->addDays($trialDays) : null;

        // End date — support short-duration test plans via 'duration_minutes' feature
        if ($plan->hasFeature('duration_minutes')) {
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
                'plan'          => $plan->slug,
                'status'        => 'pending',
                'start_date'    => $startDate,
                'end_date'      => $endDate,
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

        DB::beginTransaction();

        try {
            $subscription->update(['status' => SubscriptionStatus::Active]);

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
        if (!$subscription->canRenew()) {
            throw new \RuntimeException('This subscription cannot be renewed.');
        }

        $newEndDate = now()->addMonth();

        DB::beginTransaction();

        try {
            $subscription->update([
                'status'              => 'active',
                'cancelled_at'        => null,
                'cancellation_reason' => null,
                'end_date'            => $newEndDate,
                'auto_renew'          => true,
            ]);

            DB::commit();

            Log::info('Subscription renewed', [
                'subscription_id' => $subscription->id,
                'user_id'         => $subscription->user_id,
                'new_end_date'    => $newEndDate,
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
            $subscription = Subscription::create([
                'user_id'    => $user->id,
                'plan'       => 'premium',
                'status'     => 'active',
                'start_date' => now(),
                'end_date'   => $endDate,
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
