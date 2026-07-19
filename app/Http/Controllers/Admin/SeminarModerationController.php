<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SeminarStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectSeminarRequest;
use App\Models\Seminar;
use App\Models\SeminarComment;
use App\Models\SeminarQuestion;
use App\Notifications\Seminars\SeminarCancelledNotification;
use App\Services\Seminars\SeminarInteractionService;
use App\Services\Seminars\SeminarLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarModerationController extends Controller
{
    public function __construct(
        private readonly SeminarInteractionService $interactions,
        private readonly SeminarLifecycleService $lifecycle,
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->string('status', SeminarStatus::PendingReview->value)->toString();
        $allowedStatuses = collect(SeminarStatus::cases())->map->value->all();
        abort_unless(in_array($status, $allowedStatuses, true), 404);

        $seminars = Seminar::query()
            ->with(['connector', 'speakers.user'])
            ->where('status', $status)
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('speakers', fn ($speakerQuery) => $speakerQuery->where('display_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->latest('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.seminars.index', [
            'seminars' => $seminars,
            'stats' => [
                'total' => Seminar::query()->count(),
                'pending' => Seminar::query()->where('status', SeminarStatus::PendingReview->value)->count(),
                'approved' => Seminar::query()->where('status', SeminarStatus::Approved->value)->count(),
                'rejected' => Seminar::query()->where('status', SeminarStatus::Rejected->value)->count(),
            ],
            'status' => $status,
        ]);
    }

    public function show(Seminar $seminar): View
    {
        return view('admin.seminars.show', [
            'seminar' => $seminar->load([
                'connector',
                'registrants.user',
                'attendances.user',
                'comments.user',
                'questions.user',
                'speakers.user',
                'moderationReviews.moderator',
            ]),
        ]);
    }

    public function approve(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->lifecycle->approve($seminar, $request->user());

        return back()->with('success', 'Seminar approved.');
    }

    public function reject(RejectSeminarRequest $request, Seminar $seminar): RedirectResponse
    {
        $validated = $request->validated();
        $this->lifecycle->reject($seminar, $request->user(), $validated['reason'], $validated['note'] ?? null);

        return back()->with('success', 'Seminar rejected.');
    }

    public function cancel(Request $request, Seminar $seminar): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $seminar->update([
            'status' => SeminarStatus::Cancelled->value,
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->id,
            'cancellation_reason' => $validated['reason'],
            'admin_moderation_status' => 'cancelled_by_admin',
            'admin_moderation_reason' => $validated['reason'],
        ]);

        $this->notifyActiveRegistrantsAboutCancellation($seminar->fresh('connector'), $validated['reason']);

        return back()->with('success', 'Seminar cancelled.');
    }

    public function hideComment(Request $request, Seminar $seminar, SeminarComment $comment): RedirectResponse
    {
        abort_unless((int) $comment->seminar_id === (int) $seminar->id, 404);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->interactions->hideComment($request->user(), $comment, $validated['reason']);

        return back()->with('success', 'Comment hidden.');
    }

    public function hideQuestion(Request $request, Seminar $seminar, SeminarQuestion $question): RedirectResponse
    {
        abort_unless((int) $question->seminar_id === (int) $seminar->id, 404);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->interactions->hideQuestion($request->user(), $question, $validated['reason']);

        return back()->with('success', 'Question hidden.');
    }

    public function answerQuestion(Request $request, Seminar $seminar, SeminarQuestion $question): RedirectResponse
    {
        abort_unless((int) $question->seminar_id === (int) $seminar->id, 404);
        $validated = $request->validate(['answer' => ['required', 'string', 'max:2000']]);
        $this->interactions->markQuestionAnswered($request->user(), $question, $validated['answer']);

        return back()->with('success', 'Question answered.');
    }

    private function notifyActiveRegistrantsAboutCancellation(Seminar $seminar, string $reason): void
    {
        $seminar->registrants()
            ->active()
            ->with('user')
            ->chunkById(100, function ($registrants) use ($seminar, $reason): void {
                foreach ($registrants as $registrant) {
                    $registrant->user?->notify(new SeminarCancelledNotification($seminar, $reason));
                }
            });
    }
}
