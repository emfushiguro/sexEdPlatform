<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SeminarStatus;
use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\Seminar;
use App\Models\SeminarComment;
use App\Models\SeminarQuestion;
use App\Services\Seminars\SeminarInteractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarModerationController extends Controller
{
    public function __construct(private readonly SeminarInteractionService $interactions)
    {
    }

    public function index(Request $request): View
    {
        $seminars = Seminar::query()
            ->with('connector')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('connector_id'), fn ($query) => $query->where('connector_id', $request->integer('connector_id')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('moderation_status'), fn ($query) => $query->where('admin_moderation_status', $request->string('moderation_status')))
            ->when($request->filled('date'), fn ($query) => $query->whereDate('starts_at', $request->date('date')))
            ->latest('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.seminars.index', [
            'seminars' => $seminars,
            'connectors' => Connector::query()->orderBy('name')->get(['id', 'name']),
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
            ]),
        ]);
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
}
