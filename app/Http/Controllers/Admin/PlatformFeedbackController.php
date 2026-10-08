<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlatformFeedbackStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexPlatformFeedbackRequest;
use App\Http\Requests\Admin\UpdatePlatformFeedbackRequest;
use App\Models\PlatformFeedback;
use App\Notifications\PlatformFeedbackUpdatedNotification;
use App\Services\Support\PlatformFeedbackInsights;
use App\Services\Support\PlatformFeedbackLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformFeedbackController extends Controller
{
    public function index(IndexPlatformFeedbackRequest $request, PlatformFeedbackInsights $insights): View
    {
        $filters = $request->validated();

        return view('admin.feedback.index', [
            'feedback' => PlatformFeedback::query()
                ->with(['user.learnerProfile', 'user.instructorProfile', 'user.profile'])
                ->forAdminFilters($filters)
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'insights' => $insights->summary(),
            'filters' => $filters,
        ]);
    }

    public function show(PlatformFeedback $platformFeedback): View
    {
        return view('admin.feedback.show', [
            'feedback' => $platformFeedback->load(['user.learnerProfile', 'user.instructorProfile', 'user.profile', 'histories.actor', 'messages.sender']),
        ]);
    }

    public function open(Request $request, PlatformFeedback $platformFeedback, PlatformFeedbackLifecycleService $lifecycle): RedirectResponse
    {
        $this->authorize('update', $platformFeedback);

        if ($platformFeedback->status === PlatformFeedbackStatus::New) {
            $lifecycle->transition($platformFeedback, PlatformFeedbackStatus::Reviewed, $request->user());
        }

        return redirect()->route('admin.feedback.show', $platformFeedback);
    }

    public function update(UpdatePlatformFeedbackRequest $request, PlatformFeedback $platformFeedback, PlatformFeedbackLifecycleService $lifecycle): RedirectResponse
    {
        $data = $request->validated();
        $target = PlatformFeedbackStatus::from($data['status']);
        $changed = $platformFeedback->status !== $target;
        $updated = DB::transaction(function () use ($platformFeedback, $target, $request, $lifecycle): PlatformFeedback {
            $current = $platformFeedback->fresh();
            if ($target === PlatformFeedbackStatus::Resolved && $current->status === PlatformFeedbackStatus::New) {
                $current = $lifecycle->transition($current, PlatformFeedbackStatus::Reviewed, $request->user());
            }

            return $lifecycle->transition($current, $target, $request->user());
        });
        if ($changed) {
            $updated->user?->notify(new PlatformFeedbackUpdatedNotification($updated));
        }

        return back()->with('success', 'Support ticket updated.');
    }
}
