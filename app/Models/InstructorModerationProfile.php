<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorModerationProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'warning_count',
        'current_restriction_status',
        'restriction_starts_at',
        'restriction_ends_at',
        'last_violation_at',
        'escalation_level',
    ];

    protected function casts(): array
    {
        return [
            'warning_count' => 'integer',
            'restriction_starts_at' => 'datetime',
            'restriction_ends_at' => 'datetime',
            'last_violation_at' => 'datetime',
            'escalation_level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
