<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'lesson_id',
        'completed',
        'progress_percentage',
        'completed_lessons_count',
        'last_accessed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'progress_percentage' => 'integer',
            'completed_lessons_count' => 'integer',
            'last_accessed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    // Scopes

    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('completed', false)->where('progress_percentage', '>', 0);
    }
}
