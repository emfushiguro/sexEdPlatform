<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'order',
        'duration_minutes',
        'is_published',
        'is_premium',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'duration_minutes' => 'integer',
            'is_published' => 'boolean',
            'is_premium' => 'boolean',
        ];
    }

    // Relationships

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function enrollments()
    {
        return $this->hasMany(ModuleEnrollment::class);
    }

    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function attachments()
    {
        return $this->hasMany(ModuleAttachment::class);
    }

    // Scopes

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
