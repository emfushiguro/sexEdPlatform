<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Mail\PaymentFailedMail;
use App\Mail\SubscriptionExpiringMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SubscriptionDunningService
{
    public function handleFailedPayment(Payment $payment): void
    {
        $subscription = $payment->subscription;
        $user = $subscription->user;

        // Increment retry count
        $retryCount = $payment->payment_details['retry_count'] ?? 0;
        $retryCount++;

        $payment->update([
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'retry_count' => $retryCount,
                'last_retry_at' => now(),
                'failure_reason' => 'Payment failed during processing'
            ])
        ]);

        // Define retry schedule (days)
        $retrySchedule = [3, 7, 14]; // Retry after 3, 7, and 14 days

        if ($retryCount <= count($retrySchedule)) {
            // Schedule next retry
            $nextRetryDays = $retrySchedule[$retryCount - 1];
            
            Log::info('Scheduling payment retry', [
                'payment_id' => $payment->id,
                'retry_count' => $retryCount,
                'next_retry_in_days' => $nextRetryDays
            ]);

            // Send failed payment notification only once per retry count.
            if ($this->shouldSendFailureReminder($payment, $retryCount)) {
                Mail::to($user->email)->send(new PaymentFailedMail($payment, $nextRetryDays));
                $this->markFailureReminderSent($payment, $retryCount);
            }
            
            // Add grace period to subscription
            if ($subscription->status === SubscriptionStatus::Active) {
                $subscription->update([
                    'status' => SubscriptionStatus::GracePeriod,
                    'grace_period_ends' => now()->addDays($nextRetryDays),
                    'grace_ends_at' => now()->addDays($nextRetryDays),
                ]);
            }
        } else {
            // Max retries reached - cancel subscription
            $this->cancelSubscription($subscription, 'Payment failed after maximum retry attempts');
        }
    }

    public function handleExpiringSubscriptions(): void
    {
        // Find subscriptions expiring in 7 days
        $expiringSoon = Subscription::where('status', SubscriptionStatus::Active)
            ->where('auto_renew', true)
            ->whereBetween('end_date', [now()->addDays(7), now()->addDays(8)])
            ->with('user')
            ->get();

        foreach ($expiringSoon as $subscription) {
            Mail::to($subscription->user->email)
                ->send(new SubscriptionExpiringMail($subscription));
        }

        // Find subscriptions that expired yesterday - add grace period
        $expiredYesterday = Subscription::where('status', SubscriptionStatus::Active)
            ->where('end_date', '<', now()->startOfDay())
            ->where('end_date', '>', now()->subDays(2)->startOfDay())
            ->get();

        foreach ($expiredYesterday as $subscription) {
            if ($subscription->auto_renew) {
                $subscription->update([
                    'status' => SubscriptionStatus::GracePeriod,
                    'grace_period_ends' => now()->addDays(7),
                    'grace_ends_at' => now()->addDays(7),
                ]);
            } else {
                $this->cancelSubscription($subscription, 'Subscription expired - auto-renew disabled');
            }
        }
    }

    public function expireGracePeriodSubscription(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::GracePeriod) {
            return;
        }

        $graceEndsAt = $subscription->grace_ends_at ?? $subscription->grace_period_ends;
        if ($graceEndsAt && now()->lt($graceEndsAt)) {
            return;
        }

        $subscription->update([
            'status' => SubscriptionStatus::Expired,
            'auto_renew' => false,
            'grace_ends_at' => null,
            'grace_period_ends' => null,
        ]);
    }

    private function cancelSubscription(Subscription $subscription, string $reason): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason
        ]);

        Log::info('Subscription cancelled due to payment failure', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'reason' => $reason
        ]);
    }

    public function processRetryPayments(): void
    {
        // Find payments ready for retry
        $paymentsToRetry = Payment::where('status', PaymentStatus::Failed)
            ->whereJsonContains('payment_details->retry_count', '<=', 3)
            ->where('created_at', '>', now()->subDays(21)) // Don't retry older than 21 days
            ->with(['subscription.user'])
            ->get();

        foreach ($paymentsToRetry as $payment) {
            $retryCount = $payment->payment_details['retry_count'] ?? 0;
            $lastRetry = $payment->payment_details['last_retry_at'] ?? null;
            
            if ($lastRetry) {
                $daysSinceLastRetry = now()->diffInDays($lastRetry);
                $retrySchedule = [3, 7, 14];
                
                if ($retryCount < count($retrySchedule) && $daysSinceLastRetry >= $retrySchedule[$retryCount]) {
                    $this->retryPayment($payment);
                }
            }
        }
    }

    private function retryPayment(Payment $payment): void
    {
        // In a real implementation, this would call PayMongo API to retry the payment
        // For now, we'll create a new payment intent
        
        Log::info('Retrying payment', [
            'payment_id' => $payment->id,
            'subscription_id' => $payment->subscription_id
        ]);

        // Create new payment record for retry
        $newPayment = Payment::create([
            'user_id' => $payment->user_id,
            'subscription_id' => $payment->subscription_id,
            'amount' => $payment->amount,
            'method' => 'retry_' . $payment->method,
            'status' => PaymentStatus::Pending,
            'transaction_id' => 'RETRY-' . strtoupper(uniqid()),
            'payment_details' => [
                'original_payment_id' => $payment->id,
                'retry_attempt' => true,
                'created_via' => 'dunning_retry'
            ]
        ]);

        // TODO: Integrate with PayMongo to create new payment intent
        // For now, just log the retry attempt
    }

    private function shouldSendFailureReminder(Payment $payment, int $retryCount): bool
    {
        $alreadySent = $payment->payment_details['failure_reminders_sent'] ?? [];

        return !in_array($retryCount, $alreadySent, true);
    }

    private function markFailureReminderSent(Payment $payment, int $retryCount): void
    {
        $details = $payment->payment_details ?? [];
        $alreadySent = $details['failure_reminders_sent'] ?? [];
        $alreadySent[] = $retryCount;

        $details['failure_reminders_sent'] = array_values(array_unique($alreadySent));

        $payment->update(['payment_details' => $details]);
    }
}