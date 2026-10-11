<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectorVerificationRequirement extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SATISFIED = 'satisfied';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'connector_id',
        'requirement_key',
        'evidence_category',
        'label',
        'guidance',
        'is_required',
        'rule_group',
        'rule_mode',
        'minimum_evidence_count',
        'maximum_evidence_count',
        'definition_snapshot',
        'source_snapshot',
        'status',
        'learner_identity_verification_id',
        'requested_by',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'minimum_evidence_count' => 'integer',
            'maximum_evidence_count' => 'integer',
            'definition_snapshot' => 'array',
            'source_snapshot' => 'array',
            'superseded_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function learnerIdentityVerification(): BelongsTo
    {
        return $this->belongsTo(LearnerIdentityVerification::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ConnectorVerificationDocument::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ConnectorVerificationAudit::class);
    }
}
