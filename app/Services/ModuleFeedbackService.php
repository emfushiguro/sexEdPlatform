<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleFeedback;
use App\Models\User;
use App\Notifications\Instructor\ModuleFeedbackSubmittedNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ModuleFeedbackService
{
    public function __construct(
        private readonly LearnerModuleCompletionService $completionService,
    ) {
    }

    public function upsertLearnerFeedback(User $learner, Module $module, int $rating, string $reviewHtml): ModuleFeedback
    {
        $eligibility = $this->completionService->reviewEligibility($learner, $module);
        if (!$eligibility['eligible']) {
            throw new RuntimeException((string) ($eligibility['reason'] ?? 'You are not allowed to submit feedback yet.'));
        }

        $sanitizedReview = $this->sanitizeHtml($reviewHtml);
        if (trim(strip_tags($sanitizedReview)) === '') {
            throw new RuntimeException('Review content cannot be empty.');
        }

        return DB::transaction(function () use ($learner, $module, $rating, $sanitizedReview) {
            $feedback = ModuleFeedback::query()->firstOrNew([
                'module_id' => $module->id,
                'learner_id' => $learner->id,
            ]);

            $isExisting = $feedback->exists;

            $feedback->fill([
                'rating' => $rating,
                'review_html' => $sanitizedReview,
                'submitted_at' => $isExisting ? $feedback->submitted_at : now(),
                'last_edited_at' => $isExisting ? now() : null,
            ]);
            $feedback->save();

            $feedback->loadMissing(['module.creator', 'learner']);
            $moduleOwner = $feedback->module?->creator;
            if ($moduleOwner && (int) $moduleOwner->id !== (int) $learner->id) {
                $moduleOwner->notify(new ModuleFeedbackSubmittedNotification($feedback));
            }

            return $feedback->fresh(['learner']);
        });
    }

    public function upsertInstructorReply(User $instructor, ModuleFeedback $feedback, string $replyHtml): ModuleFeedback
    {
        $feedback->loadMissing('module');
        $module = $feedback->module;

        if (!$module || (int) $module->created_by !== (int) $instructor->id) {
            throw new RuntimeException('You are not allowed to reply to this review.');
        }

        $sanitizedReply = $this->sanitizeHtml($replyHtml);
        if (trim(strip_tags($sanitizedReply)) === '') {
            throw new RuntimeException('Reply content cannot be empty.');
        }

        $feedback->update([
            'instructor_reply_html' => $sanitizedReply,
        ]);

        return $feedback->fresh(['learner']);
    }

    public function sanitizeHtml(string $html): string
    {
        return trim((string) strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote>'));
    }
}
