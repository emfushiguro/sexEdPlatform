<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianIdentityAudit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'guardian_id',
        'actor_id',
        'persona_inquiry_id',
        'action',
        'from_status',
        'to_status',
        'attempt_number',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected function performUpdate(Builder $query): bool
    {
        throw new \LogicException('Guardian identity audit records are append-only.');
    }

    protected function performDeleteOnModel(): void
    {
        throw new \LogicException('Guardian identity audit records are append-only.');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
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
