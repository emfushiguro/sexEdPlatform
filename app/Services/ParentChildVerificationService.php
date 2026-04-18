<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\Admin\ChildVerificationRequestSubmittedNotification;
use App\Notifications\Admin\ParentVerificationRequestSubmittedNotification;
use App\Notifications\ChildVerificationApprovedNotification;
use App\Notifications\ChildVerificationRejectedNotification;
use App\Notifications\ParentVerificationApprovedNotification;
use App\Notifications\ParentVerificationRejectedNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public function resubmitParent(User $parent, string $newDocumentPath): void
    {
        if (! $parent->is_parent_registration) {
            throw new InvalidArgumentException('User is not a parent registration account.');
        }

        if ($parent->parent_verification_status !== VerificationStatus::Rejected->value) {
            throw new InvalidArgumentException('Only rejected parent verification records can be resubmitted.');
        }

        DB::transaction(function () use ($parent, $newDocumentPath): void {
            $oldPath = $parent->parent_id_document_path;

            $parent->update([
                'parent_id_document_path' => $newDocumentPath,
                'parent_verification_status' => VerificationStatus::Pending->value,
                'parent_verification_reviewed_by' => null,
                'parent_verification_reviewed_at' => null,
                'parent_verification_approved_at' => null,
            ]);

            if (! empty($oldPath) && $oldPath !== $newDocumentPath) {
                Storage::disk('public')->delete((string) $oldPath);
            }

            $this->notifyAdminsSafely(new ParentVerificationRequestSubmittedNotification($parent));
        });
    }

    public function resubmitChild(ParentChildAccount $verification, string $newDocumentPath): void
    {
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
                'relationship_verified_at' => null,
            ]);

            if (! empty($oldPath) && $oldPath !== $newDocumentPath) {
                Storage::disk('public')->delete((string) $oldPath);
            }

            $this->notifyAdminsSafely(new ChildVerificationRequestSubmittedNotification(
                $verification->parent,
                $verification->child,
                $verification
            ));
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
