<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeminarQuestion extends Model
{
    protected $fillable = [
        'seminar_id',
        'user_id',
        'question',
        'status',
        'answer',
        'answered_by',
        'answered_at',
        'hidden_by',
        'hidden_at',
        'hidden_reason',
        'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
            'hidden_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }
}
