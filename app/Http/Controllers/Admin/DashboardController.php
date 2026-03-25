<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstructorApplication;
use App\Services\AdminDashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboardService)
    {
    }

    public function index()
    {
        $metrics = $this->dashboardService->getHybridCommandCenterMetrics();

        return view('admin.dashboard', [
            'riskMetrics' => $metrics['risk'],
            'leakageMetrics' => $metrics['leakage'],
            'growthMetrics' => $metrics['growth'],
            'pendingInstructorApplications' => InstructorApplication::pending()->count(),
        ]);
    }
}
