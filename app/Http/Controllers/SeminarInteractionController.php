<?php

namespace App\Http\Controllers;

use App\Http\Requests\Seminars\StoreSeminarCommentRequest;
use App\Http\Requests\Seminars\StoreSeminarQuestionRequest;
use App\Models\Seminar;
use App\Services\Seminars\SeminarInteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SeminarInteractionController extends Controller
{
    public function __construct(private readonly SeminarInteractionService $interactions)
    {
    }

    public function storeComment(StoreSeminarCommentRequest $request, Seminar $seminar): JsonResponse|RedirectResponse
    {
        $comment = $this->interactions->postComment($request->user(), $seminar, $request->validated('body'));

        if ($request->expectsJson()) {
            return response()->json(['comment' => $comment], 201);
        }

        return back()->with('success', 'Comment posted.');
    }

    public function storeQuestion(StoreSeminarQuestionRequest $request, Seminar $seminar): JsonResponse|RedirectResponse
    {
        $question = $this->interactions->postQuestion($request->user(), $seminar, $request->validated('question'));

        if ($request->expectsJson()) {
            return response()->json(['question' => $question], 201);
        }

        return back()->with('success', 'Question posted.');
    }
}
