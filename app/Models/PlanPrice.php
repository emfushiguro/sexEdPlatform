<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPrice extends Model
{
    protected $fillable = [
        'plan_id',
        'duration_mode',
        'duration_unit',
        'duration_count',
        'duration_label',
        'amount_minor',
        'currency',
        'compare_at_minor',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'compare_at_minor' => 'integer',
            'duration_count' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_price_id');
    }

    public function getAmountAttribute(): string
    {
        return number_format(((int) ($this->amount_minor ?? 0)) / 100, 2, '.', '');
    }

    public function getDurationDisplayAttribute(): string
    {
        return $this->duration_label ?: ucfirst((string) $this->duration_unit);
    }
}
