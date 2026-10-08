<?php

declare(strict_types=1);

namespace App\Models;

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
        return $query->whereHas(
            'learnerCategories',
            fn (Builder $categories) => $categories->where('category', $category)
        );
    }
}
