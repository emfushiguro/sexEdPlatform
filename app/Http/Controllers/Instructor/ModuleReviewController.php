<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\SubmitModuleForReviewRequest;
use App\Models\Module;
use App\Services\ContentGovernanceService;

class ModuleReviewController extends Controller
{
    public function __construct(
        private readonly ContentGovernanceService $contentGovernanceService,
    ) {
    }

    public function submit(SubmitModuleForReviewRequest $request, Module $module)
    {
        $this->contentGovernanceService->submitForReview($module, $request->user());

        return redirect()->route('instructor.modules.show', $module)
            ->with('success', 'Module submitted for admin review.');
    }

    public function resubmit(SubmitModuleForReviewRequest $request, Module $module)
    {
        $this->contentGovernanceService->submitForReview($module, $request->user());

        return redirect()->route('instructor.modules.show', $module)
            ->with('success', 'Module resubmitted for admin review.');
    }
}
