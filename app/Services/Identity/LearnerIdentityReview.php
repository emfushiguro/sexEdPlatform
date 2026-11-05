<?php

namespace App\Services\Identity;

use App\Enums\LearnerIdentityRejectionReason;
use App\Enums\VerificationStatus;
use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Notifications\LearnerIdentityApprovedNotification;
use App\Notifications\LearnerIdentityRejectedNotification;
use App\Services\Persona\PersonaInquiryService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LearnerIdentityReview
{
    public function __construct(
        private readonly LearnerIdentityRequirement $requirement,
        private readonly PersonaInquiryService $personaInquiries,
    ) {}

    public function approve(
        User $reviewer,
        LearnerIdentityVerification $case,
        int $submissionRound,
        bool $personaPortraitCompared = false,
        bool $sandboxPhilippineIdConfirmed = false,
        bool $sandboxIdTypeConfirmed = false,
    ): void {
        $this->decide($reviewer, $case, VerificationStatus::Approved, null, $submissionRound, $personaPortraitCompared, $sandboxPhilippineIdConfirmed, $sandboxIdTypeConfirmed);
    }

    public function reject(User $reviewer, LearnerIdentityVerification $case, LearnerIdentityRejectionReason $reason, int $submissionRound,
        ?array $corrections = null, ?string $note = null, bool $allowResubmission = true): void
    {
        $this->decide($reviewer, $case, VerificationStatus::Rejected, $reason, $submissionRound,
            corrections: $corrections, note: $note, allowResubmission: $allowResubmission);
    }

    private function decide(
        User $reviewer,
        LearnerIdentityVerification $case,
        VerificationStatus $decision,
        ?LearnerIdentityRejectionReason $reason,
        int $submissionRound,
        bool $personaPortraitCompared = false,
        bool $sandboxPhilippineIdConfirmed = false,
        bool $sandboxIdTypeConfirmed = false,
        ?array $corrections = null,
        ?string $note = null,
        bool $allowResubmission = true,
    ): void {
        abort_unless($reviewer->hasRole('admin'), 403);

        DB::transaction(function () use ($reviewer, $case, $decision, $reason, $submissionRound, $personaPortraitCompared, $sandboxPhilippineIdConfirmed, $sandboxIdTypeConfirmed, $corrections, $note, $allowResubmission): void {
            $locked = LearnerIdentityVerification::query()->lockForUpdate()->findOrFail($case->id);
            $learner = $locked->learner;
            abort_if((int) $locked->submission_round !== $submissionRound,
                409, 'A newer evidence round has been submitted. Reload this case before reviewing.');
            try {
                $currentPathway = $this->requirement->pathwayFor($learner);
            } catch (DomainException) {
                abort(409, 'The learner birthdate no longer matches this case.');
            }
            abort_if($locked->superseded_at !== null
                || $locked->status !== VerificationStatus::Pending->value
                || $locked->pathway !== $currentPathway
                || $learner->identityVerifications()->whereNull('superseded_at')->whereKeyNot($locked->id)->exists(),
                409, 'Only a current pending case can be reviewed.');
            abort_unless($this->requiredEvidenceExists($locked), 422, 'Required private evidence is missing.');
            if ($decision === VerificationStatus::Rejected) {
                $available = $locked->evidence_source === 'persona' ? ['persona_identity'] : ['identity_front', 'selfie'];
                if ($locked->evidence()->where('slot', 'identity_back')->exists()) {
                    $available[] = 'identity_back';
                }
                $corrections ??= $locked->evidence_source === 'persona' ? ['persona_identity']
                    : ($reason === LearnerIdentityRejectionReason::UnclearSelfie ? ['selfie'] : ['identity_front']);
                abort_if($allowResubmission && (! $corrections || array_diff($corrections, $available)), 422, 'Select the evidence that needs updating.');
                $locked->requested_corrections = $allowResubmission ? array_values(array_unique($corrections)) : [];
                $locked->correction_note = $note;
                $locked->resubmission_allowed = $allowResubmission;
                if ($locked->usesPersonaSelfie() && ! $locked->persona_evidence_attempt_id) {
                    $locked->persona_evidence_attempt_id = $locked->personaInquiries()->where('target_submission_round', $locked->submission_round)
                        ->where('user_id', $learner->id)->whereNull('superseded_at')->where('flow_type', PersonaInquiry::FLOW_TEEN_SCHOOL)
                        ->latest('attempt_number')->value('id');
                }
            }

            if ($decision === VerificationStatus::Approved) {
                abort_if($locked->sandbox_manual_review
                    && (! $sandboxPhilippineIdConfirmed || ! $sandboxIdTypeConfirmed || ! $personaPortraitCompared),
                    422, 'Confirm Philippine issuance, selected ID type and ID/selfie portrait comparison before approving this local Sandbox application.');
                try {
                    $this->personaInquiries->assertLearnerMayApprove($learner, $locked);
                } catch (DomainException) {
                    abort(422, 'Persona verification is required before learner approval.');
                }

                if ((config('persona.enabled', false) || $locked->usesPersonaSelfie())
                    && $locked->pathway === 'teen'
                    && in_array($locked->document_type, ['school_id', 'institution_id'], true)
                    && ! $personaPortraitCompared) {
                    abort(422, 'Administrator portrait comparison confirmation is required.');
                }
            }

            $locked->forceFill([
                'status' => $decision->value,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'approved_at' => $decision === VerificationStatus::Approved ? now() : null,
                'rejection_reason' => $reason?->label(),
            ])->save();
            $locked->audits()->create([
                'actor_id' => $reviewer->id,
                'action' => $decision->value,
                'from_status' => VerificationStatus::Pending->value,
                'to_status' => $decision->value,
                'submission_round' => $locked->submission_round,
                'reason' => $reason?->label() ?? ($locked->sandbox_manual_review ? PersonaInquiry::SANDBOX_APPROVAL_REASON : null),
                'correction_request' => $reason ? ['items' => $corrections ?? [], 'note' => $note, 'allow_resubmission' => $allowResubmission] : null,
                'created_at' => now(),
            ]);
            DB::afterCommit(function () use ($learner, $locked, $decision, $reason): void {
                $notification = $decision === VerificationStatus::Approved
                    ? new LearnerIdentityApprovedNotification($locked)
                    : new LearnerIdentityRejectedNotification($locked, $reason);
                try {
                    $learner->notify($notification);
                } catch (\Throwable) {
                    Log::warning('Learner identity notification failed.', [
                        'user_id' => $learner->id, 'case_id' => $locked->id,
                        'notification' => $notification::class,
                    ]);
                }
            });
        });
    }

    private function requiredEvidenceExists(LearnerIdentityVerification $case): bool
    {
        if ($case->evidence_source === 'persona') {
            return $case->personaInquiries()->where('user_id', $case->user_id)
                ->where('target_submission_round', $case->submission_round)
                ->whereNull('superseded_at')->where('is_sandbox', true)
                ->whereNotNull('government_id_verification_id')->whereNotNull('selfie_verification_id')->exists();
        }
        $documentTypes = $case->pathway === 'teen'
            ? ['school_id', 'institution_id', 'government_id'] : ['government_id'];
        if (! in_array($case->document_type, $documentTypes, true)
            || ($case->document_type === 'government_id'
                && ! array_key_exists((string) $case->government_id_type, config('guardian_identity.id_types', [])))) {
            return false;
        }
        $slots = $case->usesPersonaSelfie() ? ['identity_front'] : ['identity_front', 'selfie'];
        if ($case->document_type === 'government_id'
            && (bool) data_get(config('guardian_identity.id_types', []), $case->government_id_type.'.requires_back', false)) {
            $slots[] = 'identity_back';
        }
        $evidence = $case->evidence()->whereIn('slot', $slots)->get()->keyBy('slot');
        foreach ($slots as $slot) {
            $file = $evidence->get($slot);
            if (! $file || ! in_array($file->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)
                || ! Storage::disk('local')->exists($file->storage_path)) {
                return false;
            }
        }

        return true;
    }
}
