<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Services\SubscriptionDunningService;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewals extends Command
{
    protected $signature = 'subscriptions:process-renewals';
    protected $description = 'Process subscription renewals and handle payment failures';

    public function handle(SubscriptionDunningService $dunningService)
    {
        $this->info('Processing subscription renewals...');

        // 1. Handle expiring subscriptions notifications
        $dunningService->handleExpiringSubscriptions();
        $this->info('✓ Processed expiring subscription notifications');

        // 2. Process retry payments
        $dunningService->processRetryPayments();
        $this->info('✓ Processed retry payments');

        // 3. Expire subscriptions past grace period
        $this->expireGracePeriodSubscriptions();
        $this->info('✓ Expired subscriptions past grace period');

        // 4. Clean up old pending payments
        $this->cleanupOldPendingPayments();
        $this->info('✓ Cleaned up old pending payments');

        $this->info('Subscription renewal processing completed!');
    }

    private function expireGracePeriodSubscriptions(): void
    {
        $expiredSubscriptions = Subscription::where('status', SubscriptionStatus::PastDue)
            ->where('grace_period_ends', '<', now())
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => 'Grace period expired - payment failed'
            ]);

            Log::info('Subscription cancelled after grace period', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id
            ]);
        }

        $this->info("Cancelled {$expiredSubscriptions->count()} subscriptions past grace period");
    }

    private function cleanupOldPendingPayments(): void
    {
        // Cancel payments older than 7 days that are still pending
        $oldPendingPayments = Payment::where('status', PaymentStatus::Pending)
            ->where('created_at', '<', now()->subDays(7))
            ->get();

        foreach ($oldPendingPayments as $payment) {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'failure_reason' => 'Payment timeout - cancelled after 7 days',
                    'cancelled_at' => now()->toDateTimeString()
                ])
            ]);
        }

        $this->info("Cancelled {$oldPendingPayments->count()} old pending payments");
    }
}