<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use App\Mail\WeeklyAnalyticsReportMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GenerateAnalyticsReport extends Command
{
    protected $signature = 'analytics:generate-report {type=weekly : Type of report (weekly|monthly)}';
    protected $description = 'Generate and send analytics reports to administrators';

    public function handle(AnalyticsService $analyticsService)
    {
        $type = $this->argument('type');
        $period = $type === 'weekly' ? '7d' : '30d';

        $this->info("Generating {$type} analytics report...");

        // Gather analytics data
        $data = [
            'subscription_metrics' => $analyticsService->getSubscriptionMetrics($period),
            'revenue_metrics' => $analyticsService->getRevenueMetrics($period),
            'payment_analytics' => $analyticsService->getPaymentAnalytics($period),
            'user_growth' => $analyticsService->getUserGrowthMetrics($period),
            'period' => $period,
            'type' => $type,
            'generated_at' => now(),
        ];

        // Get admin users to send report to
        $adminUsers = User::where('role', 'admin')->get();

        if ($adminUsers->isEmpty()) {
            $this->warn('No admin users found to send report to');
            return;
        }

        // Send report via email
        foreach ($adminUsers as $admin) {
            Mail::to($admin->email)->send(new WeeklyAnalyticsReportMail($data));
        }

        $this->info("✓ Analytics report sent to {$adminUsers->count()} administrators");

        // Display summary in console
        $this->displayReportSummary($data);
    }

    private function displayReportSummary(array $data): void
    {
        $this->info("\n📊 Report Summary:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Subscribers', number_format($data['subscription_metrics']['total_subscribers'])],
                ['New Subscribers', number_format($data['subscription_metrics']['new_subscribers'])],
                ['MRR', '₱' . number_format($data['subscription_metrics']['monthly_recurring_revenue'], 2)],
                ['Total Revenue', '₱' . number_format($data['revenue_metrics']['total_revenue'], 2)],
                ['Payment Success Rate', number_format($data['payment_analytics']['success_rate'], 2) . '%'],
                ['New Users', number_format($data['user_growth']['new_users'])],
                ['Churn Rate', number_format($data['subscription_metrics']['churn_rate'], 2) . '%'],
            ]
        );
    }
}