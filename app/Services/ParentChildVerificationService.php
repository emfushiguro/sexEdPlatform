<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\GuardianIdentityAudit;
use App\Models\ParentChildAccount;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Notifications\Admin\ChildVerificationRequestSubmittedNotification;
use App\Notifications\Admin\ParentVerificationRequestSubmittedNotification;
use App\Notifications\ChildVerificationApprovedNotification;
use App\Notifications\ChildVerificationRejectedNotification;
use App\Notifications\ParentVerificationApprovedNotification;
use App\Notifications\ParentVerificationRejectedNotification;
use App\Notifications\ParentVerificationSubmittedNotification;
use App\Services\Persona\PersonaInquiryService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ParentChildVerificationService
{
    public function __construct(
        private readonly PersonaInquiryService $personaInquiryService,
    ) {}

    public function approveParent(User $parent): void
    {
        if (! $parent->is_parent_registration) {
            throw new InvalidArgumentException('User is not a parent registration account.');
        }

        DB::transaction(function () use ($parent): void {
            $lockedParent = User::query()->whereKey($parent->getKey())->lockForUpdate()->firstOrFail();
            $fromStatus = $lockedParent->parent_verification_status;

            try {
                $this->personaInquiryService->assertGuardianMayApprove($lockedParent);
            } catch (\DomainException) {
                abort(422, 'Persona verification is required before guardian approval.');
            }

            $lockedParent->update([
                'parent_verification_status' => VerificationStatus::Approved->value,
                'parent_verification_rejection_reason' => null,
                'parent_verification_reviewed_by' => Auth::id(),
                'parent_verification_reviewed_at' => now(),
                'parent_verification_approved_at' => now(),
                'guardian_onboarding_status' => $lockedParent->hasCompletedProfile() ? 'completed' : 'not_started',
                'guardian_onboarding_started_at' => null,
                'guardian_onboarding_completed_at' => $lockedParent->hasCompletedProfile() ? now() : null,
            ]);

            $this->appendGuardianAudit(
                guardian: $lockedParent,
                action: 'approved',
                fromStatus: $fromStatus,
                toStatus: VerificationStatus::Approved->value,
            );

            DB::afterCommit(function () use ($lockedParent): void {
                $this->notifySafely($lockedParent, new ParentVerificationApprovedNotification());
            });
        });
    }

    public function rejectParent(User $parent, string $reason): void
    {
        if (! $parent->is_parent_registration) {
            throw new InvalidArgumentException('User is not a parent registration account.');
        }

        DB::transaction(function () use ($parent, $reason): void {
            $lockedParent = User::query()->whereKey($parent->getKey())->lockForUpdate()->firstOrFail();
            $fromStatus = $lockedParent->parent_verification_status;
            $normalizedReason = trim($reason);

            $lockedParent->update([
                'parent_verification_status' => VerificationStatus::Rejected->value,
                'parent_verification_rejection_reason' => $normalizedReason,
                'parent_verification_reviewed_by' => Auth::id(),
                'parent_verification_reviewed_at' => now(),
                'parent_verification_approved_at' => null,
            ]);

            $this->appendGuardianAudit(
                guardian: $lockedParent,
                action: 'rejected',
                fromStatus: $fromStatus,
                toStatus: VerificationStatus::Rejected->value,
                reason: $normalizedReason,
            );

            DB::afterCommit(function () use ($lockedParent, $normalizedReason): void {
                $this->notifySafely($lockedParent, new ParentVerificationRejectedNotification($normalizedReason));
            });
        });
    }

    public function submitParentIdentity(
        User $parent,
        string $idType,
        ?string $idTypeOther,
        string $frontPath,
        ?string $backPath,
    ): void {
        if (! $parent->is_parent_registration) {
            throw new InvalidArgumentException('User is not a guardian registration account.');
        }

        if ($parent->parent_verification_status === VerificationStatus::Pending->value) {
            throw new InvalidArgumentException('Guardian verification is already pending review.');
        }

        DB::transaction(function () use ($parent, $idType, $idTypeOther, $frontPath, $backPath): void {
            $lockedParent = User::query()->whereKey($parent->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedParent->parent_verification_status === VerificationStatus::Pending->value) {
                throw new InvalidArgumentException('Guardian verification is already pending review.');
            }

            $personaAttempt = $this->personaInquiryService->isRequired()
                ? $this->personaInquiryService->assertGuardianMaySubmit($lockedParent, $idType)
                : null;
            $oldStatus = $lockedParent->parent_verification_status;
            $oldFrontPath = $lockedParent->parent_id_document_path;
            $oldBackPath = $lockedParent->parent_id_document_back_path;
            $attemptNumber = $personaAttempt?->attempt_number
                ?? max(1, (int) GuardianIdentityAudit::query()
                    ->where('guardian_id', $lockedParent->id)
                    ->max('attempt_number') + 1);

            $lockedParent->update([
                'parent_id_type' => $idType,
                'parent_id_type_other' => $idType === 'other' ? $idTypeOther : null,
                'parent_id_document_path' => $frontPath,
                'parent_id_document_back_path' => $backPath,
                'parent_verification_status' => VerificationStatus::Pending->value,
                'parent_verification_rejection_reason' => null,
                'parent_verification_submitted_at' => now(),
                'parent_verification_reviewed_by' => null,
                'parent_verification_reviewed_at' => null,
                'parent_verification_approved_at' => null,
            ]);

            foreach ([$oldFrontPath, $oldBackPath] as $oldPath) {
                if (! empty($oldPath) && ! in_array($oldPath, [$frontPath, $backPath], true)) {
                    Storage::disk('local')->delete((string) $oldPath);
                    Storage::disk('public')->delete((string) $oldPath);
                }
            }

            $this->appendGuardianAudit(
                guardian: $lockedParent,
                action: 'submitted',
                fromStatus: $oldStatus,
                toStatus: VerificationStatus::Pending->value,
                personaAttempt: $personaAttempt,
                attemptNumber: $attemptNumber,
            );

            DB::afterCommit(function () use ($lockedParent): void {
                $this->notifySafely($lockedParent, new ParentVerificationSubmittedNotification());
                $this->notifyAdminsSafely(new ParentVerificationRequestSubmittedNotification($lockedParent));
            });
        });
    }

    private function appendGuardianAudit(
        User $guardian,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?PersonaInquiry $personaAttempt = null,
        ?int $attemptNumber = null,
        ?string $reason = null,
    ): void {
        GuardianIdentityAudit::query()->create([
            'guardian_id' => $guardian->id,
            'actor_id' => Auth::id(),
            'persona_inquiry_id' => $personaAttempt?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'attempt_number' => $attemptNumber ?? max(1, (int) GuardianIdentityAudit::query()
                ->where('guardian_id', $guardian->id)
                ->max('attempt_number')),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    public function approveChild(ParentChildAccount $verification): void
    {
        $this->assertChildRegistrationVerification($verification);

        DB::transaction(function () use ($verification): void {
            $verification->loadMissing(['parent', 'child']);

            $verification->update([
                'verification_status' => VerificationStatus::Approved->value,
                'verification_rejection_reason' => null,
                'verification_reviewed_by' => Auth::id(),
                'verification_reviewed_at' => now(),
                'verification_approved_at' => now(),
            ]);

            $this->notifySafely($verification->parent, new ChildVerificationApprovedNotification($verification->child));
        });
    }

    public function rejectChild(ParentChildAccount $verification, string $reason): void
    {
        $this->assertChildRegistrationVerification($verification);

        DB::transaction(function () use ($verification, $reason): void {
            $verification->loadMissing(['parent', 'child']);

            $verification->update([
                'verification_status' => VerificationStatus::Rejected->value,
                'verification_rejection_reason' => trim($reason),
                'verification_reviewed_by' => Auth::id(),
                'verification_reviewed_at' => now(),
                'verification_approved_at' => null,
            ]);

            $this->notifySafely($verification->parent, new ChildVerificationRejectedNotification($verification->child, trim($reason)));
        });
    }

    public function resubmitChild(ParentChildAccount $verification, string $newDocumentPath): void
    {
        $this->assertChildRegistrationVerification($verification);

        if ($verification->verification_status !== VerificationStatus::Rejected->value) {
            throw new InvalidArgumentException('Only rejected child verification records can be resubmitted.');
        }

        DB::transaction(function () use ($verification, $newDocumentPath): void {
            $verification->loadMissing(['parent', 'child']);
            $oldPath = $verification->verification_document_path;

            $verification->update([
                'verification_document_path' => $newDocumentPath,
                'verification_status' => VerificationStatus::Pending->value,
                'verification_reviewed_by' => null,
                'verification_reviewed_at' => null,
                'verification_approved_at' => null,
            ]);

            if (! empty($oldPath) && $oldPath !== $newDocumentPath) {
                Storage::disk('local')->delete((string) $oldPath);
                Storage::disk('public')->delete((string) $oldPath);
            }

            $this->notifyAdminsSafely(new ChildVerificationRequestSubmittedNotification(
                $verification->parent,
                $verification->child,
                $verification
            ));
        });
    }

    private function assertChildRegistrationVerification(ParentChildAccount $verification): void
    {
        if (blank($verification->verification_document_path)) {
            throw new InvalidArgumentException('Invitation relationships must be reviewed through the relationship verification queue.');
        }
    }

    private function notifySafely(User $notifiable, Notification $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Email transport failed while sending parent-child verification notification.', [
                'notifiable_id' => $notifiable->id,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function notifyAdminsSafely(Notification $notification): void
    {
        try {
            User::query()
                ->role('admin')
                ->get()
                ->each(fn (User $admin) => $admin->notify($notification));
        } catch (\Throwable $exception) {
            Log::warning('Failed to send admin parent-child verification resubmission notification.', [
                'notification' => $notification::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
