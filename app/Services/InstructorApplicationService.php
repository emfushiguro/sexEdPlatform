<?php

namespace App\Services;

use App\Enums\InstructorApplicationRejectionReason;
use App\Models\InstructorApplication;
use App\Models\InstructorApplicationReview;
use App\Models\InstructorProfile;
use App\Models\RoleTransition;
use App\Models\User;
use App\Notifications\InstructorApplicationStatusUpdate;
use App\Notifications\InstructorApplicationSubmitted;
use App\Services\Admin\RoleSyncService;
use App\Services\InstructorApplication\InstructorApplicationDocumentStorage;
use App\Services\Moderation\SourceAdapters\InstructorApplicationModerationAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;
use DomainException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class InstructorApplicationService
{
    private const DEFAULT_APPROVAL_MESSAGE = '<p>Congratulations! Your instructor application has been approved.</p><p>You can now start creating and publishing learning modules on the platform.</p>';

    private const DEFAULT_REJECTION_MESSAGE = '<p>Thank you for applying to become an instructor on our platform.</p><p>After reviewing your application, we regret to inform you that it has not been approved at this time.</p>';

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly RoleSyncService $roleSyncService,
        private readonly InstructorApplicationDocumentStorage $documentStorage,
        private readonly InstructorApplicationModerationAdapter $instructorApplicationModerationAdapter,
    )
    {
    }

    public function submitApplication(User $user, array $data): InstructorApplication
    {
        return DB::transaction(function () use ($user, $data): InstructorApplication {
            $application = $user->instructorApplications()
                ->where('status', 'draft')
                ->lockForUpdate()
                ->latest('id')
                ->first();
            $metadata = is_array($application?->application_metadata) ? $application->application_metadata : [];
            $submittedAt = now();
            $documentData = $this->documentPaths($user, $data, $application);
            $attributes = [
                'status' => 'pending',
                'submitted_at' => $submittedAt,
                'document_disk' => $application?->document_disk ?? config('instructor_application.document_disk', 'local'),
                'instructor_identity_verification_id' => $user->instructorIdentityVerifications()->current()->accessApproved()->value('id'),
                'educational_background' => $this->educationalBackground($data, $application?->educational_background),
                'bio' => array_key_exists('bio', $data) ? (string) $data['bio'] : (string) ($application?->bio ?? ''),
                'application_metadata' => array_replace_recursive($metadata, [
                    'submission_ip' => request()?->ip(),
                    'submitted_at' => $submittedAt->toDateTimeString(),
                    'files' => array_replace($metadata['files'] ?? [], $this->buildFileMetadata($data)),
                    'documents' => array_replace($metadata['documents'] ?? [], $documentData['metadata']),
                ]),
            ];
            $attributes = array_merge($attributes, $documentData['paths']);

            if ($application) {
                $application->update($attributes);
                $application = $application->fresh();
            } else {
                $application = $user->instructorApplications()->create($attributes);
            }

            $this->instructorApplicationModerationAdapter->syncSubmission($application);
            DB::afterCommit(function () use ($application): void {
                User::role('admin')->get()->each(function (User $admin) use ($application): void {
                    $this->notifySafely($admin, new InstructorApplicationSubmitted($application));
                });
            });

            return $application;
        });
    }

    public function saveDraft(User $user, array $data): InstructorApplication
    {
        return DB::transaction(function () use ($user, $data): InstructorApplication {
            $application = $user->instructorApplications()
                ->where('status', 'draft')
                ->lockForUpdate()
                ->latest('id')
                ->first();
            $metadata = is_array($application?->application_metadata) ? $application->application_metadata : [];
            $documentData = $this->documentPaths($user, $data, $application);
            $attributes = array_merge([
                'status' => 'draft',
                'document_disk' => $application?->document_disk ?? config('instructor_application.document_disk', 'local'),
                'educational_background' => $this->educationalBackground($data, $application?->educational_background),
                'bio' => array_key_exists('bio', $data) ? (string) $data['bio'] : (string) ($application?->bio ?? ''),
                'application_metadata' => array_replace_recursive($metadata, [
                    'files' => array_replace($metadata['files'] ?? [], $this->buildFileMetadata($data)),
                    'documents' => array_replace($metadata['documents'] ?? [], $documentData['metadata']),
                ]),
            ], $documentData['paths']);

            if ($application) {
                $application->update($attributes);

                return $application->fresh();
            }

            return $user->instructorApplications()->create($attributes);
        });
    }

    public function approve(InstructorApplication $application, ?string $adminMessage = null, ?User $reviewer = null): void
    {
        $reviewer ??= Auth::user();

        DB::transaction(function () use ($application, $adminMessage, $reviewer): void {
            $lockedApplication = InstructorApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($lockedApplication->status !== 'pending') {
                throw new DomainException('This application is no longer pending. Reload the review before deciding.');
            }

            $user = User::query()->whereKey($lockedApplication->user_id)->lockForUpdate()->firstOrFail();
            $lockedApplication->setRelation('user', $user);
            $user->load('learnerProfile', 'instructorProfile');
            if ($reviewer) {
                if ((int) $reviewer->id === (int) $user->id) {
                    throw new DomainException('An applicant cannot approve their own application.');
                }
                if (! $reviewer->isAdmin() || ! $reviewer->hasPermissionTo('review instructor applications')) {
                    throw new DomainException('You are not authorized to approve instructor applications.');
                }
            }
            $this->assertActivationEligible($user, $lockedApplication);
            $reviewedAt = now();
            $sanitizedMessage = $this->sanitizeAdminMessage($adminMessage ?? self::DEFAULT_APPROVAL_MESSAGE, self::DEFAULT_APPROVAL_MESSAGE);

            $snapshot = [
                'enrolled_modules_count' => $user->moduleEnrollments()->count(),
                'certificates_earned' => $user->certificates()->count(),
                'gamification_level' => $user->gamification?->level,
                'gamification_score' => $user->gamification?->score,
                'subscription_status' => $user->subscription?->status,
                'last_activity_at' => $user->userProgress()->latest('updated_at')->value('updated_at'),
            ];

            RoleTransition::create([
                'user_id' => $user->id,
                'from_role' => $user->role,
                'to_role' => 'instructor',
                'approved_by' => $reviewer?->id,
                'reason' => 'Instructor application approved',
                'preserved_data' => $snapshot,
                'transitioned_at' => now(),
            ]);

            $this->roleSyncService->assignPrimaryRole($user, 'instructor');

            $profilePhotoPath = $user->instructorProfile?->profile_photo_path ?: $user->learnerProfile?->avatar_path;

            InstructorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bio' => $lockedApplication->bio,
                    'educational_background' => $lockedApplication->educational_background,
                    'professional_background' => $lockedApplication->bio,
                    'profile_photo_path' => $profilePhotoPath,
                ]
            );

            $lockedApplication->update([
                'status' => 'approved',
                'approved_by' => $reviewer?->id,
                'approved_at' => $reviewedAt,
                'rejection_reason' => null,
                'rejection_reason_code' => null,
                'rejection_reason_note' => null,
                'review_message' => $sanitizedMessage,
            ]);

            $this->instructorApplicationModerationAdapter->syncSubmission($lockedApplication->fresh());

            InstructorApplicationReview::query()->create([
                'instructor_application_id' => $lockedApplication->id,
                'status' => 'approved',
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => $reviewedAt,
                'admin_message' => $sanitizedMessage,
            ]);

            DB::afterCommit(function () use ($user, $sanitizedMessage): void {
                $this->notifySafely($user, new InstructorApplicationStatusUpdate('approved', $sanitizedMessage));
                if ($user->subscription && $user->subscription->status === 'active') {
                    $this->subscriptionService->cancel($user->subscription, 'Role changed to instructor');
                }
            });
        });
    }

    public function reject(InstructorApplication $application, string $reasonCode, ?string $reasonNote = null, ?string $adminMessage = null, ?User $reviewer = null): void
    {
        $reviewer ??= Auth::user();
        DB::transaction(function () use ($application, $reasonCode, $reasonNote, $adminMessage, $reviewer): void {
            $lockedApplication = InstructorApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($lockedApplication->status !== 'pending') {
                throw new DomainException('This application is no longer pending. Reload the review before deciding.');
            }

            $user = User::query()->whereKey($lockedApplication->user_id)->lockForUpdate()->firstOrFail();
            if ($reviewer && (! $reviewer->isAdmin() || ! $reviewer->hasPermissionTo('review instructor applications'))) {
                throw new DomainException('You are not authorized to review instructor applications.');
            }
            $resolvedReason = InstructorApplicationRejectionReason::tryFrom($reasonCode);
            $reasonLabel = $resolvedReason?->label() ?? str_replace('_', ' ', $reasonCode);
            $reviewedAt = now();
            $composedReason = trim($reasonLabel . (! empty($reasonNote) ? ': ' . trim($reasonNote) : ''));
            $sanitizedMessage = $this->sanitizeAdminMessage($adminMessage ?? self::DEFAULT_REJECTION_MESSAGE, self::DEFAULT_REJECTION_MESSAGE);

            $lockedApplication->update([
                'status' => 'rejected',
                'rejection_reason_code' => $reasonCode,
                'rejection_reason_note' => $reasonNote,
                'rejection_reason' => $composedReason,
                'approved_by' => $reviewer?->id,
                'approved_at' => $reviewedAt,
                'review_message' => $sanitizedMessage,
            ]);

            $this->instructorApplicationModerationAdapter->syncSubmission($lockedApplication->fresh());
            InstructorApplicationReview::query()->create([
                'instructor_application_id' => $lockedApplication->id,
                'status' => 'rejected',
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => $reviewedAt,
                'admin_message' => $sanitizedMessage,
                'reason_code' => $reasonCode,
                'reason_label' => $reasonLabel,
                'reason_note' => $reasonNote,
            ]);

            DB::afterCommit(function () use ($user, $sanitizedMessage, $reasonCode, $reasonLabel, $reasonNote): void {
                $this->notifySafely(
                    $user,
                    new InstructorApplicationStatusUpdate('rejected', $sanitizedMessage, $reasonCode, $reasonLabel, $reasonNote)
                );
            });
        });
    }

    private function assertActivationEligible(User $user, InstructorApplication $application): void
    {
        if ($user->status !== User::STATUS_ACTIVE || ! $user->hasVerifiedEmail()) {
            throw new DomainException('The applicant account must be active with a verified email before approval.');
        }
        if (($user->calculateAge() ?? 0) < (int) config('instructor_application.minimum_age', 18)) {
            throw new DomainException('The applicant must be at least 18 years old before approval.');
        }

        $identity = $user->instructorIdentityVerifications()->current()->accessApproved()->lockForUpdate()->first();
        $requiresIdentity = $user->isInstructorApplicant()
            || (config('instructor_application.identity_enforced_from')
                && $application->submitted_at?->greaterThanOrEqualTo(config('instructor_application.identity_enforced_from')));
        if ($requiresIdentity && ! $identity) {
            throw new DomainException('Stage One identity approval is required before final application approval.');
        }
        if ($identity && ! $identity->isAccessEligible()) {
            throw new DomainException('The current identity approval is not access-eligible.');
        }
    }

    private function documentPaths(User $user, array $data, ?InstructorApplication $application): array
    {
        $fields = [
            'government_id' => 'government_id_path',
            'clearance' => 'clearance_path',
            'cv_resume' => 'cv_resume_path',
            'teaching_credential' => 'teaching_credential_path',
            'sexed_certificate' => 'sexed_certificate_path',
            'professional_license' => 'professional_license_path',
        ];
        $paths = [];
        $metadata = [];
        foreach ($fields as $input => $column) {
            if (Arr::get($data, $input) instanceof UploadedFile) {
                $stored = $this->documentStorage->store($user, $data[$input], $input);
                $paths[$column] = $stored['path'];
                $metadata[$input] = $stored;
            } elseif ($application?->{$column}) {
                $paths[$column] = $application->{$column};
            }
        }

        return ['paths' => $paths, 'metadata' => $metadata];
    }

    private function educationalBackground(array $data, ?string $fallback = null): string
    {
        $value = Arr::get($data, 'educational_background', $fallback);

        if ($value === 'other' && filled($data['educational_background_other'] ?? null)) {
            return (string) $data['educational_background_other'];
        }

        return (string) ($value ?? '');
    }

    private function buildFileMetadata(array $data): array
    {
        $keys = [
            'government_id',
            'clearance',
            'cv_resume',
            'teaching_credential',
            'sexed_certificate',
            'professional_license',
        ];

        $output = [];
        foreach ($keys as $key) {
            $file = Arr::get($data, $key);
            if ($file instanceof UploadedFile) {
                $output[$key] = [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getClientMimeType(),
                ];
            }
        }

        return $output;
    }

    private function notifySafely(User $notifiable, Notification $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Email transport failed while sending instructor application notification.', [
                'notifiable_id' => $notifiable->id,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public static function defaultApprovalMessage(): string
    {
        return self::DEFAULT_APPROVAL_MESSAGE;
    }

    public static function defaultRejectionMessage(): string
    {
        return self::DEFAULT_REJECTION_MESSAGE;
    }

    private function sanitizeAdminMessage(string $message, string $fallback): string
    {
        $sanitized = strip_tags($message, '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote>');

        return trim($sanitized) !== '' ? $sanitized : $fallback;
    }
}
