<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureCatalog extends Model
{
    protected $table = 'feature_catalog';

    protected $fillable = [
        'key',
        'name',
        'description',
        'value_type',
        'unit_label',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function planEntitlements(): HasMany
    {
        return $this->hasMany(PlanFeatureEntitlement::class, 'feature_id');
    }
}
