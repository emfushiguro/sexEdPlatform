<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformFeedbackHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_feedback_id',
        'actor_id',
        'from_status',
        'to_status',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PlatformFeedback::class, 'platform_feedback_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
