<?php

namespace App\Models;

use App\Enums\ModerationCaseSource;
use App\Enums\ModerationCaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_reference_code',
        'reporter_id',
        'reported_user_id',
        'content_type',
        'content_id',
        'case_source',
        'status',
        'severity_level',
        'decision',
        'reviewed_by_admin_id',
        'reviewed_at',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'case_source' => ModerationCaseSource::class,
            'status' => ModerationCaseStatus::class,
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}
