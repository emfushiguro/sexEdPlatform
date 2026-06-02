<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\Seminar;
use App\Models\SeminarComment;
use App\Models\SeminarQuestion;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarInteractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SeminarInteractionController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarInteractionService $interactions,
    ) {
    }

    public function hideComment(Request $request, Connector $connector, Seminar $seminar, SeminarComment $comment): RedirectResponse
    {
        $this->authorizeModeration($request, $connector, $seminar);
        abort_unless((int) $comment->seminar_id === (int) $seminar->id, 404);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->interactions->hideComment($request->user(), $comment, $validated['reason']);

        return back()->with('success', 'Comment hidden.');
    }

    public function hideQuestion(Request $request, Connector $connector, Seminar $seminar, SeminarQuestion $question): RedirectResponse
    {
        $this->authorizeModeration($request, $connector, $seminar);
        abort_unless((int) $question->seminar_id === (int) $seminar->id, 404);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->interactions->hideQuestion($request->user(), $question, $validated['reason']);

        return back()->with('success', 'Question hidden.');
    }

    public function answerQuestion(Request $request, Connector $connector, Seminar $seminar, SeminarQuestion $question): RedirectResponse
    {
        $this->authorizeModeration($request, $connector, $seminar);
        abort_unless((int) $question->seminar_id === (int) $seminar->id, 404);

        $validated = $request->validate(['answer' => ['required', 'string', 'max:2000']]);
        $this->interactions->markQuestionAnswered($request->user(), $question, $validated['answer']);

        return back()->with('success', 'Question answered.');
    }

    private function authorizeModeration(Request $request, Connector $connector, Seminar $seminar): void
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
    }
}
