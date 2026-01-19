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
        'grade_level',
        'difficulty_level',
        'order',
        'duration_minutes',
        'is_published',
        'is_premium',
        'final_quiz_id',
        'certificate_pass_score',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'duration_minutes' => 'integer',
            'is_published' => 'boolean',
            'is_premium' => 'boolean',
            'certificate_pass_score' => 'integer',
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

    public function finalQuiz()
    {
        return $this->belongsTo(Quiz::class, 'final_quiz_id');
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

    /**
     * Scope to filter modules appropriate for user's grade level
     */
    public function scopeForGradeLevel($query, $gradeLevel)
    {
        // Grade level hierarchy
        $gradeLevels = [
            'grade_4_up' => 1,
            'grade_6_up' => 2,
            'grade_8_up' => 3,
            'grade_10_up' => 4,
            'adult_18_plus' => 5,
        ];

        $userGradeValue = $gradeLevels[$gradeLevel] ?? 1;

        // Return modules that are equal to or lower than the user's grade level
        return $query->where(function ($q) use ($gradeLevels, $userGradeValue) {
            foreach ($gradeLevels as $level => $value) {
                if ($value <= $userGradeValue) {
                    $q->orWhere('grade_level', $level);
                }
            }
        });
    }
}
