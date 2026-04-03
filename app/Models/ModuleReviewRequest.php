<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleReviewRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'module_revision_id',
        'status',
        'submitted_by',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class)->withTrashed();
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ModuleRevision::class, 'module_revision_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function violationRecords(): HasMany
    {
        return $this->hasMany(InstructorViolationHistory::class, 'module_review_request_id');
    }

    public function getModuleTitleAttribute(): string
    {
        if ($this->module?->title) {
            return $this->module->title;
        }

        $snapshotTitle = data_get($this->revision?->snapshot_payload, 'module.title');
        if (is_string($snapshotTitle) && trim($snapshotTitle) !== '') {
            return $snapshotTitle;
        }

        return 'Untitled module #' . (string) $this->module_id;
    }
}
