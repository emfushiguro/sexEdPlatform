<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuspensionAppeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_suspension_id',
        'user_id',
        'status',
        'appeal_reason',
        'evidence_payload',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_admin_id',
        'review_decision_notes',
        'clarification_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_payload' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'clarification_requested_at' => 'datetime',
        ];
    }

    public function suspension(): BelongsTo
    {
        return $this->belongsTo(UserSuspension::class, 'user_suspension_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}
