<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class OnboardingGuide extends Model
{
    use HasFactory;

    public const GUIDE_KEY_DASHBOARD_NAVIGATION = 'dashboard_navigation';

    public const ROLE_LEARNER = 'learner';

    public const ROLE_INSTRUCTOR = 'instructor';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'guide_key',
        'target_role',
        'status',
        'current_version_id',
        'created_by',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(OnboardingGuideVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(OnboardingGuideVersion::class, 'current_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
