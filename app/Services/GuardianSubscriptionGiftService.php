<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Events\PaymentSuccessful;
use App\Jobs\SendSubscriptionWelcomeEmail;
use App\Models\ParentChildAccount;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\Admin\NewPaymentTransactionNotification;
use App\Notifications\Admin\NewSubscriptionPurchaseNotification;
use App\Notifications\Learner\SubscriptionResultNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuardianSubscriptionGiftService
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly LearnerSubscriptionPlanCatalog $catalog,
    ) {}

    public static function isGift(Payment $payment): bool
    {
        return is_array(data_get($payment->payment_details, 'gift'));
    }

    public static function unresolvedPayments(User $dependent): \Illuminate\Database\Eloquent\Builder
    {
        return Payment::query()->whereHas('subscription', fn ($query) => $query->where('user_id', $dependent->id))
            ->where(function ($query) {
                $query->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])
                    ->orWhere(function ($query) {
                        $query->whereNotNull('payment_details->gift')->whereNull('payment_details->gift->applied_at')
                            ->where(function ($query) {
                                $query->where('status', PaymentStatus::Completed)
                                    ->orWhere(function ($query) {
                                        $query->whereNotNull('payment_details->paymongo_checkout_session_id')
                                            ->where(function ($query) {
                                                $query->whereNull('payment_details->gift->checkout_state')
                                                    ->orWhere('payment_details->gift->checkout_state', '!=', 'expired');
                                            });
                                    })
                                    ->orWhereIn('payment_details->gift->checkout_state', ['creating', 'unknown']);
                            });
                    });
            });
    }

    public function authorizePayment(Payment $payment, User $actor, bool $management = true): void
    {
        abort_unless(self::isGift($payment) && $payment->user_id === $actor->id, 403);
        if ($management) {
            $dependent = $payment->subscription?->user;
            abort_unless($dependent && ParentChildAccount::query()
                ->whereKey(data_get($payment->payment_details, 'gift.relationship_id'))
                ->where('parent_user_id', $actor->id)->where('child_user_id', $dependent->id)->exists(), 403);
            Gate::forUser($actor)->authorize('giftSubscription', $dependent);
        }
    }

    public function currentSubscriptions(User $dependent, bool $lockForUpdate = false): Collection
    {
        $query = $dependent->subscriptions()->whereNull('connector_id')
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::ScheduledCancel, SubscriptionStatus::GracePeriod])
            ->with('plan');
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()->filter(function (Subscription $subscription) {
            $end = $this->subscriptions->resolveEffectiveEndTimestamp($subscription);

            return ! $end || $end->isFuture() || $subscription->status === SubscriptionStatus::GracePeriod;
        })->sortByDesc(fn (Subscription $subscription) => $this->subscriptions->resolveEffectiveEndTimestamp($subscription)?->getTimestamp() ?? 0)->values();
    }

    public function canRenewSubscription(Subscription $subscription, Collection $currentSubscriptions): bool
    {
        return $currentSubscriptions->isNotEmpty()
            && $currentSubscriptions->first()->id === $subscription->id
            && ! $currentSubscriptions->contains(fn (Subscription $candidate) => $candidate->plan_id !== $subscription->plan_id
                || ! $this->subscriptions->resolveEffectiveEndTimestamp($candidate)
                || $candidate->status === SubscriptionStatus::GracePeriod)
            && $this->subscriptions->isRenewableNow($subscription);
    }

    /** Reserve before the external request so an uncertain response cannot produce a second payable session. */
    public function reserveCheckout(Payment $payment, User $guardian): array
    {
        $reservation = DB::transaction(function () use ($payment, $guardian) {
            User::query()->whereKey($payment->subscription->user_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $this->authorizePayment($payment, $guardian);
            $details = (array) $payment->payment_details;
            if (isset($details['paymongo_checkout_session_id']) || $payment->status !== PaymentStatus::Pending
                || in_array(data_get($details, 'gift.checkout_state'), ['creating', 'unknown'], true)) {
                return ['reserved' => false, 'checkout_url' => $payment->status === PaymentStatus::Pending
                    ? ($details['checkout_url'] ?? null) : null];
            }
            $subscription = $payment->subscription;
            $plan = SubscriptionPlan::active()->where('plan_audience', 'learner')->find($subscription->plan_id);
            if (! $plan || ! $this->catalog->isAvailable($plan) || ! $this->catalog->effectivePrice($plan)) {
                $details['gift']['checkout_state'] = 'unavailable';
                $payment->update(['status' => PaymentStatus::Cancelled, 'payment_details' => $details]);

                return ['reserved' => false, 'unavailable' => true];
            }
            $currentSubscriptions = $this->currentSubscriptions($subscription->user, lockForUpdate: true);
            if ($currentSubscriptions->isNotEmpty() && ! $this->canRenewSubscription($subscription, $currentSubscriptions)) {
                throw ValidationException::withMessages(['plan_id' => 'This dependent already has an active subscription. Renewal is not available yet.']);
            }
            $details['gift']['checkout_state'] = 'creating';
            $payment->update(['payment_details' => $details]);

            return ['reserved' => true];
        });

        // Commit the unstarted checkout's closure before returning the validation error.
        if ($reservation['unavailable'] ?? false) {
            throw ValidationException::withMessages(['plan_id' => 'This plan is no longer available. Choose an available learner plan.']);
        }

        return $reservation;
    }

    public function verifyAndCompletePendingPayment(Payment $payment): bool
    {
        $payment->refresh();
        if (! self::isGift($payment)) {
            return false;
        }
        if (data_get($payment->payment_details, 'gift.applied_at')) {
            $this->dispatchFollowUps($payment->id);

            return $payment->isCompleted();
        }
        if ($payment->status === PaymentStatus::Cancelled
            && data_get($payment->payment_details, 'gift.checkout_state') === 'expired') {
            return false;
        }

        $sessionId = (string) data_get($payment->payment_details, 'paymongo_checkout_session_id', '');
        if (! str_starts_with($sessionId, 'cs_')) {
            return false;
        }
        $response = app(PayMongoPaymentLinkService::class)->retrieveCheckoutSession($sessionId);
        $session = $response['data'] ?? [];
        if (! collect(data_get($session, 'attributes.payments', []))
            ->contains(fn ($item) => data_get($item, 'attributes.status') === 'paid')) {
            if (data_get($session, 'attributes.status') === 'expired'
                && data_get($session, 'id') === $sessionId
                && data_get($session, 'attributes.livemode') === data_get($payment->payment_details, 'gift.livemode', false)) {
                DB::transaction(function () use ($payment, $sessionId) {
                    User::query()->whereKey($payment->subscription->user_id)->lockForUpdate()->firstOrFail();
                    $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);
                    if (data_get($locked->payment_details, 'paymongo_checkout_session_id') !== $sessionId
                        || data_get($locked->payment_details, 'gift.applied_at')
                        || in_array($locked->status, [PaymentStatus::Completed, PaymentStatus::Refunded], true)) {
                        return;
                    }
                    $details = (array) $locked->payment_details;
                    $details['gift']['checkout_state'] = 'expired';
                    $details['gift']['closed_at'] = now()->toIso8601String();
                    $locked->update(['status' => PaymentStatus::Cancelled, 'payment_details' => $details]);
                });
                $payment->refresh();
            }

            return false;
        }

        $this->completeFromVerifiedSession($payment, $session, data_get($session, 'attributes.livemode'));

        return $payment->fresh()->isCompleted();
    }

    public function completeFromWebhook(array $event): void
    {
        $session = data_get($event, 'attributes.data', []);
        $metadata = data_get($session, 'attributes.metadata', []);
        $payment = Payment::query()->find($metadata['payment_id'] ?? 0);
        if (! $payment || empty($event['id'])
            || data_get($event, 'attributes.type') !== 'checkout_session.payment.paid') {
            throw ValidationException::withMessages(['payment' => 'Gift payment confirmation does not match the purchase.']);
        }

        $this->completeFromVerifiedSession($payment, $session, data_get($event, 'attributes.livemode'), $event['id']);
    }

    private function completeFromVerifiedSession(Payment $payment, array $session, mixed $livemode, ?string $eventId = null): void
    {
        $metadata = data_get($session, 'attributes.metadata', []);
        $paid = collect(data_get($session, 'attributes.payments', []))
            ->first(fn ($item) => data_get($item, 'attributes.status') === 'paid');
        $gift = data_get($payment->payment_details, 'gift', []);
        if (! self::isGift($payment) || ! $paid
            || ! str_starts_with((string) data_get($session, 'id'), 'cs_')
            || empty(data_get($payment->payment_details, 'paymongo_checkout_session_id'))
            || data_get($session, 'id') !== data_get($payment->payment_details, 'paymongo_checkout_session_id')
            || (int) ($metadata['payment_id'] ?? 0) !== $payment->id
            || (int) ($metadata['user_id'] ?? 0) !== $payment->user_id
            || (int) ($metadata['subscription_id'] ?? 0) !== $payment->subscription_id
            || $livemode !== ($gift['livemode'] ?? false)
            || (int) data_get($paid, 'attributes.amount') !== (int) ($gift['amount_minor'] ?? 0)
            || data_get($paid, 'attributes.currency') !== ($gift['currency'] ?? null)
            || ! str_starts_with((string) data_get($paid, 'id'), 'pay_')) {
            throw ValidationException::withMessages(['payment' => 'Gift payment confirmation does not match the purchase.']);
        }

        DB::transaction(function () use ($payment, $paid, $eventId) {
            $dependent = User::query()->lockForUpdate()->findOrFail($payment->subscription->user_id);
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($payment->subscription_id);
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $details = (array) $payment->payment_details;
            if (! empty($details['gift']['applied_at'])) {
                DB::afterCommit(fn () => $this->dispatchFollowUps($payment->id));

                return;
            }
            if (in_array($payment->status, [PaymentStatus::Refunded, PaymentStatus::Cancelled], true)) {
                throw ValidationException::withMessages(['payment' => 'This gift payment needs reconciliation.']);
            }
            $gift = $details['gift'];
            $conflictingAccess = $dependent->subscriptions()->whereNull('connector_id')
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::GracePeriod, SubscriptionStatus::ScheduledCancel])
                ->where('id', '!=', $subscription->id)->get()
                ->contains(function (Subscription $candidate) use ($gift) {
                    $end = $this->subscriptions->resolveEffectiveEndTimestamp($candidate);

                    return (! $end || $end->isFuture() || $candidate->status === SubscriptionStatus::GracePeriod)
                        && $candidate->plan_id !== (int) $gift['plan_id'];
                });
            if ($conflictingAccess || $subscription->plan_id !== (int) $gift['plan_id']) {
                throw ValidationException::withMessages(['payment' => 'Another plan became active. Contact support to reconcile this paid purchase.']);
            }

            $existingEnd = $this->subscriptions->resolveEffectiveEndTimestamp($subscription);
            $wasActive = $subscription->isActive();
            $anchor = $existingEnd && $existingEnd->isFuture() && $subscription->status !== SubscriptionStatus::Pending
                ? $existingEnd->copy() : now();
            $end = $this->subscriptions->calculatePrepaidEnd($anchor, $gift['duration_unit'], (int) $gift['duration_count']);
            $details['gift'] = array_merge($gift, [
                'applied_at' => now()->toIso8601String(), 'checkout_state' => 'paid',
                'welcome_required' => ! $wasActive,
                'before_expires_at' => $existingEnd?->toIso8601String(), 'after_expires_at' => $end->toIso8601String(),
            ]);
            $details['paymongo_payment_id'] = data_get($paid, 'id');
            if ($eventId !== null) {
                $details['gift_webhook_event_id'] = $eventId;
            } else {
                $details['verified_via_api'] = true;
                $details['verified_at'] = now()->toDateTimeString();
            }
            $method = (string) data_get($paid, 'attributes.source.type', 'paymongo');
            $payment->forceFill([
                'status' => PaymentStatus::Completed, 'paid_at' => now(),
                'method' => in_array($method, ['gcash', 'paymaya', 'grab_pay', 'card', 'bank_transfer'], true) ? $method : 'paymongo',
                'payment_details' => $details,
            ])->saveQuietly();
            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'start_date' => $wasActive ? $subscription->start_date : now(),
                'starts_at' => $wasActive ? ($subscription->starts_at ?? $subscription->start_date) : now(),
                'end_date' => $end, 'ends_at' => $end, 'auto_renew' => false, 'next_billing_at' => null,
                'trial_ends_at' => null, 'cancelled_at' => null, 'canceled_at' => null, 'cancel_at' => null,
                'grace_ends_at' => null, 'grace_period_ends' => null, 'cancellation_reason' => null,
                'source_provider' => 'guardian_gift', 'source_reference' => $payment->transaction_id,
            ]);

            DB::afterCommit(fn () => $this->dispatchFollowUps($payment->id));
        }, attempts: 3);
    }

    private function dispatchFollowUps(int $paymentId): void
    {
        // A failed enqueue returns 500 so provider redelivery retries missing work, never the paid term.
        foreach (['receipt', 'welcome', 'purchaser', 'admins'] as $key) {
            DB::transaction(function () use ($paymentId, $key) {
                $payment = Payment::query()->lockForUpdate()->findOrFail($paymentId);
                if (data_get($payment->payment_details, 'gift.follow_ups.'.$key)) {
                    return;
                }
                $subscription = $payment->subscription;
                $actions = [
                    'receipt' => fn () => event(new PaymentSuccessful($payment->fresh())),
                    'welcome' => function () use ($payment, $subscription) {
                        if (data_get($payment->payment_details, 'gift.welcome_required')) {
                            SendSubscriptionWelcomeEmail::dispatch($subscription)->onQueue('emails');
                        }
                    },
                    'purchaser' => fn () => $payment->user?->notify(new SubscriptionResultNotification('completed', $subscription, $payment)),
                    'admins' => fn () => User::role('admin')->get()->each(function (User $admin) use ($payment) {
                        $admin->notify(new NewSubscriptionPurchaseNotification($payment));
                        $admin->notify(new NewPaymentTransactionNotification($payment));
                    }),
                ];
                $actions[$key]();
                $details = (array) $payment->fresh()->payment_details;
                $details['gift']['follow_ups'][$key] = now()->toIso8601String();
                $payment->forceFill(['payment_details' => $details])->saveQuietly();
            });
        }
    }

    public function prepare(User $guardian, User $dependent, SubscriptionPlan $plan, string $requestKey): Payment
    {
        return DB::transaction(function () use ($guardian, $dependent, $plan, $requestKey) {
            $dependent = User::query()->lockForUpdate()->findOrFail($dependent->id);
            Gate::forUser($guardian->fresh())->authorize('giftSubscription', $dependent);
            $reference = 'GIFT-'.$guardian->id.'-'.Str::lower($requestKey);
            $previous = Payment::query()->where('transaction_id', $reference)->first();
            if ($previous) {
                if ($previous->user_id !== $guardian->id || $previous->subscription?->user_id !== $dependent->id
                    || (int) data_get($previous->payment_details, 'gift.plan_id') !== $plan->id) {
                    throw ValidationException::withMessages(['plan_id' => 'This purchase request was already used.']);
                }

                return $previous;
            }

            $plan = SubscriptionPlan::active()->where('plan_audience', 'learner')->findOrFail($plan->id);
            $price = $this->catalog->effectivePrice($plan);
            if (! $price) {
                throw ValidationException::withMessages(['plan_id' => 'Choose an available paid learner plan with a prepaid duration.']);
            }
            if (! $this->catalog->isAvailable($plan)) {
                throw ValidationException::withMessages(['plan_id' => 'This plan is not currently available.']);
            }

            $pending = self::unresolvedPayments($dependent)->lockForUpdate()->first();
            if ($pending) {
                if (self::isGift($pending) && $pending->user_id === $guardian->id
                    && (int) data_get($pending->payment_details, 'gift.plan_id') === $plan->id) {
                    return $pending;
                }

                throw ValidationException::withMessages(['plan_id' => 'A purchase is already awaiting confirmation for this dependent.']);
            }

            $activeSubscriptions = $this->currentSubscriptions($dependent, lockForUpdate: true);
            if ($activeSubscriptions->contains(fn (Subscription $candidate) => $candidate->plan_id !== $plan->id
                || ! $this->subscriptions->resolveEffectiveEndTimestamp($candidate)
                || $candidate->status === SubscriptionStatus::GracePeriod)) {
                throw ValidationException::withMessages(['plan_id' => 'A different or unresolved plan is active. Wait until it expires before gifting this plan.']);
            }
            $subscription = $activeSubscriptions->first();
            if ($subscription && ! $this->canRenewSubscription($subscription, $activeSubscriptions)) {
                throw ValidationException::withMessages(['plan_id' => 'This dependent already has an active subscription. Renewal is not available yet.']);
            }

            if (! $subscription) {
                $subscription = $this->subscriptions->create($dependent, $plan, $guardian);
                $payment = $subscription->payments()->sole();
            } else {
                $payment = $subscription->payments()->create([
                    'user_id' => $guardian->id, 'amount' => $price['amount_minor'] / 100,
                    'method' => null, 'status' => PaymentStatus::Pending,
                    'payment_details' => ['payment_scope' => 'subscription', 'plan_name' => $plan->name],
                ]);
            }

            $link = ParentChildAccount::query()->where('parent_user_id', $guardian->id)
                ->where('child_user_id', $dependent->id)->firstOrFail();
            $payment->update([
                'transaction_id' => $reference,
                'payment_details' => array_merge((array) $payment->payment_details, [
                    'gift' => [
                        'relationship_id' => $link->id, 'beneficiary_id' => $dependent->id,
                        'beneficiary_name' => $dependent->name, 'plan_id' => $plan->id,
                        'plan_name' => $plan->name, 'plan_price_id' => is_numeric($price['id']) ? (int) $price['id'] : null,
                        'amount_minor' => $price['amount_minor'], 'currency' => $price['currency'],
                        'duration_unit' => $price['duration_unit'], 'duration_count' => $price['duration_count'],
                        'duration_label' => $price['label'], 'checkout_state' => 'not_started',
                        'livemode' => str_starts_with((string) config('paymongo.secret_key'), 'sk_live_'),
                    ],
                ]),
            ]);

            return $payment;
        });
    }
}
