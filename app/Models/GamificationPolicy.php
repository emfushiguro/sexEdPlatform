<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GamificationPolicy extends Model
{
    protected $fillable = [
        'is_active',
        'policy_payload',
        'version_label',
        'change_summary',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'policy_payload' => 'array',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(GamificationPolicyVersion::class, 'policy_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function latestActive(): ?self
    {
        return static::query()
            ->active()
            ->latest('id')
            ->first();
    }
}
