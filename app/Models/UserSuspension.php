<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSuspension extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enforcement_action_id',
        'moderation_case_id',
        'status',
        'starts_at',
        'ends_at',
        'revoked_at',
        'revoked_by_admin_id',
        'revoked_reason',
        'appeal_status',
        'appeal_submitted_at',
        'notes',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'revoked_at' => 'datetime',
            'appeal_submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enforcementAction(): BelongsTo
    {
        return $this->belongsTo(EnforcementAction::class, 'enforcement_action_id');
    }

    public function moderationCase(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class, 'moderation_case_id');
    }

    public function revokedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_admin_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }
}
