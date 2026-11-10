<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserOnboardingGuideState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'onboarding_guide_version_id',
        'dismissed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(OnboardingGuideVersion::class, 'onboarding_guide_version_id');
    }
}
