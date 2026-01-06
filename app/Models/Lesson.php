<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'content',
        'order',
        'video_url',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    // Relationships

    public function module()
    {
        return $this->belongsTo(Module::class);
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
