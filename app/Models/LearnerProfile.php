<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'username_changed_at',
        'age_range',
        'gender',
        'municipality',
        'school',
        'bio',
        'avatar_path',
        'about',
    ];

    protected $casts = [
        'age_range' => 'string',
        'gender' => 'string',
        'username_changed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the learner profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if profile is completed (all required fields filled).
     */
    public function isCompleted(): bool
    {
        return !empty($this->username) 
            && !empty($this->age_range) 
            && !empty($this->municipality);
    }
}
