<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeminarSpeaker extends Model
{
    protected $fillable = [
        'seminar_id',
        'user_id',
        'display_name',
        'title',
        'bio',
        'role',
        'status',
        'invitation_message',
        'application_motivation',
        'application_expertise',
        'application_experience',
        'application_supporting_info',
        'review_note',
        'reviewed_by',
        'invited_at',
        'responded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
