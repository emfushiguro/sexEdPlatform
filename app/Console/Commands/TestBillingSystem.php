<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use App\Services\SubscriptionDunningService;
use Illuminate\Console\Command;

class TestBillingSystem extends Command
{
    protected $signature = 'billing:test {--fix : Fix any detected issues}';
    protected $description = 'Test the billing system for errors and validate configuration';

    public function handle()
    {
        $this->info('🧪 Testing Billing System...');
        
        $errors = [];
        $warnings = [];

        // Test 1: Check if subscription plans exist
        try {
            $plansCount = SubscriptionPlan::count();
            if ($plansCount === 0) {
                $warnings[] = 'No subscription plans found. Run: php artisan db:seed --class=SubscriptionPlanSeeder';
            } else {
                $this->info("✅ Found {$plansCount} subscription plans");
            }
        } catch (\Exception $e) {
            $errors[] = "❌ Subscription plans table error: " . $e->getMessage();
        }

        // Test 2: Check User model has proper relationships
        try {
            $user = new User();
            if (!method_exists($user, 'subscriptions')) {
                $errors[] = '❌ User model missing subscriptions() relationship';
            }
            if (!method_exists($user, 'payments')) {
                $errors[] = '❌ User model missing payments() relationship';
            }
            $this->info('✅ User model relationships verified');
        } catch (\Exception $e) {
            $errors[] = "❌ User model error: " . $e->getMessage();
        }

        // Test 3: Check Payment model has proper relationships
        try {
            $payment = new Payment();
            if (!method_exists($payment, 'refunds')) {
                $errors[] = '❌ Payment model missing refunds() relationship';
            }
            if (!method_exists($payment, 'invoice')) {
                $errors[] = '❌ Payment model missing invoice() relationship';
            }
            $this->info('✅ Payment model relationships verified');
        } catch (\Exception $e) {
            $errors[] = "❌ Payment model error: " . $e->getMessage();
        }

        // Test 4: Check configuration
        try {
            $paymongoKey = config('paymongo.secret_key');
            if (!$paymongoKey) {
                $warnings[] = 'PayMongo secret key not configured in .env file';
            }
            
            $billingConfig = config('billing');
            if (!$billingConfig) {
                $warnings[] = 'Billing configuration not found. Ensure config/billing.php exists';
            }
            
            $this->info('✅ Configuration checked');
        } catch (\Exception $e) {
            $errors[] = "❌ Configuration error: " . $e->getMessage();
        }

        // Test 5: Check required directories
        $invoicesDir = storage_path('app/private/invoices');
        if (!is_dir($invoicesDir)) {
            if ($this->option('fix')) {
                mkdir($invoicesDir, 0755, true);
                $this->info('✅ Created invoices directory');
            } else {
                $warnings[] = "Invoices directory missing: {$invoicesDir}";
            }
        } else {
            $this->info('✅ Invoices directory exists');
        }

        // Test 6: Check mail classes exist
        try {
            if (!class_exists('App\\Mail\\PaymentFailedNotification')) {
                $errors[] = '❌ PaymentFailedNotification mail class not found';
            }
            if (!class_exists('App\\Mail\\SubscriptionExpiringNotification')) {
                $errors[] = '❌ SubscriptionExpiringNotification mail class not found';
            }
            $this->info('✅ Mail classes verified');
        } catch (\Exception $e) {
            $errors[] = "❌ Mail classes error: " . $e->getMessage();
        }

        // Test 7: Check service classes
        try {
            $dunningService = app(SubscriptionDunningService::class);
            if (!$dunningService instanceof SubscriptionDunningService) {
                $errors[] = '❌ SubscriptionDunningService not resolvable';
            }
            $this->info('✅ Services verified');
        } catch (\Exception $e) {
            $errors[] = "❌ Services error: " . $e->getMessage();
        }

        // Test 8: Check for DomPDF
        try {
            if (!class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
                $warnings[] = 'DomPDF not installed. Run: composer require barryvdh/laravel-dompdf';
            } else {
                $this->info('✅ DomPDF installed');
            }
        } catch (\Exception $e) {
            $warnings[] = "DomPDF check failed: " . $e->getMessage();
        }

        // Display results
        $this->displayResults($errors, $warnings);

        return empty($errors) ? 0 : 1;
    }

    private function displayResults(array $errors, array $warnings): void
    {
        $this->newLine();
        
        if (empty($errors) && empty($warnings)) {
            $this->info('🎉 All tests passed! Your billing system is ready to use.');
            return;
        }

        if (!empty($errors)) {
            $this->error('❌ Errors found:');
            foreach ($errors as $error) {
                $this->line('   ' . $error);
            }
            $this->newLine();
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Warnings:');
            foreach ($warnings as $warning) {
                $this->line('   ' . $warning);
            }
            $this->newLine();
        }

        if (!empty($warnings) && empty($errors)) {
            $this->info('✅ Core system is functional but some optional features need setup.');
        }

        $this->info('💡 Run with --fix to automatically fix some issues');
    }
}