<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'revision_number',
        'snapshot_payload',
        'submitted_by',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_feedback',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_payload' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reviewRequests(): HasMany
    {
        return $this->hasMany(ModuleReviewRequest::class);
    }
}
