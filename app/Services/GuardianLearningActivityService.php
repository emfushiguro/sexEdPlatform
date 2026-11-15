<?php

namespace App\Services;

use App\Models\User;
use App\Models\Lesson;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Collection;
use Throwable;

class GuardianLearningActivityService
{
    public function recordLogin(Login $event): void
    {
        if ($event->user instanceof User && $event->user->isLearner()) {
            $this->record($event->user, 'login');
        }
    }

    public function recordLessonView(User $learner, Lesson $lesson): void
    {
        $this->record($learner, 'lesson_viewed', ['lesson_id' => $lesson->id, 'lesson_title' => $lesson->title]);
    }

    private function record(User $learner, string $type, ?array $metadata = null): void
    {
        try {
            $learner->activityLogs()->create(['activity_type' => $type, 'metadata' => $metadata]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function forChild(User $child, bool $includeQuizActivity): Collection
    {
        $events = collect();
        foreach ($child->userProgress()->with(['lesson', 'module'])
            ->where(fn ($query) => $query->whereNotNull('last_accessed_at')->orWhereNotNull('completed_at'))
            ->orderByRaw('CASE WHEN last_accessed_at > completed_at OR completed_at IS NULL THEN last_accessed_at ELSE completed_at END DESC')
            ->limit(25)->get() as $progress) {
            $title = $progress->lesson?->title ?: $progress->module?->title;
            if ($progress->last_accessed_at) {
                $events->push(['label' => 'Viewed learning content', 'title' => $title, 'at' => $progress->last_accessed_at]);
            }
            if ($progress->completed_at) {
                $events->push(['label' => 'Completed learning content', 'title' => $title, 'at' => $progress->completed_at]);
            }
        }
        foreach ($child->activityLogs()->whereIn('activity_type', ['login', 'lesson_viewed'])->latest('created_at')->limit(25)->get() as $log) {
            $events->push([
                'label' => $log->activity_type === 'lesson_viewed' ? 'Viewed learning content' : 'Signed in',
                'title' => $log->activity_type === 'lesson_viewed' ? ($log->metadata['lesson_title'] ?? null) : null,
                'at' => $log->created_at,
            ]);
        }
        if ($includeQuizActivity) {
            foreach ($child->quizAttempts()->with('quiz')->whereNotNull('completed_at')->latest('completed_at')->limit(25)->get() as $attempt) {
                $events->push(['label' => 'Completed quiz', 'title' => $attempt->quiz?->title, 'at' => $attempt->completed_at]);
            }
        }

        return $events->sortByDesc('at')->take(25)->values();
    }
}
