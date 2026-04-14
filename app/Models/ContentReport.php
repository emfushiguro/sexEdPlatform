<?php

namespace App\Models;

use App\Enums\ContentReportStatus;
use App\Enums\ContentReportTargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'target_type',
        'target_id',
        'reason_code',
        'status',
        'details_html',
        'latest_outcome_message',
        'assigned_admin_id',
        'resolved_by',
        'resolved_at',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => ContentReportTargetType::class,
            'status' => ContentReportStatus::class,
            'resolved_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ContentReportActivity::class)->latest('id');
    }

    public function scopeActiveForTarget($query, int $reporterId, string $targetType, int $targetId)
    {
        return $query->where('reporter_id', $reporterId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->whereIn('status', [
                ContentReportStatus::Submitted->value,
                ContentReportStatus::UnderReview->value,
            ]);
    }
}
