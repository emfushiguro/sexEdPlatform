<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'thumbnail',
        'min_age',
        'max_age',
        'age_specific_content',
        'difficulty_level',
        'order',
        'duration_minutes',
        'is_published',
        'is_premium',
        'access_type',
        'price_amount',
        'price_currency',
        'enrollment_limit',
        'enrollment_mode',
        'final_quiz_id',
        'certificate_pass_score',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'age_specific_content' => 'array',
            'order' => 'integer',
            'duration_minutes' => 'integer',
            'is_published' => 'boolean',
            'is_premium' => 'boolean',
            'access_type' => 'string',
            'price_amount' => 'decimal:2',
            'price_currency' => 'string',
            'enrollment_limit' => 'integer',
            'enrollment_mode' => 'string',
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
     * Scope to filter modules appropriate for user's age
     */
    public function scopeForAge($query, int $age)
    {
        return $query->where(function ($q) use ($age) {
            $q->where(function ($normal) use ($age) {
                $normal->where('min_age', '<=', $age)
                    ->where('max_age', '>=', $age);
            })->orWhere(function ($swapped) use ($age) {
                $swapped->where('max_age', '<=', $age)
                    ->where('min_age', '>=', $age);
            });
        });
    }

    /**
     * Scope to filter modules for age bracket
     */
    public function scopeForAgeBracket($query, string $ageBracket)
    {
        $ageRanges = [
            'kids' => [5, 12],
            'teens' => [13, 17],
            'adults' => [18, 100],
        ];

        if (!isset($ageRanges[$ageBracket])) {
            return $query;
        }

        [$minAge, $maxAge] = $ageRanges[$ageBracket];

        // Return modules that overlap with the age bracket
        return $query->where(function ($q) use ($minAge, $maxAge) {
            $q->where(function ($subQ) use ($minAge, $maxAge) {
                $subQ->where('min_age', '<=', $maxAge)
                     ->where('max_age', '>=', $minAge);
            });
        });
    }

    /**
     * Check if module is appropriate for a specific age
     */
    public function isAppropriateForAge(int $age): bool
    {
        if ($this->min_age === null || $this->max_age === null) {
            return false;
        }

        $minAge = min((int) $this->min_age, (int) $this->max_age);
        $maxAge = max((int) $this->min_age, (int) $this->max_age);

        return $age >= $minAge && $age <= $maxAge;
    }

    /**
     * Get age-specific content for a given age bracket
     */
    public function getContentForAgeBracket(string $ageBracket): ?array
    {
        if (!$this->age_specific_content) {
            return null;
        }

        return $this->age_specific_content[$ageBracket] ?? null;
    }
}
