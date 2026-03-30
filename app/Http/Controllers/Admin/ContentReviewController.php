<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmInstructorPenaltyRequest;
use App\Http\Requests\Admin\RejectModuleReviewRequest;
use App\Models\ModuleReviewRequest;
use App\Services\ContentGovernanceService;
use App\Services\InstructorModerationPenaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentReviewController extends Controller
{
    public function __construct(
        private readonly ContentGovernanceService $contentGovernanceService,
        private readonly InstructorModerationPenaltyService $instructorModerationPenaltyService,
    ) {
    }

    public function index(): View
    {
        $reviewRequests = ModuleReviewRequest::query()
            ->with(['module', 'revision'])
            ->latest('submitted_at')
            ->get();

        return view('admin.content-reviews.index', compact('reviewRequests'));
    }

    public function show(ModuleReviewRequest $reviewRequest): View
    {
        $reviewRequest->load(['module.publisher', 'module.publishedRevision', 'revision.submitter', 'reviewer']);

        return view('admin.content-reviews.show', compact('reviewRequest'));
    }

    public function approve(ModuleReviewRequest $reviewRequest): RedirectResponse
    {
        $this->contentGovernanceService->approveReview($reviewRequest, request()->user());

        return redirect()->route('admin.content-reviews.show', $reviewRequest)
            ->with('success', 'Content review approved.');
    }

    public function reject(RejectModuleReviewRequest $request, ModuleReviewRequest $reviewRequest): RedirectResponse
    {
        $this->contentGovernanceService->rejectReview(
            $reviewRequest,
            $request->user(),
            $request->string('feedback')->toString(),
            $request->input('reason_code'),
            $request->input('guidance_note'),
        );

        return redirect()->route('admin.content-reviews.show', $reviewRequest)
            ->with('success', 'Content review rejected with feedback.');
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
