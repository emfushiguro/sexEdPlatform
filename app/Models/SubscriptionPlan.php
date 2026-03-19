<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class, 'plan_id');
    }

    public function defaultPlanPrice(): HasOne
    {
        return $this->hasOne(PlanPrice::class, 'plan_id')
            ->where('is_default', true)
            ->where('is_active', true);
    }

    public function featureEntitlements(): HasMany
    {
        return $this->hasMany(PlanFeatureEntitlement::class, 'plan_id');
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
        $features = $this->features;
        if (!is_array($features) || empty($features)) return false;

        // ── Flat array of strings: ['unlimited_quizzes', 'certificates', …]
        $firstVal = reset($features);
        if (is_string($firstVal) && !is_array($firstVal)) {
            return in_array($feature, $features);
        }

        // ── Grouped nested: ['learning' => ['full_course_access' => true, …], …]
        foreach ($features as $group => $items) {
            if (is_array($items)) {
                if (!array_key_exists($feature, $items)) continue;
                $val = $items[$feature];
                return $val === true
                    || (is_string($val) && !in_array($val, ['false', '0', '']))
                    || (is_numeric($val) && $val > 0);
            }
            // top-level key matches (e.g. legacy 'test_mode' => true)
            if ($group === $feature) {
                return !empty($items) && $items !== false;
            }
        }

        return false;
    }

    public function getFeatureValue(string $feature, $default = null)
    {
        $features = $this->features;
        if (!is_array($features)) return $default;

        // Flat array  — can only say yes/no
        $firstVal = reset($features);
        if (is_string($firstVal)) {
            return in_array($feature, $features) ? true : $default;
        }

        // Grouped nested — return actual value
        foreach ($features as $group => $items) {
            if (is_array($items) && array_key_exists($feature, $items)) {
                return $items[$feature];
            }
            if ($group === $feature) return $items;
        }

        return $default;
    }

    public function isFree(): bool
    {
        return $this->price == 0;
    }
}