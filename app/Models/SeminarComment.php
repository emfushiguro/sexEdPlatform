<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeminarComment extends Model
{
    protected $fillable = [
        'seminar_id',
        'user_id',
        'body',
        'status',
        'hidden_by',
        'hidden_at',
        'hidden_reason',
    ];

    protected function casts(): array
    {
        return [
            'hidden_at' => 'datetime',
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

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }
}
