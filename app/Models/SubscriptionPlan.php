<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'features',
        'trial_days',
        'max_users',
        'max_modules',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'    => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getPrice(): float
    {
        return (float) ($this->price ?? 0.0);
    }

    public function hasFeature(string $feature): bool
    {
        if (is_array($this->features)) {
            return in_array($feature, $this->features);
        }
        return false;
    }

    public function getFeatureValue(string $feature, $default = null)
    {
        // For backwards compatibility - always returns true if feature exists
        if ($this->hasFeature($feature)) {
            return true;
        }
        return $default;
    }

    public function isFree(): bool
    {
        return $this->price == 0;
    }
}