<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentChildAccount extends Model
{
    protected $fillable = [
        'parent_user_id',
        'child_user_id',
        'can_view_progress',
        'can_view_quiz_answers',
        'can_approve_content',
        'verification_status',
        'verification_document_path',
        'verification_rejection_reason',
        'verification_reviewed_by',
        'verification_reviewed_at',
        'verification_approved_at',
        'relationship_verified_at',
    ];

    protected $casts = [
        'can_view_progress' => 'boolean',
        'can_view_quiz_answers' => 'boolean',
        'can_approve_content' => 'boolean',
        'verification_reviewed_at' => 'datetime',
        'verification_approved_at' => 'datetime',
        'relationship_verified_at' => 'datetime',
    ];

    /**
     * Get the parent user
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /**
     * Get the child user
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_user_id');
    }

    /**
     * Verify the parent-child relationship
     */
    public function verify(): void
    {
        $this->update(['relationship_verified_at' => now()]);
    }

    /**
     * Check if relationship is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'approved' && $this->relationship_verified_at !== null;
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }
}
