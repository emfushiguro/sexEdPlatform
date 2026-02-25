<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Events\SubscriptionExpired;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire 
                            {--force : Force expire without confirmation}
                            {--dry-run : Show what would be expired without making changes}';
    
    protected $description = 'Expire subscriptions that have passed their end date';

    public function handle(): int
    {
        $this->info('Checking for expired subscriptions...');

        // Find all active subscriptions with end_date in the past
        $query = Subscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now());

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
                    $s->end_date->format('Y-m-d H:i'),
                    now()->diffInDays($s->end_date) . ' days'
                ])
            );
            $this->warn('Dry run - no changes made.');
            return Command::SUCCESS;
        }

        $expired = 0;
        $subscriptions = $query->get();

        foreach ($subscriptions as $subscription) {
            try {
                $subscription->update([
                    'status' => 'expired',
                ]);

                // Dispatch event for notifications/logging
                event(new SubscriptionExpired($subscription));

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
