<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRuleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'target_user_id',
        'matched_violation_ids',
        'condition_snapshot',
        'action_executed',
        'enforcement_action_id',
        'status',
        'idempotency_key',
        'executed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'matched_violation_ids' => 'array',
            'condition_snapshot' => 'array',
            'executed_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ModerationAutomationRule::class, 'rule_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function enforcementAction(): BelongsTo
    {
        return $this->belongsTo(EnforcementAction::class, 'enforcement_action_id');
    }
}
