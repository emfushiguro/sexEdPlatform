<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmInstructorPenaltyRequest;
use App\Http\Requests\Admin\RejectModuleReviewRequest;
use App\Models\ModuleReviewRequest;
use App\Services\AdminModuleReviewWorkspaceService;
use App\Services\ContentGovernanceService;
use App\Services\InstructorModerationPenaltyService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class ContentReviewController extends Controller
{
    public function __construct(
        private readonly ContentGovernanceService $contentGovernanceService,
        private readonly InstructorModerationPenaltyService $instructorModerationPenaltyService,
        private readonly AdminModuleReviewWorkspaceService $workspaceService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $statusFilter = (string) $request->string('status');
        $instructorFilter = $request->filled('instructor_id')
            ? (int) $request->integer('instructor_id')
            : null;
        $submittedDate = (string) $request->string('submitted_date');

        $reviewRequests = ModuleReviewRequest::query()
            ->with(['module', 'revision', 'submitter'])
            ->whereIn('status', ['submitted', 'in_review'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('status', 'like', '%' . $search . '%')
                        ->orWhereHas('module', function ($moduleQuery) use ($search): void {
                            $moduleQuery->where('title', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('submitter', function ($submitterQuery) use ($search): void {
                            $submitterQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when(in_array($statusFilter, ['submitted', 'in_review'], true), function ($query) use ($statusFilter): void {
                $query->where('status', $statusFilter);
            })
            ->when(($instructorFilter ?? 0) > 0, function ($query) use ($instructorFilter): void {
                $query->where('submitted_by', $instructorFilter);
            })
            ->when($submittedDate !== '', function ($query) use ($submittedDate): void {
                $query->whereDate('submitted_at', $submittedDate);
            })
            ->latest('submitted_at')
            ->paginate(12)
            ->withQueryString();

        $instructors = \App\Models\User::query()
            ->where('role', 'instructor')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.content-reviews.index', compact(
            'reviewRequests',
            'instructors',
            'search',
            'statusFilter',
            'instructorFilter',
            'submittedDate',
        ));
    }

    public function show(ModuleReviewRequest $reviewRequest): View
    {
        $reviewRequest->load(['module.publisher', 'module.publishedRevision', 'revision.submitter', 'reviewer']);

        $workspace = $this->workspaceService->compose($reviewRequest);

        $moderationHistory = ModuleReviewRequest::query()
            ->with(['submitter:id,name', 'reviewer:id,name'])
            ->where('module_id', $reviewRequest->module_id)
            ->latest('submitted_at')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.content-reviews.show', compact('reviewRequest', 'workspace', 'moderationHistory'));
    }

    public function startReview(ModuleReviewRequest $reviewRequest): RedirectResponse
    {
        if ($reviewRequest->status === 'in_review') {
            return redirect()->route('admin.content-reviews.show', $reviewRequest)
                ->with('info', 'This submission is already under review.');
        }

        if ($reviewRequest->status !== 'submitted') {
            return redirect()->route('admin.content-reviews.show', $reviewRequest)
                ->with('warning', 'Only submitted modules can be moved to under review.');
        }

        $this->contentGovernanceService->startReview($reviewRequest, request()->user());

        return redirect()->route('admin.content-reviews.show', $reviewRequest)
            ->with('success', 'Module status updated to Under Review.');
    }

    public function approve(ModuleReviewRequest $reviewRequest): RedirectResponse
    {
        if ($reviewRequest->status !== 'in_review') {
            return redirect()->route('admin.content-reviews.show', $reviewRequest)
                ->with('warning', 'This submission is finalized and can no longer be moderated.');
        }

        $validated = request()->validate([
            'moderation_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        try {
            $this->contentGovernanceService->approveReview(
                $reviewRequest,
                request()->user(),
                $validated['moderation_notes'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('admin.content-reviews.show', $reviewRequest)
                ->with('warning', $exception->getMessage());
        }

        return redirect()->route('admin.content-reviews.show', $reviewRequest)
            ->with('success', 'Content review approved.');
    }

    public function reject(RejectModuleReviewRequest $request, ModuleReviewRequest $reviewRequest): RedirectResponse
    {
        if ($reviewRequest->status !== 'in_review') {
            return redirect()->route('admin.content-reviews.show', $reviewRequest)
                ->with('warning', 'This submission is finalized and can no longer be moderated.');
        }

        $this->contentGovernanceService->rejectReview(
            $reviewRequest,
            $request->user(),
            $request->string('feedback')->toString(),
            $request->input('reason_code'),
            $request->input('guidance_note'),
            $request->boolean('issue_warning'),
            $request->input('moderation_notes'),
        );

        return redirect()->route('admin.content-reviews.show', $reviewRequest)
            ->with('success', 'Content review rejected with feedback.');
    }

    public function archive(ModuleReviewRequest $reviewRequest): RedirectResponse
    {
        if ($reviewRequest->status !== 'in_review') {
            return redirect()->route('admin.content-reviews.show', $reviewRequest)
                ->with('warning', 'This submission is finalized and can no longer be moderated.');
        }

        $this->contentGovernanceService->archiveReview(
            $reviewRequest,
            request()->user(),
            'Submission archived by admin moderation.',
        );

        return redirect()->route('admin.content-reviews.index')
            ->with('success', 'Content review archived.');
    }

    public function confirmPenalty(ConfirmInstructorPenaltyRequest $request, ModuleReviewRequest $reviewRequest): RedirectResponse
    {
        $reviewRequest->loadMissing(['module', 'violationRecords']);

        $instructor = $reviewRequest->module?->created_by
            ? \App\Models\User::query()->find($reviewRequest->module->created_by)
            : null;

        $latestViolation = $reviewRequest->violationRecords()
            ->latest('id')
            ->first();

        if (!$instructor || !$latestViolation) {
            return redirect()->route('admin.content-reviews.show', $reviewRequest)
                ->with('error', 'No violation record available for penalty confirmation.');
        }

        $this->instructorModerationPenaltyService->applyConfirmedAction(
            $instructor,
            $latestViolation,
            $request->string('action')->toString(),
            $request->user(),
        );

        return redirect()->route('admin.content-reviews.show', $reviewRequest)
            ->with('success', 'Penalty action confirmed.');
    }
}
