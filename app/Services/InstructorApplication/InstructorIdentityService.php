<?php

namespace App\Services\InstructorApplication;

use App\Models\InstructorIdentityAudit;
use App\Models\InstructorIdentityVerification;
use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class InstructorIdentityService
{
    public function __construct(
        private readonly InstructorApplicantEligibility $eligibility,
        private readonly InstructorApplicantPersonalInformation $personalInformation,
    ) {}

    public function current(User $user): ?InstructorIdentityVerification
    {
        return $user->instructorIdentityVerifications()
            ->current()
            ->latest('submission_round')
            ->first();
    }

    public function ensureAwaitingVerification(User $user): InstructorIdentityVerification
    {
        $this->eligibility->assertDirectApplicant($user);

        return DB::transaction(function () use ($user): InstructorIdentityVerification {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $current = $lockedUser->instructorIdentityVerifications()->current()->latest('submission_round')->lockForUpdate()->first();
            if ($current) {
                return $current;
            }

            $case = $lockedUser->instructorIdentityVerifications()->create([
                'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
                'status' => InstructorIdentityVerification::STATUS_AWAITING_VERIFICATION,
                'submission_round' => 1,
                'declared_snapshot' => $this->personalInformation->for($lockedUser),
                'resubmission_allowed' => true,
            ]);
            $this->audit($case, 'created', null, $case->status);

            return $case;
        });
    }

    public function satisfyFromApprovedLearnerIdentity(User $user): InstructorIdentityVerification
    {
        $this->eligibility->assertStageOneEligible($user);
        if (! $user->isLearner()) {
            throw new DomainException('Only an existing learner can reuse learner identity approval.');
        }

        return DB::transaction(function () use ($user): InstructorIdentityVerification {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $learnerCase = $lockedUser->identityVerifications()
                ->whereNull('superseded_at')
                ->where('status', 'approved')
                ->latest('submission_round')
                ->lockForUpdate()
                ->first();

            if (! $learnerCase) {
                throw new DomainException('Complete learner identity verification before continuing.');
            }

            $current = $lockedUser->instructorIdentityVerifications()->current()->latest('submission_round')->lockForUpdate()->first();
            if ($current) {
                if ($current->source === InstructorIdentityVerification::SOURCE_LEARNER_IDENTITY
                    && $current->learner_identity_verification_id === $learnerCase->id) {
                    return $current;
                }

                return $current;
            }

            $case = $lockedUser->instructorIdentityVerifications()->create([
                'source' => InstructorIdentityVerification::SOURCE_LEARNER_IDENTITY,
                'learner_identity_verification_id' => $learnerCase->id,
                'status' => InstructorIdentityVerification::STATUS_APPROVED,
                'submission_round' => 1,
                'declared_snapshot' => $this->personalInformation->for($lockedUser),
                'sandbox_manual_review' => (bool) $learnerCase->sandbox_manual_review,
                'resubmission_allowed' => false,
                'submitted_at' => $learnerCase->submitted_at ?? now(),
                'reviewed_at' => $learnerCase->reviewed_at ?? now(),
                'approved_at' => $learnerCase->approved_at ?? now(),
            ]);
            $this->audit($case, 'satisfied_from_learner_identity', null, $case->status, null, [
                'learner_identity_verification_id' => $learnerCase->id,
                'sandbox_manual_review' => (bool) $learnerCase->sandbox_manual_review,
            ]);

            return $case;
        });
    }

    public function startAttempt(User $user, array $declaredSnapshot): InstructorIdentityVerification
    {
        $this->eligibility->assertDirectApplicant($user);

        return DB::transaction(function () use ($user, $declaredSnapshot): InstructorIdentityVerification {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $current = $lockedUser->instructorIdentityVerifications()->current()->latest('submission_round')->lockForUpdate()->first();

            if ($current?->status === InstructorIdentityVerification::STATUS_AWAITING_VERIFICATION) {
                $from = $current->status;
                $current->forceFill([
                    'status' => InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS,
                    'declared_snapshot' => $declaredSnapshot,
                ])->save();
                $this->audit($current, 'attempt_started', $from, $current->status);

                return $current;
            }

            if ($current && in_array($current->status, [
                InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS,
                InstructorIdentityVerification::STATUS_PENDING_REVIEW,
                InstructorIdentityVerification::STATUS_APPROVED,
            ], true)) {
                return $current;
            }

            $round = (int) ($current?->submission_round ?? 0) + 1;
            if ($current) {
                if (! $current->resubmission_allowed) {
                    throw new DomainException('This identity case cannot be replaced.');
                }
                $current->forceFill(['superseded_at' => now()])->save();
                $this->audit($current, 'superseded', $current->status, $current->status);
            }

            $case = $lockedUser->instructorIdentityVerifications()->create([
                'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
                'status' => InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS,
                'submission_round' => $round,
                'declared_snapshot' => $declaredSnapshot,
                'resubmission_allowed' => true,
            ]);
            $this->audit($case, 'attempt_started', null, $case->status);

            return $case;
        });
    }

    public function markPendingReview(InstructorIdentityVerification $case, PersonaInquiry $attempt, array $comparison): void
    {
        DB::transaction(function () use ($case, $attempt, $comparison): void {
            $locked = InstructorIdentityVerification::query()->whereKey($case->id)->lockForUpdate()->firstOrFail();
            $this->assertCurrentCase($locked);
            if ($locked->status === InstructorIdentityVerification::STATUS_PENDING_REVIEW) {
                return;
            }
            if (! in_array($locked->status, [InstructorIdentityVerification::STATUS_AWAITING_VERIFICATION, InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS], true)) {
                throw new DomainException('This identity case cannot enter review.');
            }

            if ($attempt->user_id !== $locked->user_id) {
                throw new DomainException('Persona attempt does not belong to this identity case.');
            }
            $attempt->forceFill(['instructor_identity_verification_id' => $locked->id])->save();
            $locked->forceFill([
                'status' => InstructorIdentityVerification::STATUS_PENDING_REVIEW,
                'comparison_summary' => $comparison,
                'submitted_at' => $locked->submitted_at ?? now(),
                'reason_code' => null,
                'reason_note' => null,
            ])->save();
            $this->audit($locked, 'submitted_for_review', $case->status, $locked->status, null, [
                'persona_attempt_id' => $attempt->id,
            ]);
        });
    }

    public function markActionRequired(InstructorIdentityVerification $case, PersonaInquiry $attempt, string $reasonCode): void
    {
        DB::transaction(function () use ($case, $attempt, $reasonCode): void {
            $locked = InstructorIdentityVerification::query()->whereKey($case->id)->lockForUpdate()->firstOrFail();
            $this->assertCurrentCase($locked);
            if ($locked->status === InstructorIdentityVerification::STATUS_ACTION_REQUIRED && $locked->reason_code === $reasonCode) {
                return;
            }
            if (! in_array($locked->status, [InstructorIdentityVerification::STATUS_AWAITING_VERIFICATION, InstructorIdentityVerification::STATUS_VERIFICATION_IN_PROGRESS, InstructorIdentityVerification::STATUS_PENDING_REVIEW], true)) {
                throw new DomainException('This identity case cannot request action.');
            }
            if ($attempt->user_id !== $locked->user_id) {
                throw new DomainException('Persona attempt does not belong to this identity case.');
            }

            $locked->forceFill([
                'status' => InstructorIdentityVerification::STATUS_ACTION_REQUIRED,
                'reason_code' => $reasonCode,
                'action_required_at' => now(),
                'resubmission_allowed' => true,
            ])->save();
            $this->audit($locked, 'action_required', $case->status, $locked->status, $reasonCode, [
                'persona_attempt_id' => $attempt->id,
            ]);
        });
    }

    public function approve(InstructorIdentityVerification $case, User $reviewer, array $confirmations): void
    {
        $this->decide($case, $reviewer, InstructorIdentityVerification::STATUS_APPROVED, $confirmations);
    }

    public function reject(InstructorIdentityVerification $case, User $reviewer, string $reasonCode, ?string $note = null): void
    {
        $this->decide($case, $reviewer, InstructorIdentityVerification::STATUS_REJECTED, ['declared' => true, 'provider' => true], $reasonCode, $note);
    }

    public function requestAction(InstructorIdentityVerification $case, User $reviewer, array $actions, ?string $note = null): void
    {
        if ($actions === []) {
            throw new DomainException('At least one requested action is required.');
        }
        $this->decide($case, $reviewer, InstructorIdentityVerification::STATUS_ACTION_REQUIRED, ['declared' => true, 'provider' => true], 'reviewer_action_required', $note, $actions);
    }

    private function decide(InstructorIdentityVerification $case, User $reviewer, string $toStatus, array $confirmations, ?string $reasonCode = null, ?string $note = null, ?array $actions = null): void
    {
        if (! $reviewer->isAdmin()
            || ! $reviewer->can('review instructor identities')
            || $reviewer->id === $case->user_id) {
            throw new DomainException('Only an administrator other than the applicant can decide this identity case.');
        }
        if ($toStatus === InstructorIdentityVerification::STATUS_APPROVED
            && (! ($confirmations['declared'] ?? false) || ! ($confirmations['provider'] ?? false))) {
            throw new DomainException('Reviewer confirmations are required before approval.');
        }

        DB::transaction(function () use ($case, $reviewer, $toStatus, $reasonCode, $note, $actions): void {
            $locked = InstructorIdentityVerification::query()->whereKey($case->id)->lockForUpdate()->firstOrFail();
            $this->assertCurrentCase($locked);
            if ($locked->status !== InstructorIdentityVerification::STATUS_PENDING_REVIEW) {
                throw new DomainException('Only a pending identity case can be decided.');
            }

            $from = $locked->status;
            $locked->forceFill([
                'status' => $toStatus,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'approved_at' => $toStatus === InstructorIdentityVerification::STATUS_APPROVED ? now() : null,
                'rejected_at' => $toStatus === InstructorIdentityVerification::STATUS_REJECTED ? now() : null,
                'action_required_at' => $toStatus === InstructorIdentityVerification::STATUS_ACTION_REQUIRED ? now() : null,
                'reason_code' => $reasonCode,
                'reason_note' => $note,
                'requested_action_codes' => $actions,
                'resubmission_allowed' => $toStatus !== InstructorIdentityVerification::STATUS_APPROVED,
            ])->save();
            $this->audit($locked, 'reviewer_decision', $from, $toStatus, $reasonCode, [
                'requested_action_count' => count($actions ?? []),
            ], $reviewer->id);
        });
    }

    private function assertCurrentCase(InstructorIdentityVerification $case): void
    {
        if ($case->superseded_at !== null) {
            throw new DomainException('This identity case is stale. Refresh before continuing.');
        }
    }

    private function audit(
        InstructorIdentityVerification $case,
        string $action,
        ?string $from = null,
        ?string $to = null,
        ?string $reason = null,
        array $metadata = [],
        ?int $actorId = null,
    ): void {
        $case->audits()->create([
            'actor_id' => $actorId,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'submission_round' => $case->submission_round,
            'reason' => $reason,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
