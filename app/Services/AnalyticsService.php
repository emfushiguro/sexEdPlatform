<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getSubscriptionMetrics(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);
        
        return [
            'total_subscribers' => Subscription::where('status', SubscriptionStatus::Active)->count(),
            'new_subscribers' => Subscription::where('created_at', '>=', $startDate)->count(),
            'churned_subscribers' => Subscription::where('status', SubscriptionStatus::Cancelled)
                ->where('cancelled_at', '>=', $startDate)->count(),
            'monthly_recurring_revenue' => $this->calculateMRR(),
            'annual_recurring_revenue' => $this->calculateARR(),
            'churn_rate' => $this->calculateChurnRate($period),
            'lifetime_value' => $this->calculateLTV(),
            'conversion_rate' => $this->calculateConversionRate($period),
        ];
    }

    public function getRevenueMetrics(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);
        
        $payments = Payment::where('status', PaymentStatus::Completed)
            ->where('paid_at', '>=', $startDate);
            
        return [
            'total_revenue' => $payments->sum('amount'),
            'average_order_value' => $payments->avg('amount'), 
            'payment_count' => $payments->count(),
            'refund_amount' => $this->getRefundAmount($startDate),
            'net_revenue' => $payments->sum('amount') - $this->getRefundAmount($startDate),
            'revenue_by_plan' => $this->getRevenueByPlan($startDate),
            'daily_revenue' => $this->getDailyRevenue($startDate),
        ];
    }

    public function getPaymentAnalytics(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);
        
        return [
            'payment_methods' => $this->getPaymentMethodStats($startDate),
            'success_rate' => $this->getPaymentSuccessRate($startDate),
            'failed_payments' => $this->getFailedPaymentStats($startDate),
            'processing_time' => $this->getAverageProcessingTime($startDate),
            'geographic_distribution' => $this->getGeographicStats($startDate),
        ];
    }

    public function getUserGrowthMetrics(string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);
        
        return [
            'total_users' => User::count(),
            'new_users' => User::where('created_at', '>=', $startDate)->count(),
            'active_users' => $this->getActiveUsers($startDate),
            'user_retention' => $this->calculateRetentionRate($period),
            'daily_signups' => $this->getDailySignups($startDate),
            'user_engagement' => $this->getUserEngagementStats($startDate),
        ];
    }

    private function calculateMRR(): float
    {
        $monthlyRevenue = Subscription::where('status', SubscriptionStatus::Active)
            ->join('payments', 'subscriptions.id', '=', 'payments.subscription_id')
            ->where('payments.status', PaymentStatus::Completed)
            ->where('subscriptions.plan', 'monthly')
            ->sum('payments.amount');
            
        $annualRevenue = Subscription::where('status', SubscriptionStatus::Active)
            ->join('payments', 'subscriptions.id', '=', 'payments.subscription_id')
            ->where('payments.status', PaymentStatus::Completed)
            ->where('subscriptions.plan', 'annual')
            ->sum('payments.amount');
            
        return $monthlyRevenue + ($annualRevenue / 12);
    }

    private function calculateARR(): float
    {
        return $this->calculateMRR() * 12;
    }

    private function calculateChurnRate(string $period): float
    {
        $startDate = $this->getStartDate($period);
        
        $startingSubscribers = Subscription::where('status', SubscriptionStatus::Active)
            ->where('created_at', '<', $startDate)
            ->count();
            
        $churned = Subscription::where('status', SubscriptionStatus::Cancelled)
            ->where('cancelled_at', '>=', $startDate)
            ->count();
            
        return $startingSubscribers > 0 ? ($churned / $startingSubscribers) * 100 : 0;
    }

    private function calculateLTV(): float
    {
        $avgMonthlyRevenue = $this->calculateMRR();
        $churnRate = $this->calculateChurnRate('30d') / 100;
        
        return $churnRate > 0 ? $avgMonthlyRevenue / $churnRate : 0;
    }

    private function calculateConversionRate(string $period): float
    {
        $startDate = $this->getStartDate($period);
        
        $totalUsers = User::where('created_at', '>=', $startDate)->count();
        $subscribers = Subscription::where('created_at', '>=', $startDate)->count();
        
        return $totalUsers > 0 ? ($subscribers / $totalUsers) * 100 : 0;
    }

    private function getRevenueByPlan(\DateTime $startDate): Collection
    {
        return Payment::where('status', PaymentStatus::Completed)
            ->where('paid_at', '>=', $startDate)
            ->join('subscriptions', 'payments.subscription_id', '=', 'subscriptions.id')
            ->select('subscriptions.plan', DB::raw('SUM(payments.amount) as total_revenue'))
            ->groupBy('subscriptions.plan')
            ->get();
    }

    private function getDailyRevenue(\DateTime $startDate): Collection
    {
        return Payment::where('status', PaymentStatus::Completed)
            ->where('paid_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(paid_at) as date'),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getPaymentMethodStats(\DateTime $startDate): Collection
    {
        return Payment::where('paid_at', '>=', $startDate)
            ->select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('method')
            ->orderBy('count', 'desc')
            ->get();
    }

    private function getPaymentSuccessRate(\DateTime $startDate): float
    {
        $totalPayments = Payment::where('created_at', '>=', $startDate)->count();
        $successfulPayments = Payment::where('status', PaymentStatus::Completed)
            ->where('created_at', '>=', $startDate)->count();
            
        return $totalPayments > 0 ? ($successfulPayments / $totalPayments) * 100 : 0;
    }

    private function getRefundAmount(\DateTime $startDate): float
    {
        return DB::table('refunds')
            ->where('status', 'completed')
            ->where('processed_at', '>=', $startDate)
            ->sum('amount');
    }

    private function getStartDate(string $period): \DateTime
    {
        return match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };
    }

    private function getActiveUsers(\DateTime $startDate): int
    {
        // Users who have logged in or performed actions since start date
        return User::where('last_login_at', '>=', $startDate)->count();
    }

    private function calculateRetentionRate(string $period): float
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();
        
        $usersAtStart = User::where('created_at', '<', $startDate)->count();
        $activeUsers = User::where('created_at', '<', $startDate)
            ->where('last_login_at', '>=', $startDate)
            ->count();
            
        return $usersAtStart > 0 ? ($activeUsers / $usersAtStart) * 100 : 0;
    }

    private function getDailySignups(\DateTime $startDate): Collection
    {
        return User::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as signups')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getUserEngagementStats(\DateTime $startDate): array
    {
        // Placeholder for engagement metrics
        // In real implementation, track user actions like module views, quiz attempts, etc.
        return [
            'avg_session_duration' => 0,
            'page_views_per_session' => 0,
            'bounce_rate' => 0,
            'feature_usage' => []
        ];
    }

    private function getFailedPaymentStats(\DateTime $startDate): array
    {
        $failedPayments = Payment::where('status', PaymentStatus::Failed)
            ->where('created_at', '>=', $startDate);
            
        return [
            'count' => $failedPayments->count(),
            'total_amount' => $failedPayments->sum('amount'),
            'common_reasons' => Payment::where('status', PaymentStatus::Failed)
                ->where('created_at', '>=', $startDate)
                ->selectRaw("JSON_EXTRACT(payment_details, '$.failure_reason') as reason, COUNT(*) as count")
                ->groupBy('reason')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    private function getAverageProcessingTime(\DateTime $startDate): float
    {
        return Payment::where('status', PaymentStatus::Completed)
            ->where('paid_at', '>=', $startDate)
            ->avg(DB::raw('TIMESTAMPDIFF(SECOND, created_at, paid_at)')) ?? 0;
    }

    private function getGeographicStats(\DateTime $startDate): Collection
    {
        // Placeholder - would require additional user location data
        // Could integrate with IP geolocation or user-provided address info
        return collect([]);
    }
}