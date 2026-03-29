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
        $search = trim((string) $request->string('search'));
        if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $applications = InstructorApplication::query()
            ->with([
                'user.learnerProfile.city',
                'user.learnerProfile.barangayLocation',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('educational_background', 'like', '%' . $search . '%')
                        ->orWhere('bio', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhereHas('learnerProfile', function ($profileQuery) use ($search): void {
                                    $profileQuery->where('username', 'like', '%' . $search . '%')
                                        ->orWhere('barangay', 'like', '%' . $search . '%');
                                });
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.instructor-applications.index', [
            'status' => $status,
            'search' => $search,
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
        $this->service->reject(
            $application,
            (string) $request->string('rejection_reason_code'),
            $request->filled('rejection_reason_note') ? (string) $request->string('rejection_reason_note') : null
        );

        return redirect()->back()->with('success', 'Instructor application rejected successfully.');
    }
}
