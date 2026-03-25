<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectInstructorApplicationRequest;
use App\Models\InstructorApplication;
use App\Services\InstructorApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorApplicationController extends Controller
{
    public function __construct(private readonly InstructorApplicationService $service)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString() ?: 'pending';
        if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $applications = InstructorApplication::query()
            ->with('user')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.instructor-applications.index', [
            'status' => $status,
            'applications' => $applications,
            'pendingCount' => InstructorApplication::pending()->count(),
            'approvedCount' => InstructorApplication::approved()->count(),
            'rejectedCount' => InstructorApplication::rejected()->count(),
        ]);
    }

    public function show(InstructorApplication $application): View
    {
        $application->load(['user.learnerProfile', 'user.gamification', 'approvedBy']);
        $user = $application->user;

        $snapshot = [
            'enrolled_modules_count' => $user->moduleEnrollments()->count(),
            'certificates_earned' => $user->certificates()->count(),
            'gamification_level' => $user->gamification?->level,
            'gamification_score' => $user->gamification?->score,
            'subscription_status' => $user->subscription?->status ?? 'none',
        ];

        return view('admin.instructor-applications.show', [
            'application' => $application,
            'snapshot' => $snapshot,
        ]);
    }

    public function approve(InstructorApplication $application): RedirectResponse
    {
        $this->service->approve($application);

        return redirect()->back()->with('success', 'Instructor application approved successfully.');
    }

    public function reject(RejectInstructorApplicationRequest $request, InstructorApplication $application): RedirectResponse
    {
        $this->service->reject($application, (string) $request->string('rejection_reason'));

        return redirect()->back()->with('success', 'Instructor application rejected successfully.');
    }
}
