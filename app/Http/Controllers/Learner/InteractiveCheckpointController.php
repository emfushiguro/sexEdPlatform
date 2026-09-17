<?php

namespace App\Http\Controllers\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\InteractiveCheckpointProgress;
use App\Models\QuizQuestion;
use App\Services\Learning\QuestionEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InteractiveCheckpointController extends Controller
{
    public function __construct(private QuestionEvaluator $questionEvaluator) {}

    public function submit(Request $request, QuizQuestion $question): JsonResponse
    {
        $question->load(['options', 'checkpointTopic.lesson.module']);
        $this->authorizeCheckpointAccess($question);

        if ($question->question_type === 'perspective_feedback') {
            return $this->submitPerspectiveFeedback($request, $question);
        }

        $progress = InteractiveCheckpointProgress::firstOrNew([
            'user_id' => Auth::id(),
            'quiz_question_id' => $question->id,
        ]);

        if ($progress->status === 'correct') {
            return response()->json([
                'status' => 'correct',
                'is_correct' => true,
                'result' => $progress->latest_answer,
                'explanation' => $question->explanation,
            ]);
        }

        $validated = $request->validate(['answer' => ['nullable']]);
        $result = $this->questionEvaluator->evaluate($question, $validated['answer'] ?? null);
        $status = $result['is_correct'] ? 'correct' : 'incorrect';

        $progress->fill([
            'lesson_topic_id' => $question->checkpoint_topic_id,
            'checkpoint_block_uuid' => $question->checkpoint_block_uuid,
            'status' => $status,
            'latest_answer' => $result,
            'is_correct' => $result['is_correct'],
            'attempt_count' => ((int) $progress->attempt_count) + 1,
            'answered_at' => now(),
            'skipped_at' => null,
            'completed_at' => $result['is_correct'] ? now() : null,
        ])->save();

        return response()->json([
            'status' => $status,
            'is_correct' => $result['is_correct'],
            'result' => $result,
            'explanation' => $result['is_correct'] ? $question->explanation : null,
        ]);
    }

    public function skip(QuizQuestion $question): JsonResponse
    {
        $question->load(['checkpointTopic.lesson.module']);
        $this->authorizeCheckpointAccess($question);

        $progress = InteractiveCheckpointProgress::firstOrNew([
            'user_id' => Auth::id(),
            'quiz_question_id' => $question->id,
        ]);

        if ($progress->status === 'correct') {
            return response()->json([
                'status' => 'correct',
                'is_correct' => true,
                'result' => $progress->latest_answer,
                'explanation' => $question->explanation,
            ]);
        }

        if ($question->question_type === 'perspective_feedback' && $progress->status === 'completed') {
            return $this->perspectiveFeedbackResponse($question, $progress);
        }

        $progress->fill([
            'lesson_topic_id' => $question->checkpoint_topic_id,
            'checkpoint_block_uuid' => $question->checkpoint_block_uuid,
            'status' => 'skipped',
            'latest_answer' => null,
            'is_correct' => null,
            'skipped_at' => now(),
            'completed_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'skipped',
            'is_correct' => null,
            'explanation' => null,
        ]);
    }

    private function authorizeCheckpointAccess(QuizQuestion $question): void
    {
        abort_unless($question->checkpoint_topic_id !== null, 404);

        $topic = $question->checkpointTopic;
        $lesson = $topic?->lesson;
        $module = $lesson?->module;

        abort_unless($topic && $lesson && $module, 404);
        abort_unless($lesson->is_published, 404);
        abort_unless($module->isLearnerVisible(), 403);

        $isEnrolled = Auth::user()->moduleEnrollments()
            ->where('module_id', $module->id)
            ->where('status', EnrollmentStatus::Approved)
            ->exists();

        abort_unless($isEnrolled, 403);
    }

    private function submitPerspectiveFeedback(
        Request $request,
        QuizQuestion $question,
    ): JsonResponse {
        $limit = (int) ($question->perspective_character_limit ?: 1000);

        return DB::transaction(function () use ($request, $question, $limit): JsonResponse {
            $progress = InteractiveCheckpointProgress::firstOrCreate([
                'user_id' => Auth::id(),
                'quiz_question_id' => $question->id,
            ], [
                'lesson_topic_id' => $question->checkpoint_topic_id,
                'checkpoint_block_uuid' => $question->checkpoint_block_uuid,
                'status' => 'not_attempted',
            ]);
            $progress = InteractiveCheckpointProgress::query()
                ->whereKey($progress->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($progress->status === 'completed') {
                return $this->perspectiveFeedbackResponse($question, $progress);
            }

            $validated = $request->validate([
                'answer' => ['required', 'array'],
                'answer.pathway' => ['required', 'in:guided,own'],
                'answer.option_id' => ['nullable', 'required_if:answer.pathway,guided', 'integer'],
                'answer.perspective_text' => [
                    'nullable',
                    'required_if:answer.pathway,own',
                    'string',
                    'max:'.$limit,
                ],
            ]);
            $answer = $validated['answer'];

            if ($answer['pathway'] === 'guided') {
                $option = $question->options->firstWhere('id', (int) $answer['option_id']);
                if (! $option || trim((string) $option->feedback) === '') {
                    throw ValidationException::withMessages([
                        'answer.option_id' => 'Select a response that belongs to this checkpoint.',
                    ]);
                }

                $snapshot = [
                    'pathway' => 'guided',
                    'option_id' => (int) $option->id,
                    'option_text' => $option->option_text,
                    'feedback' => $option->feedback,
                ];
            } else {
                if (! $question->allow_own_perspective) {
                    throw ValidationException::withMessages([
                        'answer.pathway' => 'Sharing your own perspective is not enabled for this checkpoint.',
                    ]);
                }

                $snapshot = [
                    'pathway' => 'own',
                    'perspective_text' => $answer['perspective_text'],
                ];
            }

            $progress->fill([
                'lesson_topic_id' => $question->checkpoint_topic_id,
                'checkpoint_block_uuid' => $question->checkpoint_block_uuid,
                'status' => 'completed',
                'latest_answer' => $snapshot,
                'is_correct' => null,
                'attempt_count' => ((int) $progress->attempt_count) + 1,
                'answered_at' => now(),
                'skipped_at' => null,
                'completed_at' => now(),
            ])->save();

            return $this->perspectiveFeedbackResponse($question, $progress);
        });
    }

    private function perspectiveFeedbackResponse(
        QuizQuestion $question,
        InteractiveCheckpointProgress $progress,
    ): JsonResponse {
        $result = $progress->latest_answer ?? [];

        return response()->json([
            'status' => 'completed',
            'is_correct' => null,
            'pathway' => $result['pathway'] ?? null,
            'result' => $result,
            'feedback' => ($result['pathway'] ?? null) === 'guided'
                ? ($result['feedback'] ?? null)
                : null,
            'explanation' => $question->explanation,
        ]);
    }
}
