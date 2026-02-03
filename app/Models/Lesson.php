<?php

namespace App\Models;

use App\Helpers\VideoEmbedHelper;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'content_type',
        'text_content',
        'description',
        'video_provider',
        'video_id',
        'video_file_path',
        'file_path',
        'image_attachments',
        'slideshow_data',
        'interactive_config',
        'order',
        'duration',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'duration' => 'integer',
            'is_published' => 'boolean',
            'image_attachments' => 'array',
            'slideshow_data' => 'array',
            'interactive_config' => 'array',
        ];
    }

    // Accessors

    /**
     * Get the video embed URL
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        if ($this->content_type !== 'video' || !$this->video_provider || !$this->video_id) {
            return null;
        }

        return VideoEmbedHelper::getEmbedUrl($this->video_provider, $this->video_id);
    }

    /**
     * Get video thumbnail URL
     */
    public function getVideoThumbnailAttribute(): ?string
    {
        if ($this->content_type !== 'video' || !$this->video_provider || !$this->video_id) {
            return null;
        }

        return VideoEmbedHelper::getThumbnailUrl($this->video_provider, $this->video_id);
    }

    /**
     * Check if lesson is a video
     */
    public function isVideo(): bool
    {
        return $this->content_type === 'video';
    }

    /**
     * Check if lesson has downloadable content
     */
    public function hasDownload(): bool
    {
        return $this->content_type === 'worksheet' && !empty($this->file_path);
    }

    // Relationships

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function topics()
    {
        return $this->hasMany(LessonTopic::class)->orderBy('order');
    }

    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }

    /**
     * Get lesson completion percentage for a user based on topics.
     */
    public function getTopicCompletionPercentage($userId): int
    {
        $totalTopics = $this->topics()->count();
        
        if ($totalTopics === 0) {
            return 0;
        }

        $completedTopics = $this->topics()
            ->whereHas('progress', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->where('completed', true);
            })
            ->count();

        return (int) (($completedTopics / $totalTopics) * 100);
    }

    /**
     * Check if all topics are completed by user.
     */
    public function allTopicsCompletedBy($userId): bool
    {
        $totalTopics = $this->topics()->count();
        
        if ($totalTopics === 0) {
            return true; // No topics means lesson is accessible
        }

        return $this->getTopicCompletionPercentage($userId) === 100;
    }

    // Scopes

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
