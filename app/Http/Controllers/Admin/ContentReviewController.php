<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectModuleReviewRequest;
use App\Models\ModuleReviewRequest;
use App\Services\ContentGovernanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContentReviewController extends Controller
{
    public function __construct(
        private readonly ContentGovernanceService $contentGovernanceService,
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
        );

        return redirect()->route('admin.content-reviews.show', $reviewRequest)
            ->with('success', 'Content review rejected with feedback.');
    }
}
