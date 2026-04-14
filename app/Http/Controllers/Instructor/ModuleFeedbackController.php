<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateModuleFeedbackReplyRequest;
use App\Models\Module;
use App\Models\ModuleFeedback;
use App\Services\ModuleFeedbackService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ModuleFeedbackController extends Controller
{
    public function __construct(
        private readonly ModuleFeedbackService $moduleFeedbackService,
    ) {
    }

    public function updateReply(
        UpdateModuleFeedbackReplyRequest $request,
        Module $module,
        ModuleFeedback $feedback,
    ): RedirectResponse {
        if ((int) $feedback->module_id !== (int) $module->id) {
            abort(404);
        }

        try {
            $this->moduleFeedbackService->upsertInstructorReply(
                $request->user(),
                $feedback,
                (string) $request->string('reply_content')
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Instructor reply saved.');
    }
}
