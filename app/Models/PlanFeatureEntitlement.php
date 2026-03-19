<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeatureEntitlement extends Model
{
    protected $fillable = [
        'plan_id',
        'feature_id',
        'is_enabled',
        'quota_value',
        'is_unlimited',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_unlimited' => 'boolean',
            'quota_value' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(FeatureCatalog::class, 'feature_id');
    }
}
