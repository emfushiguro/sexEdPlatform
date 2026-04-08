<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Events\PaymentSuccessful;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\Admin\NewPaymentTransactionNotification;
use App\Notifications\Admin\NewSubscriptionPurchaseNotification;
use App\Notifications\Learner\SubscriptionResultNotification;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        Log::info('Payment created', [
            'payment_id'      => $payment->id,
            'user_id'         => $payment->user_id,
            'subscription_id' => $payment->subscription_id,
            'amount'          => $payment->amount,
            'status'          => $payment->status,
        ]);
    }

    public function updated(Payment $payment): void
    {
        if ($payment->isDirty('status') && $payment->status === PaymentStatus::Completed) {
            $payment->loadMissing(['user', 'subscription.user']);

            if ($payment->isModulePurchase()) {
                Log::info('Module purchase payment completed', [
                    'payment_id' => $payment->id,
                    'user_id' => $payment->user_id,
                ]);
            } else {
                // Fire PaymentSuccessful event (invoice + receipt email queued)
                event(new PaymentSuccessful($payment));

                // Activate subscription via service.
                // Always load a FRESH instance from the DB so the idempotency guard inside
                // activate() sees the real current status, not a stale in-memory value.
                // activate() is idempotent — safe to call even if already active.
                $subscription = $payment->subscription()->first();
                if ($subscription) {
                    app(SubscriptionService::class)->activate($subscription);
                }

                if ($payment->user) {
                    $payment->user->notify(new SubscriptionResultNotification('completed', $subscription, $payment));
                }

                $this->notifyAdmins(new NewSubscriptionPurchaseNotification($payment));
            }

            $this->notifyAdmins(new NewPaymentTransactionNotification($payment));
        }

        if ($payment->isDirty('status') && $payment->status === PaymentStatus::Failed) {
            Log::warning('Payment failed for pending subscription', [
                'subscription_id' => $payment->subscription_id,
                'payment_id'      => $payment->id,
            ]);

            $payment->loadMissing(['user', 'subscription']);

            if (!$payment->isModulePurchase() && $payment->user) {
                $payment->user->notify(new SubscriptionResultNotification('failed', $payment->subscription, $payment));
            }

            $this->notifyAdmins(new NewPaymentTransactionNotification($payment));
        }
    }

    public function deleted(Payment $payment): void
    {
        Log::info('Payment deleted', [
            'payment_id'      => $payment->id,
            'subscription_id' => $payment->subscription_id,
        ]);

        if ($payment->subscription && $payment->status === PaymentStatus::Completed) {
            $subscription = $payment->subscription;

            $hasOtherCompletedPayments = $subscription->payments()
                ->where('id', '!=', $payment->id)
                ->where('status', PaymentStatus::Completed)
                ->exists();

            if (!$hasOtherCompletedPayments) {
                $subscription->update([
                    'status'              => SubscriptionStatus::Cancelled,
                    'cancelled_at'        => now(),
                    'cancellation_reason' => 'Associated payment was deleted',
                ]);

                Log::warning('Subscription deactivated due to payment deletion', [
                    'subscription_id'    => $subscription->id,
                    'deleted_payment_id' => $payment->id,
                ]);
            }
        }
    }

    public function forceDeleted(Payment $payment): void
    {
        $this->deleted($payment);
    }

    private function notifyAdmins(object $notification): void
    {
        try {
            User::query()
                ->role('admin')
                ->get()
                ->each(fn (User $admin) => $admin->notify($notification));
        } catch (\Throwable $exception) {
            Log::warning('Failed to send admin payment notification', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
