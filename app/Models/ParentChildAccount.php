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
        'relationship_verified_at',
    ];

    protected $casts = [
        'can_view_progress' => 'boolean',
        'can_view_quiz_answers' => 'boolean',
        'can_approve_content' => 'boolean',
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
        return $this->relationship_verified_at !== null;
    }
}
