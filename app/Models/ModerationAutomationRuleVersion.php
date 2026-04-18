<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAutomationRuleVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'version_number',
        'conditions',
        'action_type',
        'severity_level',
        'trigger_type',
        'created_by_admin_id',
        'activated_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'activated_at' => 'datetime',
            'is_active' => 'boolean',
            'version_number' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ModerationAutomationRule::class, 'rule_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }
}
