<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;
    protected $fillable = [
        'module_id',
        'lesson_id',
        'title',
        'description',
        'passing_score',
        'time_limit',
        'attempt_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'integer',
            'time_limit' => 'integer',
            'attempt_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper Methods

    public function getTotalPoints(): int
    {
        return $this->questions()->sum('points');
    }
}
