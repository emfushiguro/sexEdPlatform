<?php

namespace App\Services;

use App\Models\ModuleReviewRequest;
use App\Models\User;

class AdminModuleReviewWorkspaceService
{
    public function compose(ModuleReviewRequest $reviewRequest): array
    {
        $reviewRequest->loadMissing([
            'module',
            'revision',
            'reviewer',
            'revision.submitter',
            'module.publisher',
        ]);

        $snapshot = $reviewRequest->revision?->snapshot_payload ?? [];

        $moduleData = is_array($snapshot['module'] ?? null)
            ? $snapshot['module']
            : [];

        $lessons = collect($snapshot['lessons'] ?? [])
            ->filter(fn ($lesson) => is_array($lesson))
            ->sortBy(fn ($lesson) => (int) data_get($lesson, 'attributes.order', 0))
            ->values()
            ->all();

        $quizzes = collect($snapshot['quizzes'] ?? [])
            ->filter(fn ($quiz) => is_array($quiz))
            ->sortBy(fn ($quiz) => (int) data_get($quiz, 'attributes.id', 0))
            ->values()
            ->all();

        $instructor = $this->resolveInstructor($reviewRequest);
        $moderationProfile = $instructor?->moderationProfile()->first();
        $recentViolations = $instructor
            ? $instructor->violationHistories()->latest('id')->take(5)->get()
            : collect();

        return [
            'module' => [
                ...$moduleData,
                'id' => data_get($moduleData, 'id', $reviewRequest->module_id),
                'title' => data_get($moduleData, 'title', $reviewRequest->module_title),
                'submission_date' => optional($reviewRequest->submitted_at)?->toDateTimeString(),
                'status' => $reviewRequest->status,
            ],
            'hierarchy' => [
                'lessons' => $lessons,
                'quizzes' => $quizzes,
                'lesson_count' => count($lessons),
                'quiz_count' => count($quizzes),
            ],
            'instructor' => [
                'id' => $instructor?->id,
                'name' => $instructor?->name ?? $reviewRequest->revision?->submitter?->name ?? 'Unknown Instructor',
                'avatar' => null,
            ],
            'moderation' => [
                'warning_count' => $moderationProfile?->warning_count ?? 0,
                'current_restriction_status' => $moderationProfile?->current_restriction_status,
                'restriction_ends_at' => optional($moderationProfile?->restriction_ends_at)?->toDateTimeString(),
                'last_violation_at' => optional($moderationProfile?->last_violation_at)?->toDateTimeString(),
                'recent_violations' => $recentViolations->map(fn ($violation) => [
                    'id' => $violation->id,
                    'reason_code' => $violation->reason_code,
                    'guidance_note' => $violation->guidance_note,
                    'violation_sequence' => $violation->violation_sequence,
                    'suggested_penalty_action' => $violation->suggested_penalty_action,
                    'confirmed_penalty_action' => $violation->confirmed_penalty_action,
                    'created_at' => optional($violation->created_at)?->toDateTimeString(),
                ])->all(),
            ],
        ];
    }

    private function resolveInstructor(ModuleReviewRequest $reviewRequest): ?User
    {
        $moduleInstructorId = $reviewRequest->module?->created_by;
        if ($moduleInstructorId) {
            return User::query()->find($moduleInstructorId);
        }

        return $reviewRequest->revision?->submitter;
    }
}
