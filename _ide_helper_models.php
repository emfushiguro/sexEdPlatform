<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $badge_icon
 * @property int $points
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RewardLog> $rewardLogs
 * @property-read int|null $reward_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereBadgeIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement wherePoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Achievement whereUpdatedAt($value)
 */
	class Achievement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $activity_type
 * @property string|null $description
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereActivityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUserId($value)
 */
	class ActivityLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $module_id
 * @property string $certificate_number
 * @property \Illuminate\Support\Carbon $issued_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Module $module
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereCertificateNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereIssuedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereUserId($value)
 */
	class Certificate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $address
 * @property string|null $contact
 * @property string|null $services
 * @property string|null $operating_hours
 * @property string $approval_status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property bool $verified
 * @property bool $is_premium
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic verified()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereApprovalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereIsPremium($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereOperatingHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereServices($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic whereVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Clinic withoutTrashed()
 */
	class Clinic extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $counselor_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property string $status
 * @property string $consultation_type
 * @property string|null $reason
 * @property string|null $notes
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Counselor $counselor
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereConsultationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereCounselorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereUserId($value)
 */
	class Consultation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $specialization
 * @property string|null $license_number
 * @property string|null $bio
 * @property string|null $schedule
 * @property string $approval_status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property bool $verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Consultation> $consultations
 * @property-read int|null $consultations_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor verified()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereApprovalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereSchedule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereSpecialization($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor whereVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Counselor withoutTrashed()
 */
	class Counselor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $module_id
 * @property string $title
 * @property string $content
 * @property int $order
 * @property string|null $video_url
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Module $module
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Quiz> $quizzes
 * @property-read int|null $quizzes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserProgress> $userProgress
 * @property-read int|null $user_progress_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereVideoUrl($value)
 */
	class Lesson extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $thumbnail
 * @property int $order
 * @property int|null $duration_minutes
 * @property bool $is_published
 * @property bool $is_premium
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModuleAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Certificate> $certificates
 * @property-read int|null $certificates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModuleEnrollment> $enrollments
 * @property-read int|null $enrollments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lesson> $lessons
 * @property-read int|null $lessons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Quiz> $quizzes
 * @property-read int|null $quizzes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserProgress> $userProgress
 * @property-read int|null $user_progress_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module free()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module premium()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereIsPremium($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereThumbnail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Module withoutTrashed()
 */
	class Module extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $module_id
 * @property string $file_name
 * @property string $file_path
 * @property string $file_type
 * @property int $file_size
 * @property bool $is_premium
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Module $module
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment free()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment premium()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereIsPremium($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleAttachment whereUpdatedAt($value)
 */
	class ModuleAttachment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $module_id
 * @property \Illuminate\Support\Carbon $enrolled_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int $completion_percentage
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Module $module
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment whereCompletionPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment whereEnrolledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment whereModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModuleEnrollment whereUserId($value)
 */
	class ModuleEnrollment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string|null $contact_info
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Seminar> $seminars
 * @property-read int|null $seminars_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization verified()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereContactInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUserId($value)
 */
	class Organization extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $subscription_id
 * @property numeric $amount
 * @property string|null $method
 * @property string $status
 * @property string|null $transaction_id
 * @property string|null $payment_details
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Subscription|null $subscription
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUserId($value)
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $module_id
 * @property int|null $lesson_id
 * @property string $title
 * @property string|null $description
 * @property int $passing_score
 * @property int|null $time_limit
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\QuizAttempt> $attempts
 * @property-read int|null $attempts_count
 * @property-read \App\Models\Lesson|null $lesson
 * @property-read \App\Models\Module|null $module
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\QuizQuestion> $questions
 * @property-read int|null $questions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereLessonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz wherePassingScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereTimeLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quiz whereUpdatedAt($value)
 */
	class Quiz extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $quiz_id
 * @property string $user_answer
 * @property int $is_correct
 * @property int $score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Quiz $quiz
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt passed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereIsCorrect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereQuizId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereUserAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizAttempt whereUserId($value)
 */
	class QuizAttempt extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $quiz_id
 * @property int $attempts
 * @property string $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit whereQuizId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizDailyLimit whereUserId($value)
 */
	class QuizDailyLimit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $quiz_question_id
 * @property string $option_text
 * @property bool $is_correct
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\QuizQuestion $quizQuestion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption whereIsCorrect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption whereOptionText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption whereQuizQuestionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizOption whereUpdatedAt($value)
 */
	class QuizOption extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $quiz_id
 * @property string $question_text
 * @property string $question_type
 * @property int $points
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\QuizOption> $correctOptions
 * @property-read int|null $correct_options_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\QuizOption> $options
 * @property-read int|null $options_count
 * @property-read \App\Models\Quiz $quiz
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion wherePoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereQuestionText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereQuestionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereQuizId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuizQuestion whereUpdatedAt($value)
 */
	class QuizQuestion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Achievement|null $achievement
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RewardLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RewardLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RewardLog query()
 */
	class RewardLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $location
 * @property string $schedule
 * @property int $is_premium
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organization> $organizations
 * @property-read int|null $organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeminarRegistrant> $registrants
 * @property-read int|null $registrants_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar past()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar whereIsPremium($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar whereSchedule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seminar whereUpdatedAt($value)
 */
	class Seminar extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $seminar_id
 * @property int $user_id
 * @property string $status
 * @property \Illuminate\Support\Carbon $registered_at
 * @property \Illuminate\Support\Carbon|null $attended_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Seminar $seminar
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant attended()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant registered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant whereAttendedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant whereRegisteredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant whereSeminarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeminarRegistrant whereUserId($value)
 */
	class SeminarRegistrant extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $plan
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription premium()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription wherePlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscription withoutTrashed()
 */
	class Subscription extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string $status
 * @property bool $verified
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityLog> $activityLogs
 * @property-read int|null $activity_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Certificate> $certificates
 * @property-read int|null $certificates_count
 * @property-read \App\Models\Clinic|null $clinic
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Consultation> $consultations
 * @property-read int|null $consultations_count
 * @property-read \App\Models\Counselor|null $counselor
 * @property-read \App\Models\UserGamification|null $gamification
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ModuleEnrollment> $moduleEnrollments
 * @property-read int|null $module_enrollments_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Organization|null $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \App\Models\UserProfile|null $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\QuizAttempt> $quizAttempts
 * @property-read int|null $quiz_attempts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeminarRegistrant> $seminarRegistrants
 * @property-read int|null $seminar_registrants_count
 * @property-read \App\Models\Subscription|null $subscription
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserProgress> $userProgress
 * @property-read int|null $user_progress_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User byRole($role)
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User verified()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGamification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGamification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGamification query()
 */
	class UserGamification extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $bio
 * @property \Illuminate\Support\Carbon|null $birthdate
 * @property string|null $gender
 * @property string|null $location
 * @property string|null $avatar
 * @property string|null $contact
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereBirthdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUserId($value)
 */
	class UserProfile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $module_id
 * @property int $lesson_id
 * @property bool $completed
 * @property int $progress_percentage
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Lesson $lesson
 * @property-read \App\Models\Module $module
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereLessonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereModuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereProgressPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProgress whereUserId($value)
 */
	class UserProgress extends \Eloquent {}
}

