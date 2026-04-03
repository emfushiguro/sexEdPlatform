<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPolicyAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_admin_id',
        'action_type',
        'before_payload',
        'after_payload',
        'request_meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'before_payload' => 'array',
            'after_payload' => 'array',
            'request_meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actorAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_admin_id');
    }
}
