<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinorRegistrationRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'existing_child_user_id',
        'guardian_email',
        'guardian_email_fingerprint',
        'minor_payload',
        'token_hash',
        'status',
        'expires_at',
        'accepted_by_user_id',
        'accepted_at',
    ];

    protected $casts = [
        'guardian_email' => 'encrypted',
        'minor_payload' => 'encrypted',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function existingChild(): BelongsTo
    {
        return $this->belongsTo(User::class, 'existing_child_user_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }
}
