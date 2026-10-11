<?php

namespace App\Models;

use App\Enums\TestimonialStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_feedback_id',
        'submission_token',
        'user_id',
        'approved_by',
        'display_name',
        'display_role',
        'quotation',
        'show_profile_image',
        'show_role',
        'consent_given',
        'consented_at',
        'consent_withdrawn_at',
        'status',
        'sort_order',
        'published_at',
        'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TestimonialStatus::class,
            'show_profile_image' => 'boolean',
            'show_role' => 'boolean',
            'consent_given' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'consented_at' => 'datetime',
            'consent_withdrawn_at' => 'datetime',
        ];
    }

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(PlatformFeedback::class, 'platform_feedback_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', TestimonialStatus::Published)
            ->whereNotNull('published_at')
            ->where('consent_given', true)
            ->whereNull('consent_withdrawn_at')
            ->whereHas('user', function (Builder $user): void {
                $user->whereIn('role', ['learner', 'instructor'])
                    ->where(function (Builder $account): void {
                        $account->whereNull('account_type')->orWhereNotIn('account_type', [User::ACCOUNT_TYPE_LEARNER_CHILD, User::ACCOUNT_TYPE_LEARNER_TEEN, 'parent']);
                    })
                    ->where(function (Builder $status): void {
                        $status->where('status', User::STATUS_ACTIVE)->orWhereNull('status');
                    })
                    ->where(function (Builder $age): void {
                        $age->where('role', 'instructor')
                            ->orWhere(function (Builder $adult): void {
                                $adult->whereNotNull('birthdate')->whereDate('birthdate', '<=', now()->subYears(18)->toDateString())
                                    ->orWhere(function (Builder $fallback): void {
                                        $fallback->whereNull('birthdate')->where('age', '>=', 18);
                                    });
                            });
                    });
            });
    }
}
