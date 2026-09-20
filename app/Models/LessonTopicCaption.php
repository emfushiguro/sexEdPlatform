<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LessonTopicCaption extends Model
{
    protected $fillable = [
        'file_path',
        'language_code',
        'label',
        'is_default',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LessonTopic::class, 'lesson_topic_id');
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
