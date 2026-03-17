<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LessonTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'type',
        'video_provider',
        'video_id',
        'video_file_path',
        'caption_file_path',
        'text_content',
        'file_path',
        'worksheet_files',
        'quiz_id',
        'interactive_config',
        'image_attachments',
        'slideshow_data',
        'duration',
        'is_prerequisite',
        'order',
    ];

    protected $casts = [
        'is_prerequisite' => 'boolean',
        'duration' => 'integer',
        'order' => 'integer',
        'interactive_config' => 'array',
        'image_attachments' => 'array',
        'slideshow_data' => 'array',
        'worksheet_files' => 'array',
    ];

    /**
     * Get the lesson that owns the topic.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the quiz associated with this topic (if type is 'quiz').
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get all progress records for this topic.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonTopicProgress::class);
    }

    /**
     * Check if user has completed this topic.
     */
    public function isCompletedBy($userId): bool
    {
        return $this->progress()
            ->where('user_id', $userId)
            ->where('completed', true)
            ->exists();
    }

    /**
     * Mark topic as completed for user.
     */
    public function markCompleted($userId): void
    {
        $this->progress()->updateOrCreate(
            ['user_id' => $userId],
            [
                'completed' => true,
                'completed_at' => now(),
            ]
        );
    }

    /**
     * Scope to order topics by their order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get the video embed URL for video topics.
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        if ($this->type !== 'video' || !$this->video_provider || !$this->video_id) {
            return null;
        }

        return \App\Helpers\VideoEmbedHelper::getEmbedUrl($this->video_provider, $this->video_id);
    }

    /**
     * Get video thumbnail URL for video topics.
     */
    public function getVideoThumbnailAttribute(): ?string
    {
        if ($this->type !== 'video' || !$this->video_provider || !$this->video_id) {
            return null;
        }

        return \App\Helpers\VideoEmbedHelper::getThumbnailUrl($this->video_provider, $this->video_id);
    }
}
