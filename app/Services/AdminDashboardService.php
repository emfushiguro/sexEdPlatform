<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\InstructorApplication;
use Illuminate\Support\Facades\Schema;

class AdminDashboardService
{
    public function getHybridCommandCenterMetrics(): array
    {
        return [
            'risk' => [
                [
                    'label' => 'Failed Renewals Today',
                    'value' => Payment::where('status', 'failed')->whereDate('created_at', today())->count(),
                    'cta_label' => 'View Payments',
                    'cta_route' => route('admin.payments.index', ['status' => 'failed']),
                ],
                [
                    'label' => 'Grace Ending in 72h',
                    'value' => $this->countGraceEndingSoon(),
                    'cta_label' => 'View Subscribers',
                    'cta_route' => route('admin.subscribers.index', ['status' => 'grace_period']),
                ],
            ],
            'leakage' => [
                [
                    'label' => 'Cancelled Subscriptions',
                    'value' => Subscription::where('status', 'cancelled')->count(),
                    'cta_label' => 'View Subscribers',
                    'cta_route' => route('admin.subscribers.index', ['status' => 'cancelled']),
                ],
                [
                    'label' => 'Past Due Subscriptions',
                    'value' => $this->countPastDueLike(),
                    'cta_label' => 'View Subscribers',
                    'cta_route' => route('admin.subscribers.index', ['status' => 'past_due']),
                ],
            ],
            'growth' => [
                [
                    'label' => 'New Premium Conversions (30d)',
                    'value' => Subscription::where('created_at', '>=', now()->subDays(30))
                        ->whereHas('plan', fn ($q) => $q->where('price', '>', 0))
                        ->count(),
                    'cta_label' => 'View Subscribers',
                    'cta_route' => route('admin.subscribers.index'),
                ],
                [
                    'label' => 'Active Learners',
                    'value' => User::role('learner')->where('status', 'active')->count(),
                    'cta_label' => 'View Subscribers',
                    'cta_route' => route('admin.subscribers.index', ['status' => 'active']),
                ],
                [
                    'label' => 'Active Plans',
                    'value' => SubscriptionPlan::where('is_active', true)->count(),
                    'cta_label' => 'View Plans',
                    'cta_route' => route('admin.subscription-plans.index'),
                ],
                [
                    'label' => 'Pending Instructor Applications',
                    'value' => InstructorApplication::pending()->count(),
                    'cta_label' => 'Review Applications',
                    'cta_route' => route('admin.instructor-applications.index'),
                ],
            ],
        ];
    }

    private function countGraceEndingSoon(): int
    {
        $query = Subscription::query();

        if (!Schema::hasColumn('subscribers', 'status')) {
            return 0;
        }

        if (Schema::hasColumn('subscribers', 'grace_ends_at')) {
            return $query->where('status', 'grace_period')
                ->whereNotNull('grace_ends_at')
                ->whereBetween('grace_ends_at', [now(), now()->addHours(72)])
                ->count();
        }

        if (Schema::hasColumn('subscribers', 'grace_period_ends')) {
            return $query->where('status', 'past_due')
                ->whereNotNull('grace_period_ends')
                ->whereBetween('grace_period_ends', [now(), now()->addHours(72)])
                ->count();
        }

        return 0;
    }

    private function countPastDueLike(): int
    {
        if (!Schema::hasColumn('subscribers', 'status')) {
            return 0;
        }

        // Use legacy-safe statuses so this metric works during migration windows.
        return Subscription::whereIn('status', ['past_due', 'grace_period'])->count();
    }
}
