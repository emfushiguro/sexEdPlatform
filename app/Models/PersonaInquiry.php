<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaInquiry extends Model
{
    public const FLOW_TEEN_SCHOOL = 'teen_school';

    public const FLOW_TEEN_GOVERNMENT = 'teen_government';

    public const FLOW_ADULT_GOVERNMENT = 'adult_government';

    public const FLOW_GUARDIAN_GOVERNMENT = 'guardian_government';

    public const FLOW_INSTRUCTOR_GOVERNMENT = 'instructor_government';

    public const TEMPLATE_SELFIE = 'selfie';

    public const TEMPLATE_GOVID_SELFIE = 'govid_selfie';

    public const OUTCOME_NOT_READY = 'not_ready';

    public const OUTCOME_ELIGIBLE = 'eligible';

    public const OUTCOME_INELIGIBLE = 'ineligible';

    public const OUTCOME_RETRYABLE = 'retryable';

    public const OUTCOME_INCONCLUSIVE = 'inconclusive';

    public const OUTCOME_SANDBOX_REVIEW = 'sandbox_review';

    public const SANDBOX_REVIEW_REASON = 'Persona Sandbox manual review: country and ID type were not automatically verified.';

    public const SANDBOX_APPROVAL_REASON = 'Persona Sandbox test-only approval: administrator confirmed Philippine issuance, selected ID type and ID/selfie portrait match.';

    public const POLICY_NOT_APPLICABLE = 'not_applicable';

    public const POLICY_ELIGIBLE = 'eligible';

    public const POLICY_INELIGIBLE = 'ineligible';

    public const POLICY_INCONCLUSIVE = 'inconclusive';

    protected $fillable = [
        'user_id',
        'learner_identity_verification_id',
        'instructor_identity_verification_id',
        'flow_type',
        'target_submission_round',
        'attempt_number',
        'local_document_type',
        'local_government_id_type',
        'template_key',
        'template_id',
        'reference_id',
        'idempotency_key',
        'persona_inquiry_id',
        'government_id_verification_id',
        'selfie_verification_id',
        'persona_status',
        'verification_outcome',
        'country_code',
        'id_class',
        'country_policy_status',
        'document_policy_status',
        'is_sandbox',
        'persona_event_at',
        'last_synced_at',
        'completed_at',
        'expired_at',
        'superseded_at',
        'failure_category',
    ];

    protected $hidden = [
        'reference_id',
        'idempotency_key',
        'persona_inquiry_id',
        'government_id_verification_id',
        'selfie_verification_id',
    ];

    protected function casts(): array
    {
        return [
            'target_submission_round' => 'integer',
            'attempt_number' => 'integer',
            'is_sandbox' => 'boolean',
            'persona_event_at' => 'immutable_datetime',
            'last_synced_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'expired_at' => 'immutable_datetime',
            'superseded_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learnerVerification(): BelongsTo
    {
        return $this->belongsTo(LearnerIdentityVerification::class, 'learner_identity_verification_id');
    }

    public function instructorIdentityVerification(): BelongsTo
    {
        return $this->belongsTo(InstructorIdentityVerification::class);
    }

    public function isCurrent(): bool
    {
        return $this->superseded_at === null;
    }

    public function isPolicyEligible(): bool
    {
        if (! $this->isCurrent()
            || $this->verification_outcome !== self::OUTCOME_ELIGIBLE) {
            return false;
        }

        if ($this->flow_type === self::FLOW_TEEN_SCHOOL) {
            return $this->country_policy_status === self::POLICY_NOT_APPLICABLE
                && $this->document_policy_status === self::POLICY_NOT_APPLICABLE;
        }

        return $this->country_policy_status === self::POLICY_ELIGIBLE
            && $this->document_policy_status === self::POLICY_ELIGIBLE;
    }

    public static function localManualReviewEnabled(): bool
    {
        return config('persona.enabled', false)
            && config('persona.environment') === 'sandbox'
            && ((app()->environment('local') && config('persona.local_manual_review', false))
                || (app()->environment('staging') && config('persona.demo_manual_review', false)));
    }

    public function isSandboxReviewable(): bool
    {
        return self::localManualReviewEnabled() && $this->is_sandbox && $this->isCurrent()
            && $this->verification_outcome === self::OUTCOME_SANDBOX_REVIEW
            && $this->local_document_type === 'government_id'
            && in_array($this->flow_type, [self::FLOW_TEEN_GOVERNMENT, self::FLOW_ADULT_GOVERNMENT, self::FLOW_GUARDIAN_GOVERNMENT, self::FLOW_INSTRUCTOR_GOVERNMENT], true)
            && is_string(config('persona.government_id_classes.'.$this->local_government_id_type))
            && in_array($this->persona_status, ['completed', 'approved'], true)
            && $this->government_id_verification_id && $this->selfie_verification_id
            && $this->country_code && $this->id_class;
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('superseded_at');
    }
}
