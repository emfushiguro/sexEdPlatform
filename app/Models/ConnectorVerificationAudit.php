<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectorVerificationAudit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'connector_id',
        'connector_verification_requirement_id',
        'connector_verification_document_id',
        'actor_id',
        'action',
        'previous_state',
        'new_state',
        'submission_round',
        'applicant_reason',
        'internal_notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_state' => 'array',
            'new_state' => 'array',
            'submission_round' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected function performUpdate(Builder $query): never
    {
        throw new \LogicException('Connector verification audits are append-only.');
    }

    protected function performDeleteOnModel(): never
    {
        throw new \LogicException('Connector verification audits are append-only.');
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ConnectorVerificationRequirement::class, 'connector_verification_requirement_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ConnectorVerificationDocument::class, 'connector_verification_document_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
