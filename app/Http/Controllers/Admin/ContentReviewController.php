<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmInstructorPenaltyRequest;
use App\Http\Requests\Admin\RejectModuleReviewRequest;
use App\Models\ModuleReviewRequest;
use App\Services\AdminModuleReviewWorkspaceService;
use App\Services\ContentGovernanceService;
use App\Services\InstructorModerationPenaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentReviewController extends Controller
{
    public function __construct(
        private readonly ContentGovernanceService $contentGovernanceService,
        private readonly InstructorModerationPenaltyService $instructorModerationPenaltyService,
        private readonly AdminModuleReviewWorkspaceService $workspaceService,
    ) {
    }

    public function index(): View
    {
        $reviewRequests = ModuleReviewRequest::query()
            ->with(['module', 'revision', 'submitter'])
            ->where('status', 'in_review')
            ->latest('submitted_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.content-reviews.index', compact('reviewRequests'));
    }

    public function show(ModuleReviewRequest $reviewRequest): View
    {
        $reviewRequest->load(['module.publisher', 'module.publishedRevision', 'revision.submitter', 'reviewer']);

        $workspace = $this->workspaceService->compose($reviewRequest);

        return view('admin.content-reviews.show', compact('reviewRequest', 'workspace'));
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

        $this->contentGovernanceService->approveReview(
            $reviewRequest,
            request()->user(),
            $validated['moderation_notes'] ?? null,
        );

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
