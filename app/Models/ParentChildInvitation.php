<?php

namespace App\Models;

use App\Enums\ParentChildInvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentChildInvitation extends Model
{
    protected $fillable = [
        'inviter_parent_user_id',
        'child_user_id',
        'invite_token',
        'status',
        'message',
        'decision_note',
        'expires_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ParentChildInvitationStatus::class,
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function inviterParent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_parent_user_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === ParentChildInvitationStatus::Pending;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
