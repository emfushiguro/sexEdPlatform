<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'enrollment_mode',
        'final_quiz_id',
        'published_revision_id',
        'published_by_admin_id',
        'content_owner_type',
        'current_review_status',
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
            'enrollment_mode' => 'string',
            'published_revision_id' => 'integer',
            'published_by_admin_id' => 'integer',
            'content_owner_type' => 'string',
            'current_review_status' => 'string',
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

    public function revisions(): HasMany
    {
        return $this->hasMany(ModuleRevision::class);
    }

    public function reviewRequests(): HasMany
    {
        return $this->hasMany(ModuleReviewRequest::class);
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ModuleRevision::class, 'published_revision_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_admin_id');
    }

    // Scopes

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeLearnerVisible($query)
    {
        return $query->where('is_published', true)
            ->where(function ($inner) {
                $inner->whereNotNull('published_revision_id')
                    ->orWhereNull('current_review_status');
            });
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

    public function isLearnerVisible(): bool
    {
        if (!$this->is_published) {
            return false;
        }

        return $this->published_revision_id !== null || $this->current_review_status === null;
    }

    public function publishedSnapshot(): ?array
    {
        return $this->publishedRevision?->snapshot_payload;
    }

    public function applyPublishedSnapshot(): static
    {
        $snapshot = $this->publishedSnapshot();
        $moduleData = $snapshot['module'] ?? null;

        if (!is_array($moduleData)) {
            return $this;
        }

        foreach ($moduleData as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }
}
