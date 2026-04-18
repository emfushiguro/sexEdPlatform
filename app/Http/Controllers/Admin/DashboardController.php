<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboardService)
    {
    }

    public function index()
    {
        $payload = $this->dashboardService->getCommandCenterPayload();

        return view('admin.dashboard', [
            'overviewCards' => $payload['overview_cards'] ?? [],
            'snapshotMetrics' => $payload['snapshot_metrics'],
            'moderationQueues' => $payload['moderation_queues'],
            'recentActivity' => $payload['recent_activity'],
            'dashboardAnalytics' => $payload['analytics'] ?? [],
            'learnerDemographics' => $payload['learner_demographics'] ?? [],
        ]);
    }
}
