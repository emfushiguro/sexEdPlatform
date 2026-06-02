<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectorMembership extends Model
{
    protected $fillable = [
        'connector_id',
        'user_id',
        'connector_role_id',
        'status',
        'accepted_at',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ConnectorRole::class, 'connector_role_id');
    }
}
