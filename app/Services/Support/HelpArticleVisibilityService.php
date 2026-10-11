<?php

namespace App\Services\Support;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;

class HelpArticleVisibilityService
{
    public function published(HelpArticle $article, string $audience): HelpArticle
    {
        return HelpArticle::query()
            ->publishedForAudience($audience)
            ->whereKey($article->getKey())
            ->firstOrFail();
    }

    public function admin(HelpArticle $article): HelpArticle
    {
        return $article->loadMissing('category');
    }

    public function isPubliclyCacheable(HelpArticle $article): bool
    {
        return $article->status === HelpArticleStatus::Published
            && $article->published_at !== null
            && in_array('all', (array) $article->audiences, true)
            && $article->category?->is_active === true
            && in_array('all', (array) $article->category?->audiences, true);
    }
}
