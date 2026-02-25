<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Mail\PaymentFailedNotification;
use App\Mail\SubscriptionExpiringNotification;
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

            // Send failed payment notification
            Mail::to($user->email)->send(new PaymentFailedNotification($payment, $nextRetryDays));
            
            // Add grace period to subscription
            if ($subscription->status === 'active') {
                $subscription->update([
                    'status' => 'past_due',
                    'grace_period_ends' => now()->addDays($nextRetryDays)
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
        $expiringSoon = Subscription::where('status', 'active')
            ->where('auto_renew', true)
            ->whereBetween('end_date', [now()->addDays(7), now()->addDays(8)])
            ->with('user')
            ->get();

        foreach ($expiringSoon as $subscription) {
            Mail::to($subscription->user->email)
                ->send(new SubscriptionExpiringNotification($subscription));
        }

        // Find subscriptions that expired yesterday - add grace period
        $expiredYesterday = Subscription::where('status', 'active')
            ->where('end_date', '<', now()->startOfDay())
            ->where('end_date', '>', now()->subDays(2)->startOfDay())
            ->get();

        foreach ($expiredYesterday as $subscription) {
            if ($subscription->auto_renew) {
                $subscription->update([
                    'status' => 'past_due',
                    'grace_period_ends' => now()->addDays(7)
                ]);
            } else {
                $this->cancelSubscription($subscription, 'Subscription expired - auto-renew disabled');
            }
        }
    }

    private function cancelSubscription(Subscription $subscription, string $reason): void
    {
        $subscription->update([
            'status' => 'cancelled',
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
        $paymentsToRetry = Payment::where('status', 'failed')
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
            'status' => 'pending',
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
}