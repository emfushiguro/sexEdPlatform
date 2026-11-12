<?php

namespace App\Models;

use App\Support\LearnerAgePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
            'access_type' => 'string',
            'price_amount' => 'decimal:2',
            'price_currency' => 'string',
            'enrollment_limit' => 'integer',
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

    public function purchases()
    {
        return $this->hasMany(ModulePurchase::class);
    }

    public function marketplaceListing(): HasOne
    {
        return $this->hasOne(ModuleMarketplaceListing::class);
    }

    public function connectorModulePurchases(): HasMany
    {
        return $this->hasMany(ConnectorModulePurchase::class);
    }

    public function connectorModuleEntitlements(): HasMany
    {
        return $this->hasMany(ConnectorModuleEntitlement::class);
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(ModuleAccessGrant::class);
    }

    public function moduleSaleLedgers(): HasMany
    {
        return $this->hasMany(ModuleSaleLedger::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function feedback(): HasMany
    {
        return $this->hasMany(ModuleFeedback::class);
    }

    public function learnerCategories(): HasMany
    {
        return $this->hasMany(ModuleLearnerCategory::class);
    }

    public function learningPathMemberships(): HasMany
    {
        return $this->hasMany(LearningPathModule::class);
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
                    ->orWhere('current_review_status', 'approved')
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
        $ageBracket = self::ageBracketForAge($age);

        if ($ageBracket === null) {
            return $query->whereKey(-1);
        }

        return $this->scopeForAgeBracket($query, $ageBracket, $age);
    }

    /**
     * Scope to filter modules for age bracket
     */
    public function scopeForAgeBracket($query, string $ageBracket, ?int $age = null)
    {
        $ageRanges = self::learnerCategoryAgeRanges();

        if (! isset($ageRanges[$ageBracket])) {
            return $query->whereKey(-1);
        }

        [$minAge, $maxAge] = $ageRanges[$ageBracket];

        $query->where(function ($q) use ($ageBracket, $age, $minAge, $maxAge) {
            $q->whereHas('learnerCategories', function ($categoryQuery) use ($ageBracket) {
                $categoryQuery->whereIn('category', [$ageBracket, rtrim($ageBracket, 's')]);
            })->orWhere(function ($legacy) use ($age, $ageBracket, $minAge, $maxAge) {
                $legacy->whereDoesntHave('learnerCategories')
                    ->where('min_age', '<=', $age ?? $maxAge)
                    ->where('max_age', '>=', $age ?? $minAge)
                    ->where('max_age', '>=', $ageBracket === LearnerAgePolicy::CATEGORY_TEENAGERS ? 13 : 18);
            });
        });

        return self::withoutLegacyKidsWording($query);
    }

    /**
     * Check if module is appropriate for a specific age
     */
    public function isAppropriateForAge(int $age): bool
    {
        $ageBracket = self::ageBracketForAge($age);
        $this->loadMissing('learnerCategories');
        if ($ageBracket === null || $this->hasLegacyKidsWording()) {
            return false;
        }
        if ($this->learnerCategories->isNotEmpty()) {
            return $this->isDeliverableToCategory($ageBracket);
        }

        if ($this->min_age === null || $this->max_age === null) {
            return false;
        }

        $minAge = min((int) $this->min_age, (int) $this->max_age);
        $maxAge = max((int) $this->min_age, (int) $this->max_age);

        return $age >= $minAge
            && $age <= $maxAge
            && $this->max_age >= ($ageBracket === LearnerAgePolicy::CATEGORY_TEENAGERS ? 13 : 18);
    }

    public function isEligibleForLearnerCategory(string $category): bool
    {
        $category = self::normalizeLearnerCategory($category) ?? '';

        if ($category === '' || $this->hasLegacyKidsOnlyTargeting() || $this->hasLegacyKidsWording()) {
            return false;
        }

        if ($this->learnerCategories()->exists()) {
            return $this->learnerCategories()
                ->whereIn('category', [$category, rtrim($category, 's')])
                ->exists();
        }

        [$minAge, $maxAge] = self::learnerCategoryAgeRanges()[$category] ?? [null, null];

        return $minAge !== null
            && $this->min_age <= $maxAge
            && $this->max_age >= $minAge
            && $this->max_age >= ($category === LearnerAgePolicy::CATEGORY_TEENAGERS ? 13 : 18);
    }

    /**
     * @param  array<int, string>  $categories
     */
    public function syncLearnerCategories(array $categories): void
    {
        $invalid = collect($categories)
            ->filter(fn (mixed $category): bool => ! is_string($category)
                || ! in_array(strtolower(trim($category)), LearnerAgePolicy::activeCategories(), true));

        if ($invalid->isNotEmpty()) {
            throw new InvalidArgumentException('Choose only the active learner categories: Teenager or Adult.');
        }

        $normalized = self::normalizeLearnerCategories($categories);

        if ($normalized === []) {
            throw new InvalidArgumentException('Choose at least one active learner category.');
        }

        $this->learnerCategories()->delete();

        foreach ($normalized as $category) {
            $this->learnerCategories()->create(['category' => $category]);
        }
        $this->unsetRelation('learnerCategories');

        [$minAge, $maxAge] = self::ageRangeForLearnerCategories($normalized);
        $this->forceFill([
            'min_age' => $minAge,
            'max_age' => $maxAge,
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    public function learnerCategoryKeys(): array
    {
        $categories = $this->relationLoaded('learnerCategories')
            ? $this->learnerCategories->pluck('category')->all()
            : $this->learnerCategories()->pluck('category')->all();

        $normalized = self::normalizeLearnerCategories($categories);

        if ($categories !== []) {
            return $normalized;
        }

        return self::categoriesForAgeRange((int) $this->min_age, (int) $this->max_age);
    }

    /**
     * @return array<int, string>
     */
    public function learnerCategoryLabels(): array
    {
        return array_map(
            fn (string $category) => self::learnerCategoryLabelsMap()[$category] ?? ucfirst($category),
            $this->learnerCategoryKeys()
        );
    }

    /**
     * Get age-specific content for a given age bracket
     */
    public function getContentForAgeBracket(string $ageBracket): ?array
    {
        if (! $this->age_specific_content) {
            return null;
        }

        return $this->age_specific_content[$ageBracket] ?? null;
    }

    public function isLearnerVisible(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        return $this->published_revision_id !== null
            || $this->current_review_status === 'approved'
            || $this->current_review_status === null;
    }

    public function publishedSnapshot(): ?array
    {
        return $this->publishedRevision?->snapshot_payload;
    }

    public function applyPublishedSnapshot(): static
    {
        $snapshot = $this->publishedSnapshot();
        $moduleData = $snapshot['module'] ?? null;

        if (! is_array($moduleData)) {
            return $this;
        }

        // Keep live governance and lifecycle flags from the canonical module row.
        $protectedKeys = [
            'id',
            'created_by',
            'created_at',
            'updated_at',
            'deleted_at',
            'is_published',
            'current_review_status',
            'published_revision_id',
            'published_by_admin_id',
            'content_owner_type',
        ];

        foreach ($moduleData as $key => $value) {
            if (in_array($key, $protectedKeys, true)) {
                continue;
            }

            $this->setAttribute($key, $value);
        }

        return $this;
    }

    public function isPaidAccess(): bool
    {
        return $this->access_type === 'paid' && (float) ($this->price_amount ?? 0) > 0;
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null
            && (int) ($this->created_by ?? 0) > 0
            && (int) $this->created_by === (int) $user->id;
    }

    public function getDisplayPriceAttribute(): string
    {
        if (! $this->isPaidAccess()) {
            return 'Free';
        }

        $amount = (float) ($this->price_amount ?? 0);
        $formatted = fmod($amount, 1.0) === 0.0
            ? number_format($amount, 0)
            : number_format($amount, 2);

        return '₱'.$formatted;
    }

    /**
     * Get a resilient public URL for module thumbnails.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->resolvePublicMediaUrl($this->thumbnail, 'modules');
    }

    public function averageFeedbackRating(): float
    {
        return (float) $this->feedback()->avg('rating');
    }

    public function feedbackCount(): int
    {
        return (int) $this->feedback()->count();
    }

    /**
     * @param  array<int, string>  $categories
     * @return array<int, string>
     */
    public static function normalizeLearnerCategories(array $categories): array
    {
        return collect($categories)
            ->map(fn ($category) => is_string($category) ? self::normalizeLearnerCategory($category) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function normalizeLearnerCategory(string $category): ?string
    {
        return LearnerAgePolicy::normalizeActiveCategory($category);
    }

    /**
     * @return array<string, array{0:int,1:int}>
     */
    public static function learnerCategoryAgeRanges(): array
    {
        return [
            LearnerAgePolicy::CATEGORY_TEENAGERS => [10, 17],
            LearnerAgePolicy::CATEGORY_ADULTS => [18, 100],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function learnerCategoryLabelsMap(): array
    {
        return [
            LearnerAgePolicy::CATEGORY_TEENAGERS => 'Teenager',
            LearnerAgePolicy::CATEGORY_ADULTS => 'Adult',
        ];
    }

    /**
     * @param  array<int, string>  $categories
     * @return array{0:int,1:int}
     */
    public static function ageRangeForLearnerCategories(array $categories): array
    {
        $ranges = collect(self::normalizeLearnerCategories($categories))
            ->map(fn (string $category) => self::learnerCategoryAgeRanges()[$category])
            ->values();

        if ($ranges->isEmpty()) {
            throw new InvalidArgumentException('Choose at least one active learner category.');
        }

        return [
            (int) $ranges->min(fn (array $range) => $range[0]),
            (int) $ranges->max(fn (array $range) => $range[1]),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function categoriesForAgeRange(int $minAge, int $maxAge): array
    {
        $rawMinAge = $minAge;
        $rawMaxAge = $maxAge;
        $minAge = min($rawMinAge, $rawMaxAge);
        $maxAge = max($rawMinAge, $rawMaxAge);

        if ($maxAge < 13) {
            return [];
        }

        return collect(self::learnerCategoryAgeRanges())
            ->filter(fn (array $range) => $minAge <= $range[1] && $maxAge >= $range[0])
            ->keys()
            ->values()
            ->all();
    }

    public static function ageBracketForAge(int $age): ?string
    {
        foreach (self::learnerCategoryAgeRanges() as $category => [$minAge, $maxAge]) {
            if ($age >= $minAge && $age <= $maxAge) {
                return $category;
            }
        }

        return null;
    }

    /** @return array<int, string> */
    public function rawLearnerCategoriesForLegacyAudit(): array
    {
        return ($this->relationLoaded('learnerCategories')
            ? $this->learnerCategories
            : $this->learnerCategories()->get())
            ->pluck('category')
            ->map(fn ($category): string => (string) $category)
            ->values()
            ->all();
    }

    public function hasLegacyKidsOnlyTargeting(): bool
    {
        $raw = $this->rawLearnerCategoriesForLegacyAudit();
        $hasKids = collect($raw)->contains(fn (string $category): bool => in_array(strtolower(trim($category)), ['kid', 'kids', 'child', 'children'], true));

        return $hasKids && self::normalizeLearnerCategories($raw) === [];
    }

    public function hasLegacyKidsWording(): bool
    {
        return preg_match('/\b(?:kid|kids)\b/i', trim((string) $this->title.' '.(string) $this->description)) === 1;
    }

    public function isDeliverableToCategory(string $category): bool
    {
        $category = self::normalizeLearnerCategory($category);

        return $category !== null
            && ! $this->hasLegacyKidsOnlyTargeting()
            && ! $this->hasLegacyKidsWording()
            && in_array($category, $this->learnerCategoryKeys(), true);
    }

    private static function withoutLegacyKidsWording($query)
    {
        $connection = $query->getModel()->getConnection();
        $driver = $connection->getDriverName();
        $text = $driver === 'pgsql'
            ? "coalesce(title, '') || ' ' || coalesce(description, '')"
            : ($driver === 'mysql'
                ? "concat(coalesce(title, ''), ' ', coalesce(description, ''))"
                : "coalesce(title, '') || ' ' || coalesce(description, '')");

        if ($driver === 'pgsql') {
            return $query->whereRaw("{$text} !~* ?", ['(^|[^[:alnum:]_])(kid|kids)([^[:alnum:]_]|$)']);
        }

        if ($driver === 'mysql') {
            return $query->whereRaw("{$text} NOT REGEXP ?", ['(^|[^[:alnum:]_])(kid|kids)([^[:alnum:]_]|$)']);
        }

        $normalized = "lower(replace(replace(replace(replace(replace(replace({$text}, '-', ' '), ',', ' '), '.', ' '), ':', ' '), '!', ' '), '?', ' '))";

        return $query
            ->whereRaw("instr(' ' || {$normalized} || ' ', ' kid ') = 0")
            ->whereRaw("instr(' ' || {$normalized} || ' ', ' kids ') = 0");
    }

    private function resolvePublicMediaUrl(?string $path, string $defaultDirectory = ''): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        if (! str_contains($normalized, '/') && $defaultDirectory !== '') {
            $normalized = trim($defaultDirectory, '/').'/'.$normalized;
        }

        return Storage::url($normalized);
    }
}
