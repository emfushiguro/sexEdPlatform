<?php

namespace App\Services\Persona;

use App\Models\GuardianIdentityAudit;
use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Notifications\Admin\LearnerIdentitySubmittedNotification as AdminLearnerIdentitySubmittedNotification;
use App\Notifications\Admin\ParentVerificationRequestSubmittedNotification;
use App\Notifications\LearnerIdentitySubmittedNotification;
use App\Notifications\ParentVerificationSubmittedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonaReviewSubmission
{
    public function submit(PersonaInquiry $attempt): void
    {
        if (! config('persona.enabled') || config('persona.environment') !== 'sandbox'
            || ! $attempt->is_sandbox || ! $attempt->isPolicyEligible()
            || ! in_array($attempt->persona_status, ['completed', 'approved'], true)
            || ! $attempt->government_id_verification_id || ! $attempt->selfie_verification_id
            || $attempt->country_code !== 'PH'
            || $attempt->id_class !== config('persona.government_id_classes.'.$attempt->local_government_id_type)) {
            return;
        }

        $user = User::query()->whereKey($attempt->user_id)->lockForUpdate()->firstOrFail();
        if (! $user->hasVerifiedEmail()) {
            return;
        }

        if ($attempt->flow_type === PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT) {
            if (! $user->isParentRegistration() || $attempt->learner_identity_verification_id !== null
                || ! in_array($user->parent_verification_status, [null, 'rejected'], true)
                || $attempt->target_submission_round <= (int) $user->guardianIdentityAudits()->max('attempt_number')) {
                return;
            }
            $previous = $user->parent_verification_status;
            $user->forceFill([
                'parent_identity_evidence_source' => 'persona',
                'parent_id_type' => $attempt->local_government_id_type,
                'parent_id_type_other' => null,
                'parent_verification_status' => 'pending',
                'parent_verification_submitted_at' => now(),
                'parent_verification_rejection_reason' => null,
                'parent_verification_reviewed_by' => null,
                'parent_verification_reviewed_at' => null,
                'parent_verification_approved_at' => null,
            ])->save();
            GuardianIdentityAudit::create([
                'guardian_id' => $user->id, 'actor_id' => null, 'persona_inquiry_id' => $attempt->id,
                'action' => 'submitted', 'from_status' => $previous, 'to_status' => 'pending',
                'attempt_number' => $attempt->target_submission_round, 'created_at' => now(),
            ]);
            $notification = new ParentVerificationSubmittedNotification;
            $adminNotification = new ParentVerificationRequestSubmittedNotification($user);
        } else {
            $case = LearnerIdentityVerification::query()->whereKey($attempt->learner_identity_verification_id)->lockForUpdate()->first();
            if (! $user->isLearner() || $user->isParentRegistration() || ! $case
                || (int) $case->user_id !== (int) $user->id || $case->superseded_at !== null
                || ! in_array($case->status, [null, 'rejected'], true)
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
                'submission_round' => $attempt->target_submission_round, 'submitted_at' => now(),
                'reviewed_by' => null, 'reviewed_at' => null, 'approved_at' => null, 'rejection_reason' => null,
            ])->save();
            $case->audits()->create([
                'actor_id' => null, 'action' => $previous === 'rejected' ? 'resubmitted' : 'submitted',
                'from_status' => $previous, 'to_status' => 'pending',
                'submission_round' => $case->submission_round, 'created_at' => now(),
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
}
