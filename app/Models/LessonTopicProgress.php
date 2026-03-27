<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonTopicProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_topic_progress';

    protected $fillable = [
        'user_id',
        'lesson_topic_id',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the progress.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the topic that owns the progress.
     */
    public function lessonTopic(): BelongsTo
    {
        return $this->belongsTo(LessonTopic::class);
    }
}
