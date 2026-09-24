<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerIdentityAudit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'verification_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'submission_round',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'submission_round' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(LearnerIdentityVerification::class, 'verification_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
