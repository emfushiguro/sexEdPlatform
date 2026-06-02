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
    ];

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
