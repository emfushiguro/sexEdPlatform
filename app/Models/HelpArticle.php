<?php

namespace App\Models;

use App\Enums\HelpArticleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'help_category_id',
        'created_by',
        'updated_by',
        'title',
        'slug',
        'summary',
        'keywords',
        'audiences',
        'status',
        'sort_order',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'audiences' => 'array',
            'status' => HelpArticleStatus::class,
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'help_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(HelpArticleSection::class)->orderBy('sort_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(HelpArticleVote::class);
    }

    public function scopePublishedForAudience(Builder $query, string $audience): Builder
    {
        return $query
            ->where('status', HelpArticleStatus::Published)
            ->whereNotNull('published_at')
            ->whereHas('category', static fn (Builder $category): Builder => $category->visibleToAudience($audience))
            ->where(function (Builder $audiences) use ($audience): void {
                $audiences->whereJsonContains('audiences', 'all')
                    ->orWhereJsonContains('audiences', $audience);
            });
    }
}
