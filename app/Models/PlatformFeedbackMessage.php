<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformFeedbackMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_feedback_id',
        'sender_id',
        'sender_role',
        'body',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PlatformFeedback::class, 'platform_feedback_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
