<?php

namespace App\Services\Persona;

use App\Models\GuardianIdentityAudit;
use App\Models\InstructorIdentityVerification;
use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\InstructorApplication\InstructorIdentityComparison;
use App\Services\InstructorApplication\InstructorIdentityService;
use App\Notifications\Admin\LearnerIdentitySubmittedNotification as AdminLearnerIdentitySubmittedNotification;
use App\Notifications\Admin\ParentVerificationRequestSubmittedNotification;
use App\Notifications\LearnerIdentitySubmittedNotification;
use App\Notifications\ParentVerificationSubmittedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonaReviewSubmission
{
    public function __construct(
        private readonly InstructorIdentityService $instructorIdentities,
        private readonly InstructorIdentityComparison $instructorComparison,
    ) {}

    public function submit(PersonaInquiry $attempt, ?array $providerAttributes = null): void
    {
        if ($attempt->instructor_identity_verification_id !== null) {
            $this->submitInstructor($attempt, $providerAttributes ?? []);

            return;
        }

        $sandboxReview = $attempt->isSandboxReviewable();
        if (! config('persona.enabled') || config('persona.environment') !== 'sandbox'
            || ! $attempt->is_sandbox || (! $attempt->isPolicyEligible() && ! $sandboxReview)
            || ! in_array($attempt->persona_status, ['completed', 'approved'], true)
            || ! $attempt->government_id_verification_id || ! $attempt->selfie_verification_id
            || (! $sandboxReview && ($attempt->country_code !== 'PH'
                || $attempt->id_class !== config('persona.government_id_classes.'.$attempt->local_government_id_type)))) {
            return;
        }

        $user = User::query()->whereKey($attempt->user_id)->lockForUpdate()->firstOrFail();
        if (! $user->hasVerifiedEmail()) {
            return;
        }

        if ($attempt->flow_type === PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT) {
            if (! $user->isParentRegistration() || $attempt->learner_identity_verification_id !== null
                || ! in_array($user->parent_verification_status, [null, 'rejected'], true)
                || ($user->parent_verification_status === 'rejected' && ! $user->parent_identity_resubmission_allowed)
                || $attempt->target_submission_round <= (int) $user->guardianIdentityAudits()->max('attempt_number')) {
                return;
            }
            $previous = $user->parent_verification_status;
            $user->forceFill([
                'parent_identity_evidence_source' => 'persona',
                'parent_identity_sandbox_manual_review' => $sandboxReview,
                'parent_id_type' => $attempt->local_government_id_type,
                'parent_id_type_other' => null,
                'parent_verification_status' => 'pending',
                'parent_verification_submitted_at' => now(),
                'parent_verification_rejection_reason' => null,
                'parent_identity_requested_corrections' => null,
                'parent_verification_reviewed_by' => null,
                'parent_verification_reviewed_at' => null,
                'parent_verification_approved_at' => null,
            ])->save();
            GuardianIdentityAudit::create([
                'guardian_id' => $user->id, 'actor_id' => null, 'persona_inquiry_id' => $attempt->id,
                'action' => 'submitted', 'from_status' => $previous, 'to_status' => 'pending',
                'attempt_number' => $attempt->target_submission_round, 'created_at' => now(),
                'reason' => $sandboxReview ? PersonaInquiry::SANDBOX_REVIEW_REASON : null,
            ]);
            $notification = new ParentVerificationSubmittedNotification;
            $adminNotification = new ParentVerificationRequestSubmittedNotification($user);
        } else {
            $case = LearnerIdentityVerification::query()->whereKey($attempt->learner_identity_verification_id)->lockForUpdate()->first();
            if (! $user->isLearner() || $user->isParentRegistration() || ! $case
                || (int) $case->user_id !== (int) $user->id || $case->superseded_at !== null
                || ! in_array($case->status, [null, 'rejected'], true)
                || ($case->status === 'rejected' && ! $case->resubmission_allowed)
                || $attempt->target_submission_round !== $case->submission_round + 1
                || $attempt->flow_type !== match ($case->pathway) {
                    'adult' => PersonaInquiry::FLOW_ADULT_GOVERNMENT,
                    'teen' => PersonaInquiry::FLOW_TEEN_GOVERNMENT,
                    default => null,
                }) {
                return;
            }
            $previous = $case->status;
            $case->forceFill([
                'document_type' => 'government_id', 'government_id_type' => $attempt->local_government_id_type,
                'government_id_type_other' => null, 'evidence_source' => 'persona', 'status' => 'pending',
                'sandbox_manual_review' => $sandboxReview,
                'submission_round' => $attempt->target_submission_round, 'submitted_at' => now(),
                'reviewed_by' => null, 'reviewed_at' => null, 'approved_at' => null, 'rejection_reason' => null,
            ])->save();
            $case->audits()->create([
                'actor_id' => null, 'action' => $previous === 'rejected' ? 'resubmitted' : 'submitted',
                'from_status' => $previous, 'to_status' => 'pending',
                'submission_round' => $case->submission_round, 'created_at' => now(),
                'reason' => $sandboxReview ? PersonaInquiry::SANDBOX_REVIEW_REASON : null,
            ]);
            $notification = new LearnerIdentitySubmittedNotification($case);
            $adminNotification = new AdminLearnerIdentitySubmittedNotification($case);
        }

        DB::afterCommit(function () use ($user, $notification, $adminNotification): void {
            try {
                $recipients = User::query()->role('admin')->get();
            } catch (\Throwable) {
                Log::warning('Persona review administrator lookup failed.');
                $recipients = collect();
            }
            $recipients->push($user);
            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify($recipient->id === $user->id ? $notification : $adminNotification);
                } catch (\Throwable) {
                    Log::warning('Persona review submission notification failed.');
                }
            }
        });
    }

    /** @param array<string, string> $providerAttributes */
    private function submitInstructor(PersonaInquiry $attempt, array $providerAttributes): void
    {
        if (! config('persona.enabled') || config('persona.environment') !== 'sandbox'
            || ! $attempt->is_sandbox || ! $attempt->isCurrent()) {
            return;
        }

        $user = User::query()->whereKey($attempt->user_id)->lockForUpdate()->first();
        $case = InstructorIdentityVerification::query()
            ->whereKey($attempt->instructor_identity_verification_id)
            ->lockForUpdate()
            ->first();
        if (! $user || ! $case || (int) $case->user_id !== (int) $user->id
            || ! $user->isInstructorApplicant() || ! $user->hasVerifiedEmail()
            || $case->superseded_at !== null
            || $attempt->target_submission_round !== $case->submission_round) {
            return;
        }

        if ($attempt->verification_outcome === PersonaInquiry::OUTCOME_NOT_READY
            && ! in_array($attempt->persona_status, ['completed', 'approved', 'failed', 'expired', 'declined', 'needs-review'], true)) {
            return;
        }

        $hasRequiredResources = in_array($attempt->persona_status, ['completed', 'approved'], true)
            && $attempt->government_id_verification_id
            && $attempt->selfie_verification_id;

        if (! $hasRequiredResources) {
            $this->instructorIdentities->markActionRequired($case, $attempt, 'persona_evidence_unavailable');

            return;
        }

        if ($attempt->verification_outcome === PersonaInquiry::OUTCOME_RETRYABLE
            || $attempt->verification_outcome === PersonaInquiry::OUTCOME_INELIGIBLE) {
            $this->instructorIdentities->markActionRequired($case, $attempt, 'persona_retry_required');

            return;
        }

        if (! in_array($attempt->verification_outcome, [
            PersonaInquiry::OUTCOME_ELIGIBLE,
            PersonaInquiry::OUTCOME_SANDBOX_REVIEW,
            PersonaInquiry::OUTCOME_INCONCLUSIVE,
        ], true)) {
            $this->instructorIdentities->markActionRequired($case, $attempt, 'persona_evidence_unavailable');

            return;
        }

        $sandboxReview = $attempt->verification_outcome === PersonaInquiry::OUTCOME_SANDBOX_REVIEW;
        if ($sandboxReview && ! $attempt->isSandboxReviewable()) {
            $this->instructorIdentities->markActionRequired($case, $attempt, 'sandbox_manual_review_unavailable');

            return;
        }

        $comparison = $this->instructorComparison->compare(
            $case->declared_snapshot ?? [],
            $providerAttributes,
        );
        $this->instructorIdentities->markPendingReview($case, $attempt, $comparison, $sandboxReview);
    }
}
