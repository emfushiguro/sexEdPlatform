<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveInstructorApplicationRequest;
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
                'approvedBy',
                'latestReview.reviewedBy',
                'reviews' => function ($reviewQuery): void {
                    $reviewQuery->with('reviewedBy')->latest('reviewed_at');
                },
                'user' => function ($userQuery): void {
                    $userQuery->withCount([
                        'moduleEnrollments as enrolled_modules_count',
                        'moduleEnrollments as finished_modules_count' => fn ($enrollmentQuery) => $enrollmentQuery->whereNotNull('completed_at'),
                        'certificates as certificates_earned_count',
                    ])->with([
                        'learnerProfile.city',
                        'learnerProfile.barangayLocation',
                        'moduleEnrollments' => fn ($enrollmentQuery) => $enrollmentQuery
                            ->whereNotNull('completed_at')
                            ->with('module:id,title')
                            ->latest('completed_at'),
                    ]);
                },
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

        $focusApplicationId = $request->integer('focus');
        if ($focusApplicationId <= 0) {
            $focusApplicationId = null;
        }

        return view('admin.instructor-applications.index', [
            'status' => $status,
            'search' => $search,
            'focusApplicationId' => $focusApplicationId,
            'applications' => $applications,
            'pendingCount' => InstructorApplication::pending()->count(),
            'approvedCount' => InstructorApplication::approved()->count(),
            'rejectedCount' => InstructorApplication::rejected()->count(),
            'hasPendingOnPage' => $applications->getCollection()->contains(fn (InstructorApplication $application): bool => $application->status === 'pending'),
            'defaultApprovalMessage' => InstructorApplicationService::defaultApprovalMessage(),
            'defaultRejectionMessage' => InstructorApplicationService::defaultRejectionMessage(),
        ]);
    }

    public function show(InstructorApplication $application): RedirectResponse
    {
        return redirect()->route('admin.instructor-applications.index', [
            'status' => $application->status,
            'focus' => $application->id,
        ]);
    }

    public function approve(ApproveInstructorApplicationRequest $request, InstructorApplication $application): RedirectResponse
    {
        $this->service->approve(
            $application,
            (string) $request->string('admin_message')
        );

        return redirect()->back()->with('success', 'Instructor application approved successfully.');
    }

    public function reject(RejectInstructorApplicationRequest $request, InstructorApplication $application): RedirectResponse
    {
        $this->service->reject(
            $application,
            (string) $request->string('rejection_reason_code'),
            $request->filled('rejection_reason_note') ? (string) $request->string('rejection_reason_note') : null,
            (string) $request->string('admin_message')
        );

        return redirect()->back()->with('success', 'Instructor application rejected successfully.');
    }

    public function archive(InstructorApplication $application): RedirectResponse
    {
        if ($application->trashed()) {
            return redirect()->back()->with('info', 'Instructor application is already archived.');
        }

        $application->delete();

        return redirect()->back()->with('success', 'Instructor application archived successfully.');
    }

    public function destroy(InstructorApplication $application): RedirectResponse
    {
        if (! in_array($application->status, ['rejected', 'approved'], true)) {
            return redirect()->back()->with('error', 'Only reviewed applications can be permanently deleted.');
        }

        $application->forceDelete();

        return redirect()->back()->with('success', 'Instructor application permanently deleted.');
    }
}
