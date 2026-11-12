<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\LearnerAgePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends Model
{
    use HasFactory;

    final public const STATUS_DRAFT = 'draft';

    final public const STATUS_PUBLISHED = 'published';

    final public const STATUS_ARCHIVED = 'archived';

    final public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = ['title', 'description', 'thumbnail', 'status', 'created_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pathModules(): HasMany
    {
        return $this->hasMany(LearningPathModule::class)->orderBy('position');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'learning_path_modules')
            ->withPivot(['id', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function learnerCategories(): HasMany
    {
        return $this->hasMany(LearningPathLearnerCategory::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeForLearnerCategory(Builder $query, string $category): Builder
    {
        $category = LearnerAgePolicy::normalizeActiveCategory($category);
        if ($category === null) {
            return $query->whereKey(-1);
        }

        $query->whereHas(
            'learnerCategories',
            fn (Builder $categories) => $categories->whereIn('category', [$category, rtrim($category, 's')])
        );

        return self::withoutLegacyKidsWording($query);
    }

    public function hasLegacyKidsOnlyTargeting(): bool
    {
        $raw = collect($this->rawLearnerCategoriesForLegacyAudit())
            ->map(fn (string $category): string => strtolower(trim($category)));

        return $raw->contains(fn (string $category): bool => in_array($category, ['kid', 'kids', 'child', 'children'], true))
            && $raw->filter(fn (string $category): bool => LearnerAgePolicy::normalizeActiveCategory($category) !== null)->isEmpty();
    }

    public function hasLegacyKidsWording(): bool
    {
        return preg_match('/\b(?:kid|kids)\b/i', trim((string) $this->title.' '.(string) $this->description)) === 1;
    }

    public function isDeliverableToCategory(string $category): bool
    {
        $category = LearnerAgePolicy::normalizeActiveCategory($category);

        return $category !== null
            && ! $this->hasLegacyKidsOnlyTargeting()
            && ! $this->hasLegacyKidsWording()
            && $this->learnerCategories()
                ->whereIn('category', [$category, rtrim($category, 's')])
                ->exists();
    }

    /** @return array<int, string> */
    public function rawLearnerCategoriesForLegacyAudit(): array
    {
        return $this->learnerCategories()
            ->pluck('category')
            ->map(fn ($category): string => (string) $category)
            ->values()
            ->all();
    }

    private static function withoutLegacyKidsWording(Builder $query): Builder
    {
        $driver = $query->getModel()->getConnection()->getDriverName();
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
}
