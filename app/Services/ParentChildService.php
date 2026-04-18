<?php

namespace App\Services;

use App\Models\ModuleEnrollment;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\RewardLog;
use App\Models\User;
use App\Models\UserGamification;
use App\Models\UserProgress;
use Illuminate\Support\Collection;

class ParentChildService
{
    /**
     * Get all approved module enrollments for the child with progress data.
     */
    public function getProgress(User $child): Collection
    {
        $enrollments = ModuleEnrollment::where('user_id', $child->id)
            ->where('status', 'approved')
            ->with([
                'module.lessons',
                'module.creator.instructorProfile',
            ])
            ->latest('enrolled_at')
            ->get();

        return $enrollments->map(function (ModuleEnrollment $enrollment) use ($child) {
            $totalLessons     = $enrollment->module->lessons->count();
            $completedLessons = UserProgress::where('user_id', $child->id)
                ->where('module_id', $enrollment->module_id)
                ->where('completed', true)
                ->count();

            $enrollment->completed_lessons = $completedLessons;
            $enrollment->total_lessons     = $totalLessons;
            $enrollment->progress_pct      = $totalLessons > 0
                ? round(($completedLessons / $totalLessons) * 100)
                : 0;

            return $enrollment;
        });
    }

    /**
     * Get all quiz attempts for the child, newest first.
     */
    public function getQuizResults(User $child): Collection
    {
        return QuizAttempt::where('user_id', $child->id)
            ->with(['quiz.module'])
            ->latest('completed_at')
            ->get();
    }

    /**
     * Build parent-facing quiz attempt details with readable answer text.
     *
     * @return array{attempt: QuizAttempt, question_results: Collection<int, array<string, mixed>>}
     */
    public function getQuizAttemptDetails(User $child, QuizAttempt $attempt): array
    {
        $attempt->loadMissing(['quiz.module', 'quiz.questions.options']);

        $answers = is_array($attempt->answers) ? $attempt->answers : [];
        $questionResults = collect($attempt->quiz?->questions ?? [])
            ->map(function (QuizQuestion $question) use ($answers): array {
                $entry = $answers[$question->id] ?? $answers[(string) $question->id] ?? [];

                return [
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'selected_answer' => $this->formatAttemptAnswerValue(data_get($entry, 'selected'), $question),
                    'correct_answer' => $this->formatAttemptAnswerValue(data_get($entry, 'correct'), $question),
                    'is_correct' => (bool) data_get($entry, 'is_correct', false),
                ];
            })
            ->values();

        return [
            'attempt' => $attempt,
            'question_results' => $questionResults,
        ];
    }

    /**
     * Get gamification summary and reward log for the child.
     */
    public function getAchievements(User $child): array
    {
        $gamification = UserGamification::firstOrCreate(
            ['user_id' => $child->id],
            ['level' => 1, 'score' => 0, 'total_points' => 0, 'streak_count' => 0]
        );

        $rewardLogs = RewardLog::where('user_id', $child->id)
            ->with('achievement')
            ->latest('earned_at')
            ->get();

        return [
            'gamification' => $gamification,
            'rewardLogs'   => $rewardLogs,
        ];
    }

    /**
     * Get module enrollments awaiting parent approval.
     */
    public function getPendingEnrollments(User $child): Collection
    {
        return ModuleEnrollment::where('user_id', $child->id)
            ->where('status', 'pending_parent_approval')
            ->with('module.creator.instructorProfile')
            ->latest()
            ->get();
    }

    private function formatAttemptAnswerValue(mixed $value, QuizQuestion $question): string
    {
        if (is_array($value)) {
            $formatted = collect($value)
                ->map(fn (mixed $item) => $this->formatSingleAnswerValue($item, $question))
                ->filter(fn (?string $item) => $item !== null && trim($item) !== '')
                ->values();

            return $formatted->isNotEmpty()
                ? $formatted->implode(', ')
                : 'No answer submitted';
        }

        $single = $this->formatSingleAnswerValue($value, $question);

        return ($single !== null && trim($single) !== '')
            ? $single
            : 'No answer submitted';
    }

    private function formatSingleAnswerValue(mixed $value, QuizQuestion $question): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($question->question_type, ['multiple_choice', 'true_false', 'multiple_select'], true) && is_numeric($value)) {
            $optionText = $question->options
                ->firstWhere('id', (int) $value)
                ?->option_text;

            return $optionText ?: (string) $value;
        }

        return is_scalar($value)
            ? (string) $value
            : null;
    }
}
