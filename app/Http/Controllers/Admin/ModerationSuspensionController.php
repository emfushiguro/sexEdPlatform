<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterModerationSuspensionsRequest;
use App\Models\UserSuspension;
use App\Services\Admin\ModerationSuspensionDashboardService;
use Illuminate\View\View;

class ModerationSuspensionController extends Controller
{
    public function __construct(private readonly ModerationSuspensionDashboardService $dashboardService)
    {
    }

    public function index(FilterModerationSuspensionsRequest $request): View
    {
        $filters = $request->validated();
        $payload = $this->dashboardService->buildIndexPayload($filters);

        return view('admin.moderation.suspensions.index', [
            'suspensions' => $payload['suspensions'],
            'stats' => $payload['stats'],
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'role' => (string) ($filters['role'] ?? ''),
                'severity' => (string) ($filters['severity'] ?? ''),
                'trigger' => (string) ($filters['trigger'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'appeal_status' => (string) ($filters['appeal_status'] ?? ''),
                'sort' => (string) ($filters['sort'] ?? 'latest'),
                'per_page' => (int) ($filters['per_page'] ?? 15),
            ],
        ]);
    }

    public function show(UserSuspension $userSuspension): View
    {
        $payload = $this->dashboardService->buildShowPayload($userSuspension);

        return view('admin.moderation.suspensions.show', $payload);
    }
}
