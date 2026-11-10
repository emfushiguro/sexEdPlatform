<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMfaChallenge extends Model
{
    use HasUuids, MassPrunable;

    protected $fillable = [
        'user_id',
        'code_hash',
        'session_fingerprint',
        'credential_fingerprint',
        'attempt_count',
        'resend_count',
        'code_expires_at',
        'login_expires_at',
        'resend_available_at',
        'delivered_at',
        'delivery_failed_at',
        'consumed_at',
        'cancelled_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'resend_count' => 'integer',
            'code_expires_at' => 'datetime',
            'login_expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_failed_at' => 'datetime',
            'consumed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        $cutoff = now()->subDay();

        return static::query()->where(function (Builder $query) use ($cutoff): void {
            $query->where('login_expires_at', '<=', $cutoff)
                ->orWhere('consumed_at', '<=', $cutoff)
                ->orWhere('cancelled_at', '<=', $cutoff)
                ->orWhere('locked_at', '<=', $cutoff);
        });
    }
}
