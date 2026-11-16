<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\InteractiveActivity;
use App\Models\InteractiveActivityProgress;
use App\Models\InteractiveCheckpointProgress;
use App\Models\LessonTopicProgress;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LearnerModuleCompletionService
{
    /** Null means no new transition; zero is a newly completed module with no reward. */
    public function recordCompletion(User $user, Module $module): ?int
    {
        return DB::transaction(function () use ($user, $module) {
            $enrollment = $user->moduleEnrollments()->where('module_id', $module->id)
                ->where('status', EnrollmentStatus::Approved)->lockForUpdate()->first();
            if (! $enrollment || $enrollment->completed_at !== null || ! $this->isFullyCompleted($user, $module)) {
                return null;
            }
            $enrollment->update(['completed_at' => now(), 'completion_percentage' => 100]);
            return app(GamificationService::class)->awardConfiguredPoints($user, 'module_completed');
        });
    }

    public function moduleFeedback(User $user, Module $module): array
    {
        $certificate = $user->certificates()->where('module_id', $module->id)->first();
        $eligible = $this->isFullyCompleted($user, $module);
        $enrollmentId = $user->moduleEnrollments()->where('module_id', $module->id)->value('id');
        $primary = $certificate
            ? ['id' => 'certificate', 'intent' => 'navigate', 'label' => 'View certificate', 'url' => route('learner.certificates.show', $certificate)]
            : ($eligible
                ? ['id' => 'certificate', 'intent' => 'continue', 'label' => 'Claim certificate']
                : ['id' => 'requirements', 'intent' => 'navigate', 'label' => 'Complete remaining requirements', 'url' => route('learner.modules.show', $module)]);

        return [
            'id' => "module-completed:{$user->id}:{$module->id}:{$enrollmentId}",
            'sourceId' => "module:{$module->id}",
            'type' => 'module_completed',
            'title' => 'Module complete',
            'message' => 'You completed this module. Review your results and available next steps.',
            'actions' => [$primary, ['id' => 'module', 'intent' => 'navigate', 'label' => 'Module overview', 'url' => route('learner.modules.show', $module)]],
            'replacesSuccessToast' => true,
        ];
    }

    /** Completed submissions are recorded separately from progression eligibility. */
    public function quizProgress(User $user, Collection $quizzes): Collection
    {
        $attempts = QuizAttempt::query()->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))->whereNotNull('completed_at')
            ->get(['quiz_id', 'passed'])->groupBy('quiz_id');

        return $quizzes->mapWithKeys(function ($quiz) use ($attempts) {
            $submitted = $attempts->get($quiz->id, collect());
            $passed = $submitted->contains(fn ($attempt) => $attempt->passed);
            return [$quiz->id => [
                'attempted' => $submitted->isNotEmpty(),
                'passed' => $passed,
                'resolved' => $passed || ($quiz->attempt_limit !== null && $submitted->count() >= (int) $quiz->attempt_limit),
            ]];
        });
    }

    /**
     * @return array{eligible: bool, reason: string|null}
     */
    public function reviewEligibility(User $user, Module $module): array
    {
        $reason = $this->completionBlockerReason($user, $module, requirePassingLessonQuizzes: false);

        return [
            'eligible' => $reason === null,
            'reason' => $reason,
        ];
    }

    public function isFullyCompleted(User $user, Module $module): bool
    {
        return $this->completionBlockerReason($user, $module, requirePassingLessonQuizzes: true) === null;
    }

    /**
     * @param  Collection<int, mixed>  $topics
     * @return Collection<int, int>
     */
    public function completedTopicIds(User $user, Collection $topics): Collection
    {
        $topicIds = $topics
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($topicIds->isEmpty()) {
            return collect();
        }

        $completedTopicIds = LessonTopicProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_topic_id', $topicIds)
            ->where('completed', true)
            ->pluck('lesson_topic_id');

        $checkpointTopicIds = $topics
            ->filter(fn ($topic): bool => $topic->type === 'interactive_checkpoint')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($checkpointTopicIds->isNotEmpty()) {
            $completedTopicIds = $completedTopicIds->merge(
                InteractiveCheckpointProgress::query()
                    ->where('user_id', $user->id)
                    ->whereIn('lesson_topic_id', $checkpointTopicIds)
                    ->whereNull('checkpoint_block_uuid')
                    ->whereIn('status', ['correct', 'completed', 'skipped'])
                    ->pluck('lesson_topic_id'),
            );
        }

        $activityTopicIds = $topics
            ->filter(fn ($topic): bool => $topic->type === 'interactive')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($activityTopicIds->isNotEmpty()) {
            $activities = InteractiveActivity::query()
                ->whereIn('lesson_topic_id', $activityTopicIds)
                ->where('placement', 'between_topics')
                ->get(['id', 'lesson_topic_id', 'revision'])
                ->keyBy('id');

            if ($activities->isNotEmpty()) {
                $completedTopicIds = $completedTopicIds->merge(
                    InteractiveActivityProgress::query()
                        ->where('user_id', $user->id)
                        ->whereIn('interactive_activity_id', $activities->keys())
                        ->whereIn('status', ['completed', 'skipped'])
                        ->get(['interactive_activity_id', 'activity_revision'])
                        ->filter(fn (InteractiveActivityProgress $progress): bool => (int) $progress->activity_revision === (int) $activities->get($progress->interactive_activity_id)->revision)
                        ->map(fn (InteractiveActivityProgress $progress): int => (int) $activities->get($progress->interactive_activity_id)->lesson_topic_id),
                );
            }
        }

        return $completedTopicIds
            ->map(fn ($id): int => (int) $id)
            ->intersect($topicIds)
            ->unique()
            ->values();
    }

    public function completionBlockerReason(User $user, Module $module, bool $requirePassingLessonQuizzes = true): ?string
    {
        $enrollment = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->where('status', EnrollmentStatus::Approved)
            ->first();

        if (! $enrollment) {
            return 'You must be enrolled in this module.';
        }

        $lessons = $module->lessons()
            ->where('is_published', true)
            ->with([
                'topics',
                'quiz' => fn ($query) => $query->where('is_active', true),
            ])
            ->get();

        if ($lessons->isEmpty()) {
            return 'No published lessons are available yet for this module.';
        }

        $completedLessonIds = UserProgress::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->whereIn('lesson_id', $lessons->modelKeys())
            ->where('completed', true)
            ->pluck('lesson_id')
            ->unique();

        if ($completedLessonIds->count() < $lessons->count()) {
            return 'Complete all lessons before submitting feedback.';
        }

        $topics = $lessons->flatMap(fn ($lesson) => $lesson->topics)
            ->reject(fn ($topic) => $topic->isOptionalInteraction());
        $topicIds = $topics->pluck('id')->unique();
        if ($topicIds->isNotEmpty()) {
            $completedTopicIds = $this->completedTopicIds($user, $topics);

            if ($completedTopicIds->count() < $topicIds->count()) {
                return 'Complete all lesson topics before submitting feedback.';
            }
        }

        $quizProgress = $this->quizProgress($user, $lessons->pluck('quiz')->filter());
        if ($quizProgress->contains(fn ($state) => ! $state[$requirePassingLessonQuizzes ? 'resolved' : 'attempted'])) {
            return 'Complete all lesson quizzes before submitting feedback.';
        }

        if ($module->final_quiz_id) {
            $finalAttemptCount = QuizAttempt::query()
                ->where('user_id', $user->id)
                ->where('quiz_id', $module->final_quiz_id)
                ->whereNotNull('completed_at')
                ->count();

            $hasPassedFinalQuiz = QuizAttempt::query()
                ->where('user_id', $user->id)
                ->where('quiz_id', $module->final_quiz_id)
                ->whereNotNull('completed_at')
                ->where('passed', true)
                ->exists();

            $finalQuizAttemptLimit = $module->quizzes()
                ->where('id', $module->final_quiz_id)
                ->value('attempt_limit');

            $isFinalQuizCompleted = $hasPassedFinalQuiz
                || ($finalQuizAttemptLimit !== null && $finalAttemptCount >= (int) $finalQuizAttemptLimit);

            if (! $isFinalQuizCompleted) {
                return 'Complete the final quiz before submitting feedback.';
            }
        }

        return null;
    }
}
