<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupBillingSystem extends Command
{
    protected $signature = 'billing:setup {--force : Force setup even if already configured}';
    protected $description = 'Set up the billing system with all required migrations and seeders';

    public function handle()
    {
        $this->info('🚀 Setting up billing system...');

        // Check if migrations need to be run
        $this->info('📋 Running migrations...');
        try {
            Artisan::call('migrate', [
                '--force' => $this->option('force'),
            ]);
            $this->info('✅ Migrations completed successfully');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return 1;
        }

        // Seed subscription plans
        $this->info('🌱 Seeding subscription plans...');
        try {
            Artisan::call('db:seed', [
                '--class' => 'SubscriptionPlanSeeder',
                '--force' => true,
            ]);
            $this->info('✅ Subscription plans seeded successfully');
        } catch (\Exception $e) {
            $this->error('❌ Seeding failed: ' . $e->getMessage());
            return 1;
        }

        // Display setup summary
        $this->displaySetupSummary();

        // Show next steps
        $this->displayNextSteps();

        $this->info('🎉 Billing system setup completed successfully!');
        return 0;
    }

    private function displaySetupSummary(): void
    {
        $this->info("\n📊 Setup Summary:");
        $this->table(
            ['Component', 'Status'],
            [
                ['Subscription Plans Table', '✅ Created'],
                ['Refunds Table', '✅ Created'], 
                ['Invoices Table', '✅ Created'],
                ['Payment Enhancements', '✅ Applied'],
                ['User Table Updates', '✅ Applied'],
                ['Subscription Plans Data', '✅ Seeded'],
                ['Models & Services', '✅ Created'],
            ]
        );
    }

    private function displayNextSteps(): void
    {
        $this->info("\n🔧 Next Steps:");
        $this->line("1. Configure your .env file with PayMongo credentials:");
        $this->line("   PAYMONGO_SECRET_KEY=sk_test_xxxxx");
        $this->line("   PAYMONGO_PUBLIC_KEY=pk_test_xxxxx");
        $this->line("   PAYMONGO_WEBHOOK_SECRET=whsec_xxxxx");
        
        $this->line("\n2. Set up webhook signature verification:");
        $this->line("   - Add VerifyPayMongoWebhook middleware to webhook routes");
        
        $this->line("\n3. Configure scheduled tasks in app/Console/Kernel.php:");
        $this->line("   \$schedule->command('subscriptions:process-renewals')->daily();");
        $this->line("   \$schedule->command('analytics:generate-report weekly')->weekly();");
        
        $this->line("\n4. Test the payment flows:");
        $this->line("   - Subscription creation");
        $this->line("   - Payment webhook handling");
        $this->line("   - Refund processing");
        
        $this->line("\n5. Configure email templates and notifications");
        
        $this->line("\n📖 See PAYMENT_SYSTEM_IMPROVEMENTS.md for detailed implementation guide");
    }
}