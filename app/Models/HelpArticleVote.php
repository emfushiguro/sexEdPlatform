<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticleVote extends Model
{
    use HasFactory;

    protected $fillable = ['help_article_id', 'user_id', 'is_helpful'];

    protected function casts(): array
    {
        return ['is_helpful' => 'boolean'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpArticle::class, 'help_article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
