<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learner\StoreModuleFeedbackRequest;
use App\Models\Module;
use App\Services\ModuleFeedbackService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ModuleFeedbackController extends Controller
{
    public function __construct(
        private readonly ModuleFeedbackService $moduleFeedbackService,
    ) {
    }

    public function store(StoreModuleFeedbackRequest $request, Module $module): RedirectResponse
    {
        try {
            $this->moduleFeedbackService->upsertLearnerFeedback(
                $request->user(),
                $module,
                (int) $request->integer('rating'),
                (string) $request->string('review_content')
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Your review has been saved. Thank you for your feedback.');
    }
}
