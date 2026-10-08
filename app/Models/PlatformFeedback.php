<?php

namespace App\Models;

use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'submission_token',
        'user_id',
        'user_role',
        'type',
        'subject',
        'description',
        'rating',
        'user_agent',
        'may_contact',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PlatformFeedbackType::class,
            'status' => PlatformFeedbackStatus::class,
            'rating' => 'integer',
            'may_contact' => 'boolean',
            'reviewed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PlatformFeedbackHistory::class)->latest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PlatformFeedbackMessage::class)->oldest();
    }

    public function scopeForAdminFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['rating']) && $filters['rating'] !== '') {
            $query->where('rating', (int) $filters['rating']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.addcslashes(substr(trim((string) $filters['search']), 0, 100), '%_\\').'%';
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery->where('reference_number', 'like', $search)
                    ->orWhere('subject', 'like', $search)
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery->where('name', 'like', $search)->orWhere('email', 'like', $search);
                    });
            });
        }

        return $query;
    }
}
