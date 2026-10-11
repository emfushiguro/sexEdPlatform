<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectorVerificationDocument extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'connector_id',
        'connector_verification_requirement_id',
        'uploaded_by',
        'submission_round',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'content_sha256',
        'issuer',
        'document_number',
        'issue_date',
        'expiry_date',
        'status',
        'reviewed_by',
        'reviewed_at',
        'applicant_rejection_reason',
        'internal_review_note',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'submission_round' => 'integer',
            'size_bytes' => 'integer',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'reviewed_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ConnectorVerificationRequirement::class, 'connector_verification_requirement_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ConnectorVerificationAudit::class);
    }
}
