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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class InstructorApplicationService
{
    private const DEFAULT_APPROVAL_MESSAGE = '<p>Congratulations! Your instructor application has been approved.</p><p>You can now start creating and publishing learning modules on the platform.</p>';

    private const DEFAULT_REJECTION_MESSAGE = '<p>Thank you for applying to become an instructor on our platform.</p><p>After reviewing your application, we regret to inform you that it has not been approved at this time.</p>';

    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
    }

    public function submitApplication(User $user, array $data): InstructorApplication
    {
        $basePath = 'instructor-applications/' . $user->id;
        $paths = [
            'government_id_path' => $this->storeDocument(Arr::get($data, 'government_id'), $basePath, 'government_id'),
            'clearance_path' => $this->storeDocument(Arr::get($data, 'clearance'), $basePath, 'clearance'),
            'cv_resume_path' => $this->storeDocument(Arr::get($data, 'cv_resume'), $basePath, 'cv_resume'),
            'teaching_credential_path' => $this->storeDocument(Arr::get($data, 'teaching_credential'), $basePath, 'teaching_credential'),
            'sexed_certificate_path' => $this->storeDocument(Arr::get($data, 'sexed_certificate'), $basePath, 'sexed_certificate'),
            'professional_license_path' => $this->storeDocument(Arr::get($data, 'professional_license'), $basePath, 'professional_license'),
        ];

        $metadata = [
            'submission_ip' => request()?->ip(),
            'submitted_at' => now()->toDateTimeString(),
            'files' => $this->buildFileMetadata($data),
        ];

        $educationalBg = Arr::get($data, 'educational_background');
        if ($educationalBg === 'other' && !empty($data['educational_background_other'])) {
            $educationalBg = $data['educational_background_other'];
        }

        $application = InstructorApplication::create(array_merge([
            'user_id' => $user->id,
            'status' => 'pending',
            'educational_background' => (string) $educationalBg,
            'bio' => (string) Arr::get($data, 'bio'),
            'application_metadata' => $metadata,
        ], $paths));

        User::role('admin')->get()->each(function (User $admin) use ($application): void {
            $this->notifySafely($admin, new InstructorApplicationSubmitted($application));
        });

        return $application;
    }

    public function approve(InstructorApplication $application, ?string $adminMessage = null): void
    {
        DB::transaction(function () use ($application): void {
            $application->loadMissing('user');
            $user = $application->user;
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
                'approved_by' => Auth::id(),
                'reason' => 'Instructor application approved',
                'preserved_data' => $snapshot,
                'transitioned_at' => now(),
            ]);

            $user->update(['role' => 'instructor']);
            if (! $user->hasRole('instructor')) {
                $user->assignRole('instructor');
            }

            InstructorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bio' => $application->bio,
                    'educational_background' => $application->educational_background,
                    'professional_background' => $application->bio,
                    'credentials' => [
                        'government_id_path' => $application->government_id_path,
                        'clearance_path' => $application->clearance_path,
                        'cv_resume_path' => $application->cv_resume_path,
                        'teaching_credential_path' => $application->teaching_credential_path,
                        'sexed_certificate_path' => $application->sexed_certificate_path,
                        'professional_license_path' => $application->professional_license_path,
                    ],
                ]
            );

            $application->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => $reviewedAt,
                'rejection_reason' => null,
                'rejection_reason_code' => null,
                'rejection_reason_note' => null,
                'review_message' => $sanitizedMessage,
            ]);

            InstructorApplicationReview::query()->create([
                'instructor_application_id' => $application->id,
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => $reviewedAt,
                'admin_message' => $sanitizedMessage,
            ]);

            $this->notifySafely($user, new InstructorApplicationStatusUpdate('approved', $sanitizedMessage));

            if ($user->subscription && $user->subscription->status === 'active') {
                $this->subscriptionService->cancel($user->subscription, 'Role changed to instructor');
            }
        });
    }

    public function reject(InstructorApplication $application, string $reasonCode, ?string $reasonNote = null, ?string $adminMessage = null): void
    {
        $resolvedReason = InstructorApplicationRejectionReason::tryFrom($reasonCode);
        $reasonLabel = $resolvedReason?->label() ?? str_replace('_', ' ', $reasonCode);
        $reviewedAt = now();
        $composedReason = trim($reasonLabel . (! empty($reasonNote) ? ': ' . trim($reasonNote) : ''));
        $sanitizedMessage = $this->sanitizeAdminMessage($adminMessage ?? self::DEFAULT_REJECTION_MESSAGE, self::DEFAULT_REJECTION_MESSAGE);

        $application->update([
            'status' => 'rejected',
            'rejection_reason_code' => $reasonCode,
            'rejection_reason_note' => $reasonNote,
            'rejection_reason' => $composedReason,
            'approved_by' => Auth::id(),
            'approved_at' => $reviewedAt,
            'review_message' => $sanitizedMessage,
        ]);

        InstructorApplicationReview::query()->create([
            'instructor_application_id' => $application->id,
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => $reviewedAt,
            'admin_message' => $sanitizedMessage,
            'reason_code' => $reasonCode,
            'reason_label' => $reasonLabel,
            'reason_note' => $reasonNote,
        ]);

        $application->loadMissing('user');
        $this->notifySafely(
            $application->user,
            new InstructorApplicationStatusUpdate('rejected', $sanitizedMessage, $reasonCode, $reasonLabel, $reasonNote)
        );
    }

    private function storeDocument(mixed $file, string $basePath, string $prefix): ?string
    {
        if (! $file instanceof UploadedFile) {
            return null;
        }

        $filename = now()->format('YmdHis') . '_' . $prefix . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($basePath, $filename, 'public');
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
