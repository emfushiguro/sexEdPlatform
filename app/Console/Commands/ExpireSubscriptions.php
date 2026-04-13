<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionExpired;
use App\Notifications\Learner\SubscriptionResultNotification;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire 
                            {--force : Force expire without confirmation}
                            {--dry-run : Show what would be expired without making changes}';
    
    protected $description = 'Expire subscriptions that have passed their end date';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $this->info('Checking for expired subscriptions...');

        // Find all active subscriptions whose effective end timestamp has elapsed.
        $query = Subscription::query()->expired();

        $count = $query->count();

        if ($count === 0) {
            $this->info('No subscriptions to expire.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} subscription(s) to expire.");

        if ($this->option('dry-run')) {
            $subscriptions = $query->with('user')->get();
            $this->table(
                ['ID', 'User', 'Plan', 'End Date', 'Days Overdue'],
                $subscriptions->map(fn($s) => [
                    $s->id,
                    $s->user->email ?? 'N/A',
                    $s->getPlanLabel(),
                    ($s->ends_at ?? $s->end_date)?->format('Y-m-d H:i') ?? 'N/A',
                    now()->diffInDays($s->ends_at ?? $s->end_date) . ' days'
                ])
            );
            $this->warn('Dry run - no changes made.');
            return Command::SUCCESS;
        }

        $expired = 0;
        $subscriptions = $query->with('user')->get();

        foreach ($subscriptions as $subscription) {
            try {
                $subscriptionService->expire($subscription);

                // Dispatch event for notifications/logging
                event(new SubscriptionExpired($subscription));

                if ($subscription->user) {
                    $subscription->user->notify(new SubscriptionResultNotification('expired', $subscription));
                }

                Log::info('Subscription expired', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'end_date' => $subscription->end_date,
                ]);

                $expired++;
            } catch (\Exception $e) {
                Log::error('Failed to expire subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed to expire subscription #{$subscription->id}: {$e->getMessage()}");
            }
        }

        $this->info("✓ Expired {$expired} subscription(s).");

        return Command::SUCCESS;
    }
}
