<?php

namespace App\Models;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnforcementAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'moderation_case_id',
        'action_type',
        'severity_level',
        'trigger_type',
        'starts_at',
        'ends_at',
        'status',
        'issued_by_admin_id',
        'skip_ladder',
        'skip_rationale',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => EnforcementActionType::class,
            'severity_level' => ViolationSeverity::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'skip_ladder' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderationCase(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class);
    }

    public function issuedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_admin_id');
    }
}
