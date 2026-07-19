<?php

namespace App\Models;

use App\Services\Seminars\SeminarCategoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Seminar extends Model
{
    protected $fillable = [
        'connector_id',
        'type',
        'title',
        'description',
        'purpose',
        'category',
        'custom_category',
        'status',
        'location',
        'schedule',
        'starts_at',
        'ends_at',
        'capacity',
        'registration_approval_mode',
        'target_participants',
        'learner_age_categories',
        'livestream_channel',
        'livestream_status',
        'livestream_started_at',
        'livestream_ended_at',
        'is_premium',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'completed_at',
        'completed_by',
        'admin_moderation_status',
        'admin_moderation_reason',
        'submitted_for_review_at',
        'submitted_for_review_by',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'moderator_note',
        'published_at',
        'published_by',
        'archived_at',
        'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'learner_age_categories' => 'array',
            'is_premium' => 'boolean',
            'livestream_started_at' => 'datetime',
            'livestream_ended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'submitted_for_review_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function connectors(): BelongsToMany
    {
        return $this->belongsToMany(Connector::class, 'seminar_organizations', 'seminar_id', 'organization_id');
    }

    public function registrants(): HasMany
    {
        return $this->hasMany(SeminarRegistrant::class);
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(SeminarSpeaker::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SeminarComment::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SeminarQuestion::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SeminarAttendance::class);
    }

    public function moderationReviews(): HasMany
    {
        return $this->hasMany(SeminarModerationReview::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'seminar_registrants')
            ->withPivot('status', 'participant_type', 'registered_at', 'attended_at', 'cancelled_at')
            ->withTimestamps();
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function submittedForReviewBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_for_review_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function categoryDisplayName(): string
    {
        return app(SeminarCategoryService::class)->displayName($this);
    }

    public function localStartsAt(): ?Carbon
    {
        return ($this->starts_at ?? $this->schedule)?->copy()->timezone(config('app.display_timezone'));
    }

    public function localEndsAt(): ?Carbon
    {
        return $this->ends_at?->copy()->timezone(config('app.display_timezone'));
    }

    public function formattedSchedule(): string
    {
        $startsAt = $this->localStartsAt();

        if (! $startsAt) {
            return 'To be announced';
        }

        $endsAt = $this->localEndsAt();

        return $startsAt->format('M d, Y h:i A')
            .($endsAt ? ' - '.$endsAt->format('h:i A') : '')
            .' PHT';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where(function ($query) {
            $query->where('starts_at', '>', now())
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('starts_at')->where('schedule', '>', now());
                });
        });
    }

    public function scopePast($query)
    {
        return $query->where(function ($query) {
            $query->where('ends_at', '<=', now())
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('ends_at')->where('schedule', '<=', now());
                });
        });
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeOwnedByConnector($query, Connector|int $connector)
    {
        return $query->where('connector_id', $connector instanceof Connector ? $connector->id : $connector);
    }
}
