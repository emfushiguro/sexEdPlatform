<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class OnboardingGuideVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'onboarding_guide_id',
        'version_number',
        'title',
        'description',
        'video_disk',
        'video_path',
        'video_original_name',
        'video_mime_type',
        'video_size',
        'requires_recompletion',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'requires_recompletion' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(OnboardingGuide::class, 'onboarding_guide_id');
    }

    public function states(): HasMany
    {
        return $this->hasMany(UserOnboardingGuideState::class, 'onboarding_guide_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
