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
        'video_provider',
        'video_id',
        'file_path',
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

    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
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
