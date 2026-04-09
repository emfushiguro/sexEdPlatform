<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\ChildVerificationApprovedNotification;
use App\Notifications\ChildVerificationRejectedNotification;
use App\Notifications\ParentVerificationApprovedNotification;
use App\Notifications\ParentVerificationRejectedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ParentChildVerificationService
{
    public function approveParent(User $parent): void
    {
        if (! $parent->is_parent_registration) {
            throw new InvalidArgumentException('User is not a parent registration account.');
        }

        DB::transaction(function () use ($parent): void {
            $parent->update([
                'parent_verification_status' => VerificationStatus::Approved->value,
                'parent_verification_rejection_reason' => null,
                'parent_verification_reviewed_by' => Auth::id(),
                'parent_verification_reviewed_at' => now(),
                'parent_verification_approved_at' => now(),
            ]);

            $this->notifySafely($parent, new ParentVerificationApprovedNotification());
        });
    }

    public function rejectParent(User $parent, string $reason): void
    {
        if (! $parent->is_parent_registration) {
            throw new InvalidArgumentException('User is not a parent registration account.');
        }

        DB::transaction(function () use ($parent, $reason): void {
            $parent->update([
                'parent_verification_status' => VerificationStatus::Rejected->value,
                'parent_verification_rejection_reason' => trim($reason),
                'parent_verification_reviewed_by' => Auth::id(),
                'parent_verification_reviewed_at' => now(),
                'parent_verification_approved_at' => null,
            ]);

            $this->notifySafely($parent, new ParentVerificationRejectedNotification(trim($reason)));
        });
    }

    public function approveChild(ParentChildAccount $verification): void
    {
        DB::transaction(function () use ($verification): void {
            $verification->loadMissing(['parent', 'child']);

            $verification->update([
                'verification_status' => VerificationStatus::Approved->value,
                'verification_rejection_reason' => null,
                'verification_reviewed_by' => Auth::id(),
                'verification_reviewed_at' => now(),
                'verification_approved_at' => now(),
                'relationship_verified_at' => now(),
            ]);

            $this->notifySafely($verification->parent, new ChildVerificationApprovedNotification($verification->child));
        });
    }

    public function rejectChild(ParentChildAccount $verification, string $reason): void
    {
        DB::transaction(function () use ($verification, $reason): void {
            $verification->loadMissing(['parent', 'child']);

            $verification->update([
                'verification_status' => VerificationStatus::Rejected->value,
                'verification_rejection_reason' => trim($reason),
                'verification_reviewed_by' => Auth::id(),
                'verification_reviewed_at' => now(),
                'verification_approved_at' => null,
                'relationship_verified_at' => null,
            ]);

            $this->notifySafely($verification->parent, new ChildVerificationRejectedNotification($verification->child, trim($reason)));
        });
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
}
