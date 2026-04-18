<?php

namespace App\Services;

use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstructorAssessmentInsightsService
{
    /**
    * @return array{scoreDistributionByModule: array<int, array<string, mixed>>, attemptCountByLearner: array<int, array<string, mixed>>, atRiskLearners: array<int, array<string, mixed>>, recentAttemptPreviews: array<int, array<string, mixed>>}
     */
    public function buildInsights(User $instructor, int $lowScoreThreshold = 60, int $lowActivityThreshold = 2): array
    {
        $moduleIds = Module::query()
            ->where('created_by', $instructor->id)
            ->pluck('id');

        if ($moduleIds->isEmpty()) {
            return [
                'scoreDistributionByModule' => [],
                'attemptCountByLearner' => [],
                'atRiskLearners' => [],
                'recentAttemptPreviews' => [],
            ];
        }

        $scoreDistribution = $this->scoreDistributionByModule($moduleIds, $lowScoreThreshold);
        $attemptCounts = $this->attemptCountByLearner($moduleIds);
        $recentAttemptPreviews = $this->recentAttemptPreviews($moduleIds);

        $atRiskLearners = $attemptCounts
            ->filter(function (array $entry) use ($lowScoreThreshold, $lowActivityThreshold): bool {
                return ((float) ($entry['avg_score'] ?? 0.0) < $lowScoreThreshold)
                    && ((int) ($entry['attempt_count'] ?? 0) <= $lowActivityThreshold);
            })
            ->map(function (array $entry): array {
                $entry['is_at_risk'] = true;

                return $entry;
            })
            ->values()
            ->all();

        return [
            'scoreDistributionByModule' => $scoreDistribution->all(),
            'attemptCountByLearner' => $attemptCounts->all(),
            'atRiskLearners' => $atRiskLearners,
            'recentAttemptPreviews' => $recentAttemptPreviews,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentAttemptPreviews(Collection $moduleIds, int $limit = 12): array
    {
        return QuizAttempt::query()
            ->whereHas('quiz', fn ($query) => $query->whereIn('module_id', $moduleIds))
            ->with([
                'user:id,first_name,last_name,name,email',
                'quiz:id,module_id,title',
                'quiz.module:id,title',
                'quiz.questions:id,quiz_id,question_text,question_type,order',
                'quiz.questions.options:id,quiz_question_id,option_text',
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function (QuizAttempt $attempt): array {
                $questions = $this->buildQuestionBreakdown($attempt);
                $totalQuestions = count($questions);
                $correctAnswers = collect($questions)->where('is_correct', true)->count();

                return [
                    'attempt_id' => (int) $attempt->id,
                    'learner_name' => (string) ($attempt->user?->full_name ?: $attempt->user?->name ?: $attempt->user?->email ?: 'Learner'),
                    'module_title' => (string) ($attempt->quiz?->module?->title ?? 'Unknown module'),
                    'quiz_title' => (string) ($attempt->quiz?->title ?? 'Untitled quiz'),
                    'score' => (int) ($attempt->score ?? 0),
                    'passed' => (bool) ($attempt->passed ?? false),
                    'attempted_at' => optional($attempt->completed_at ?: $attempt->created_at)?->toIso8601String(),
                    'correct_answers' => $correctAnswers,
                    'incorrect_answers' => max(0, $totalQuestions - $correctAnswers),
                    'total_questions' => $totalQuestions,
                    'questions' => $questions,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestionBreakdown(QuizAttempt $attempt): array
    {
        $answers = is_array($attempt->answers) ? $attempt->answers : [];
        $questions = $attempt->quiz?->questions?->sortBy('order') ?? collect();

        return $questions->map(function ($question) use ($answers): array {
            $answerPayload = $answers[$question->id] ?? $answers[(string) $question->id] ?? [];
            $selected = data_get($answerPayload, 'selected');
            $correct = data_get($answerPayload, 'correct');

            return [
                'question_id' => (int) $question->id,
                'question_text' => (string) $question->question_text,
                'question_type' => (string) ($question->question_type ?: data_get($answerPayload, 'type', 'unknown')),
                'learner_answer' => $this->formatAnswerValue($selected, $question),
                'correct_answer' => $this->formatAnswerValue($correct, $question),
                'is_correct' => (bool) data_get($answerPayload, 'is_correct', false),
            ];
        })->all();
    }

    private function formatAnswerValue(mixed $value, mixed $question): string
    {
        if ($value === null || $value === '') {
            return 'No answer';
        }

        if (is_array($value)) {
            if ($value === []) {
                return 'No answer';
            }

            return collect($value)
                ->map(fn ($item) => $this->formatSingleAnswerValue($item, $question))
                ->implode(', ');
        }

        return $this->formatSingleAnswerValue($value, $question);
    }

    private function formatSingleAnswerValue(mixed $value, mixed $question): string
    {
        if (is_numeric($value)) {
            $optionText = $question->options?->firstWhere('id', (int) $value)?->option_text;
            if (is_string($optionText) && trim($optionText) !== '') {
                return $optionText;
            }
        }

        if (is_bool($value)) {
            return $value ? 'True' : 'False';
        }

        return trim((string) $value) !== '' ? (string) $value : 'No answer';
    }

    private function scoreDistributionByModule(Collection $moduleIds, int $lowScoreThreshold): Collection
    {
        $midScoreThreshold = 80;

        return QuizAttempt::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('modules', 'modules.id', '=', 'quizzes.module_id')
            ->whereIn('modules.id', $moduleIds)
            ->select(
                'modules.id as module_id',
                'modules.title as module_title',
                DB::raw('COUNT(quiz_attempts.id) as total_attempts'),
                DB::raw('SUM(CASE WHEN quiz_attempts.score < ' . $lowScoreThreshold . ' THEN 1 ELSE 0 END) as low_band'),
                DB::raw('SUM(CASE WHEN quiz_attempts.score >= ' . $lowScoreThreshold . ' AND quiz_attempts.score < ' . $midScoreThreshold . ' THEN 1 ELSE 0 END) as mid_band'),
                DB::raw('SUM(CASE WHEN quiz_attempts.score >= ' . $midScoreThreshold . ' THEN 1 ELSE 0 END) as high_band')
            )
            ->groupBy('modules.id', 'modules.title')
            ->orderByDesc('total_attempts')
            ->get()
            ->map(function ($row): array {
                return [
                    'module_id' => (int) $row->module_id,
                    'module_title' => (string) $row->module_title,
                    'total_attempts' => (int) $row->total_attempts,
                    'low_band' => (int) $row->low_band,
                    'mid_band' => (int) $row->mid_band,
                    'high_band' => (int) $row->high_band,
                ];
            });
    }

    private function attemptCountByLearner(Collection $moduleIds): Collection
    {
        return QuizAttempt::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('users', 'users.id', '=', 'quiz_attempts.user_id')
            ->whereIn('quizzes.module_id', $moduleIds)
            ->select(
                'users.id as learner_id',
                DB::raw("COALESCE(NULLIF(TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, ''))), ''), users.name, users.email) as learner_name"),
                DB::raw('COUNT(quiz_attempts.id) as attempt_count'),
                DB::raw('AVG(quiz_attempts.score) as avg_score'),
                DB::raw('MAX(quiz_attempts.created_at) as last_attempt_at')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.name', 'users.email')
            ->orderByDesc('attempt_count')
            ->get()
            ->map(function ($row): array {
                return [
                    'learner_id' => (int) $row->learner_id,
                    'learner_name' => (string) $row->learner_name,
                    'attempt_count' => (int) $row->attempt_count,
                    'avg_score' => round((float) $row->avg_score, 1),
                    'last_attempt_at' => (string) $row->last_attempt_at,
                    'is_at_risk' => false,
                ];
            });
    }
}
