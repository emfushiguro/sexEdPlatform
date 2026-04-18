<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModerationAutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'is_active',
        'priority',
        'conditions',
        'action_type',
        'severity_level',
        'trigger_type',
        'metadata',
        'current_version_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'conditions' => 'array',
            'metadata' => 'array',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ModerationAutomationRuleVersion::class, 'rule_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ModerationAutomationRuleVersion::class, 'current_version_id');
    }
}
