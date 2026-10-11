<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon_key',
        'audiences',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'audiences' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(HelpArticle::class)->orderBy('sort_order')->orderBy('title');
    }

    public function scopeVisibleToAudience(Builder $query, string $audience): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $audiences) use ($audience): void {
                $audiences->whereJsonContains('audiences', 'all')
                    ->orWhereJsonContains('audiences', $audience);
            });
    }
}
