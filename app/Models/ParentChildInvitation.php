<?php

namespace App\Models;

use App\Enums\ParentChildInvitationStatus;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParentChildInvitation extends Model
{
    protected $fillable = [
        'inviter_parent_user_id',
        'child_user_id',
        'parent_child_account_id',
        'relationship_type',
        'relationship_custom',
        'relationship_verification_documents',
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
            'relationship_verification_documents' => 'array',
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

    public function parentChildAccount(): BelongsTo
    {
        return $this->belongsTo(ParentChildAccount::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function relationshipLabel(): string
    {
        return GuardianRelationshipTypes::label($this->relationship_type, $this->relationship_custom);
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
