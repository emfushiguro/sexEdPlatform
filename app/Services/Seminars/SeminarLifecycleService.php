<?php

namespace App\Services\Seminars;

use App\Enums\SeminarStatus;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\Admin\NewSeminarSubmissionNotification;
use App\Notifications\Seminars\NewSeminarAvailableNotification;
use App\Notifications\Seminars\SeminarModerationDecisionNotification;

class SeminarLifecycleService
{
    public function __construct(private readonly SeminarRegistrationService $registrations) {}

    public function submitForReview(Seminar $seminar, User $user): Seminar
    {
        abort_unless(in_array($seminar->status, [
            SeminarStatus::Draft->value,
            SeminarStatus::Rejected->value,
        ], true), 422, 'Only draft or rejected seminars can be submitted for review.');

        $seminar->forceFill([
            'status' => SeminarStatus::PendingReview->value,
            'submitted_for_review_at' => now(),
            'submitted_for_review_by' => $user->id,
        ])->save();

        $seminar = $seminar->fresh(['connector']);
        $this->notifyAdminsAboutSubmission($seminar);

        return $seminar;
    }

    public function publishApproved(Seminar $seminar, User $user): Seminar
    {
        abort_unless($seminar->status === SeminarStatus::Approved->value, 422, 'Only approved seminars can be published.');

        $seminar->forceFill([
            'status' => SeminarStatus::Published->value,
            'published_at' => now(),
            'published_by' => $user->id,
            'livestream_status' => 'scheduled',
            'livestream_started_at' => null,
            'livestream_ended_at' => null,
        ])->save();

        $seminar = $seminar->fresh(['connector']);
        $this->notifyEligibleMembersAboutPublication($seminar);

        return $seminar;
    }

    public function approve(Seminar $seminar, User $moderator): Seminar
    {
        abort_unless($seminar->status === SeminarStatus::PendingReview->value, 422, 'Only pending review seminars can be approved.');

        $fromStatus = $seminar->status;
        $seminar->forceFill([
            'status' => SeminarStatus::Approved->value,
            'approved_at' => now(),
            'approved_by' => $moderator->id,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
            'moderator_note' => null,
        ])->save();

        $this->recordReview($seminar, $moderator, $fromStatus, SeminarStatus::Approved->value);
        $this->notifyOrganizerAboutDecision($seminar->fresh(['connector']), SeminarStatus::Approved->value);

        return $seminar->fresh();
    }

    public function reject(Seminar $seminar, User $moderator, string $reason, ?string $note = null): Seminar
    {
        abort_unless($seminar->status === SeminarStatus::PendingReview->value, 422, 'Only pending review seminars can be rejected.');

        $fromStatus = $seminar->status;
        $seminar->forceFill([
            'status' => SeminarStatus::Rejected->value,
            'rejected_at' => now(),
            'rejected_by' => $moderator->id,
            'rejection_reason' => $reason,
            'moderator_note' => $note,
        ])->save();

        $this->recordReview($seminar, $moderator, $fromStatus, SeminarStatus::Rejected->value, $reason, $note);
        $this->notifyOrganizerAboutDecision($seminar->fresh(['connector']), SeminarStatus::Rejected->value, $reason, $note);

        return $seminar->fresh();
    }

    public function archive(Seminar $seminar, User $user): Seminar
    {
        abort_unless(in_array($seminar->status, [
            SeminarStatus::Rejected->value,
            SeminarStatus::Completed->value,
            SeminarStatus::Cancelled->value,
        ], true), 422, 'Only rejected, completed, or cancelled seminars can be archived.');

        $seminar->forceFill([
            'status' => SeminarStatus::Archived->value,
            'archived_at' => now(),
            'archived_by' => $user->id,
            'livestream_status' => 'archived',
        ])->save();

        return $seminar->fresh();
    }

    public function complete(Seminar $seminar, User $user): Seminar
    {
        abort_unless($seminar->status === SeminarStatus::Published->value, 422, 'Only published seminars can be completed.');

        $seminar->forceFill([
            'status' => SeminarStatus::Completed->value,
            'completed_at' => now(),
            'completed_by' => $user->id,
            'livestream_status' => 'completed',
            'livestream_ended_at' => $seminar->livestream_ended_at ?? now(),
        ])->save();

        return $seminar->fresh();
    }

    public function cancel(Seminar $seminar, User $user, string $reason): Seminar
    {
        abort_unless(in_array($seminar->status, [
            SeminarStatus::PendingReview->value,
            SeminarStatus::Approved->value,
            SeminarStatus::Published->value,
        ], true), 422, 'Only active review or published seminars can be cancelled.');

        $seminar->forceFill([
            'status' => SeminarStatus::Cancelled->value,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
            'cancellation_reason' => $reason,
            'livestream_status' => 'completed',
            'livestream_ended_at' => $seminar->livestream_started_at ? now() : null,
        ])->save();

        return $seminar->fresh();
    }

    private function recordReview(
        Seminar $seminar,
        User $moderator,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?string $note = null
    ): void {
        $seminar->moderationReviews()->create([
            'moderator_id' => $moderator->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'note' => $note,
            'reviewed_at' => now(),
        ]);
    }

    private function notifyAdminsAboutSubmission(Seminar $seminar): void
    {
        User::query()
            ->where('role', 'admin')
            ->whereDoesntHave('notifications', function ($query) use ($seminar): void {
                $query->where('data->type', 'new_seminar_submission')
                    ->where('data->seminar_id', $seminar->id)
                    ->whereNull('read_at');
            })
            ->each(fn (User $admin) => $admin->notify(new NewSeminarSubmissionNotification($seminar)));
    }

    private function notifyOrganizerAboutDecision(Seminar $seminar, string $status, ?string $reason = null, ?string $note = null): void
    {
        $user = $seminar->submittedForReviewBy
            ?? $seminar->connector?->primaryRepresentative
            ?? $seminar->connector?->creator;

        $user?->notify(new SeminarModerationDecisionNotification($seminar, $status, $reason, $note));
    }

    private function notifyEligibleMembersAboutPublication(Seminar $seminar): void
    {
        $seminar->connector?->memberships()
            ->where('status', 'active')
            ->with('user.learnerProfile')
            ->chunkById(100, function ($memberships) use ($seminar): void {
                foreach ($memberships as $membership) {
                    $user = $membership->user;

                    if ($user && $this->registrations->matchesParticipantEligibility($user, $seminar)) {
                        $user->notify(new NewSeminarAvailableNotification($seminar));
                    }
                }
            });
    }
}
