<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructorIdentityVerification extends Model
{
    public const SOURCE_DIRECT_PERSONA = 'direct_persona';

    public const SOURCE_LEARNER_IDENTITY = 'learner_identity';

    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';

    public const STATUS_VERIFICATION_IN_PROGRESS = 'verification_in_progress';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ACTION_REQUIRED = 'action_required';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'source',
        'learner_identity_verification_id',
        'status',
        'submission_round',
        'declared_snapshot',
        'comparison_summary',
        'sandbox_manual_review',
        'resubmission_allowed',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'action_required_at',
        'reason_code',
        'reason_note',
        'requested_action_codes',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'submission_round' => 'integer',
            'declared_snapshot' => 'encrypted:array',
            'comparison_summary' => 'encrypted:array',
            'sandbox_manual_review' => 'boolean',
            'resubmission_allowed' => 'boolean',
            'requested_action_codes' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'action_required_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('superseded_at');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->current()->where('status', self::STATUS_PENDING_REVIEW);
    }

    public function scopeAccessApproved(Builder $query): Builder
    {
        return $query->current()->where('status', self::STATUS_APPROVED);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learnerIdentityVerification(): BelongsTo
    {
        return $this->belongsTo(LearnerIdentityVerification::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(InstructorIdentityAudit::class);
    }

    public function personaInquiries(): HasMany
    {
        return $this->hasMany(PersonaInquiry::class);
    }

    public function isAccessEligible(): bool
    {
        return $this->superseded_at === null
            && $this->status === self::STATUS_APPROVED
            && (! $this->sandbox_manual_review || PersonaInquiry::localManualReviewEnabled());
    }
}
