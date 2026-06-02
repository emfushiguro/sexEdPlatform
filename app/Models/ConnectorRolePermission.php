<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectorRolePermission extends Model
{
    protected $fillable = [
        'connector_role_id',
        'permission_key',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(ConnectorRole::class, 'connector_role_id');
    }
}
