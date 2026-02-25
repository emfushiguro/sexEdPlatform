<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestSubscriptionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscription:test
                            {user_email? : Email of user to create test subscription for}
                            {--duration=10 : Duration in minutes for test subscription}
                            {--create-test-plan : Create a test plan if it doesn\'t exist}
                            {--list-test-subs : List all test subscriptions}
                            {--cleanup : Remove expired test subscriptions}';

    /**
     * The console command description.
     */
    protected $description = 'Create and manage test subscriptions that expire in specified minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list-test-subs')) {
            return $this->listTestSubscriptions();
        }

        if ($this->option('cleanup')) {
            return $this->cleanupExpiredTestSubscriptions();
        }

        if ($this->option('create-test-plan')) {
            return $this->createTestPlan();
        }

        $userEmail = $this->argument('user_email');
        $duration = intval($this->option('duration'));

        if (!$userEmail) {
            $userEmail = $this->ask('Enter user email for test subscription');
        }

        if ($duration < 1 || $duration > 1440) { // Max 24 hours
            $this->error('Duration must be between 1 and 1440 minutes (24 hours)');
            return Command::FAILURE;
        }

        $user = User::where('email', $userEmail)->first();
        if (!$user) {
            $this->error("User with email '{$userEmail}' not found.");
            return Command::FAILURE;
        }

        // Check if user already has active subscription
        if ($user->subscription && $user->subscription->isActive()) {
            $this->warn("User already has an active subscription: {$user->subscription->getPlanLabel()}");
            
            if (!$this->confirm('Cancel existing subscription and create test subscription?')) {
                return Command::FAILURE;
            }

            // Cancel existing subscription
            $user->subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Cancelled for test subscription',
            ]);
            $this->info('Existing subscription cancelled.');
        }

        return $this->createTestSubscription($user, $duration);
    }

    protected function createTestSubscription(User $user, int $durationMinutes)
    {
        // Find or create test plan
        $testPlan = $this->getOrCreateTestPlan($durationMinutes);
        
        if (!$testPlan) {
            $this->error('Failed to create or find test plan');
            return Command::FAILURE;
        }

        DB::beginTransaction();

        try {
            $startDate = now();
            $endDate = $startDate->copy()->addMinutes($durationMinutes);

            // Create test subscription
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $testPlan->id,
                'plan' => $testPlan->slug,
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trial_ends_at' => null,
                'price_paid' => 1.00, // Nominal fee for test
                'auto_renew' => false,
            ]);

            // Create payment record
            $subscription->payments()->create([
                'user_id' => $user->id,
                'amount' => 1.00,
                'method' => 'test_command',
                'status' => 'completed',
                'transaction_id' => 'TEST-' . strtoupper(uniqid()),
                'payment_details' => [
                    'test_subscription' => true,
                    'duration_minutes' => $durationMinutes,
                    'created_via' => 'artisan_command',
                ],
                'paid_at' => now(),
            ]);

            DB::commit();

            $this->info("✅ Test subscription created successfully!");
            $this->info("📧 User: {$user->name} ({$user->email})");
            $this->info("📋 Plan: {$testPlan->name}");
            $this->info("⏰ Duration: {$durationMinutes} minutes");
            $this->info("🔚 Expires: {$endDate->format('Y-m-d H:i:s')}");
            $this->info("💰 Price: ₱1.00 (test)");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollback();
            
            $this->error('Failed to create test subscription: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function getOrCreateTestPlan(int $durationMinutes)
    {
        // Look for existing test plan with same duration
        $existingPlan = SubscriptionPlan::where('slug', "test-{$durationMinutes}min")
            ->where('is_active', true)
            ->first();

        if ($existingPlan) {
            return $existingPlan;
        }

        // Create new test plan
        return SubscriptionPlan::create([
            'name'        => "Test Plan ({$durationMinutes} Minutes)",
            'slug'        => "test-{$durationMinutes}min",
            'description' => "Test subscription plan that expires in {$durationMinutes} minutes. For testing purposes only.",
            'price'       => 1.00,
            'trial_days'  => 0,
            'max_users'   => 1,
            'max_modules' => 999,
            'features'    => [
                'test_mode',
                'unlimited_quizzes',
                'all_modules',
            ],
            'is_active'   => true,
            'sort_order'  => 0,
        ]);
    }

    protected function createTestPlan()
    {
        $duration = intval($this->ask('Enter duration in minutes for test plan', '10'));
        
        if ($duration < 1 || $duration > 1440) {
            $this->error('Duration must be between 1 and 1440 minutes');
            return Command::FAILURE;
        }

        $testPlan = $this->getOrCreateTestPlan($duration);
        
        $this->info("✅ Test plan created/found: {$testPlan->name}");
        $this->info("📋 Slug: {$testPlan->slug}");
        $this->info("💰 Price: ₱{$testPlan->price}");
        
        return Command::SUCCESS;
    }

    protected function listTestSubscriptions()
    {
        $testSubscriptions = Subscription::whereHas('plan', function ($query) {
            $query->where('slug', 'like', 'test-%');
        })
        ->with(['user', 'plan'])
        ->orderBy('created_at', 'desc')
        ->get();

        if ($testSubscriptions->isEmpty()) {
            $this->info('No test subscriptions found.');
            return Command::SUCCESS;
        }

        $this->info('📋 Test Subscriptions:');
        $this->line('');

        $headers = ['ID', 'User', 'Email', 'Plan', 'Status', 'Start', 'End', 'Remaining'];
        $rows = [];

        foreach ($testSubscriptions as $sub) {
            $remaining = $sub->isActive() ? $sub->end_date->diffForHumans() : 'N/A';
            
            $rows[] = [
                $sub->id,
                $sub->user->name,
                $sub->user->email,
                $sub->plan?->name ?? $sub->plan,
                $sub->status,
                $sub->start_date->format('H:i:s'),
                $sub->end_date->format('H:i:s'),
                $remaining,
            ];
        }

        $this->table($headers, $rows);

        $activeCount = $testSubscriptions->where('status', 'active')->count();
        $expiredCount = $testSubscriptions->where('status', 'expired')->count();

        $this->info("📊 Summary: {$testSubscriptions->count()} total, {$activeCount} active, {$expiredCount} expired");
        
        return Command::SUCCESS;
    }

    protected function cleanupExpiredTestSubscriptions()
    {
        $expiredTestSubs = Subscription::whereHas('plan', function ($query) {
            $query->where('slug', 'like', 'test-%');
        })
        ->where('status', 'active')
        ->where('end_date', '<', now())
        ->get();

        if ($expiredTestSubs->isEmpty()) {
            $this->info('No expired test subscriptions to cleanup.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiredTestSubs->count()} expired test subscriptions.");

        if (!$this->confirm('Mark them as expired?')) {
            return Command::FAILURE;
        }

        DB::beginTransaction();

        try {
            foreach ($expiredTestSubs as $sub) {
                $sub->update(['status' => 'expired']);
                $this->line("• Expired: {$sub->user->email} ({$sub->plan?->name})");
            }

            DB::commit();

            $this->info("✅ Cleaned up {$expiredTestSubs->count()} expired test subscriptions.");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollback();
            
            $this->error('Failed to cleanup expired subscriptions: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}