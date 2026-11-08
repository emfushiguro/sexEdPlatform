<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorIdentityAudit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'instructor_identity_verification_id',
        'actor_id',
        'persona_inquiry_id',
        'action',
        'from_status',
        'to_status',
        'submission_round',
        'reason',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'submission_round' => 'integer',
            'metadata' => 'encrypted:array',
            'created_at' => 'datetime',
        ];
    }

    protected function performUpdate(Builder $query): bool
    {
        throw new \LogicException('Instructor identity audit records are append-only.');
    }

    protected function performDeleteOnModel(): void
    {
        throw new \LogicException('Instructor identity audit records are append-only.');
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(InstructorIdentityVerification::class, 'instructor_identity_verification_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function personaInquiry(): BelongsTo
    {
        return $this->belongsTo(PersonaInquiry::class);
    }
}
