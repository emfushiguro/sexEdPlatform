<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'quiz_id',
        'answers',
        'score',
        'passed',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'passed' => 'boolean',
            'answers' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    // Scopes

    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }
}
