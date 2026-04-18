<?php

namespace App\Models;

use App\Enums\ViolationSeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'moderation_case_id',
        'violation_type',
        'severity_level',
        'violation_points',
        'trigger_source',
        'expires_at',
        'issued_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'severity_level' => ViolationSeverity::class,
            'violation_points' => 'integer',
            'expires_at' => 'datetime',
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
