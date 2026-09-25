<?php

namespace App\Services\Identity;

use App\Enums\VerificationStatus;
use App\Enums\LearnerIdentityRejectionReason;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Notifications\LearnerIdentityApprovedNotification;
use App\Notifications\LearnerIdentityRejectedNotification;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LearnerIdentityReview
{
    public function __construct(private readonly LearnerIdentityRequirement $requirement)
    {
    }

    public function approve(User $reviewer, LearnerIdentityVerification $case): void
    {
        $this->decide($reviewer, $case, VerificationStatus::Approved, null);
    }

    public function reject(User $reviewer, LearnerIdentityVerification $case, LearnerIdentityRejectionReason $reason): void
    {
        $this->decide($reviewer, $case, VerificationStatus::Rejected, $reason);
    }

    private function decide(User $reviewer, LearnerIdentityVerification $case, VerificationStatus $decision, ?LearnerIdentityRejectionReason $reason): void
    {
        abort_unless($reviewer->hasRole('admin'), 403);

        DB::transaction(function () use ($reviewer, $case, $decision, $reason): void {
            $locked = LearnerIdentityVerification::query()->lockForUpdate()->findOrFail($case->id);
            $learner = $locked->learner;
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
                'reason' => $reason?->label(),
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
        $documentTypes = $case->pathway === 'teen'
            ? ['school_id', 'institution_id', 'government_id'] : ['government_id'];
        if (! in_array($case->document_type, $documentTypes, true)
            || ($case->document_type === 'government_id'
                && ! array_key_exists((string) $case->government_id_type, config('guardian_identity.id_types', [])))) {
            return false;
        }
        $slots = ['identity_front', 'selfie'];
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
