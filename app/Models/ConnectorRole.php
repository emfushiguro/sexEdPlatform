<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectorRole extends Model
{
    protected $fillable = [
        'connector_id',
        'name',
        'description',
        'is_owner',
        'is_protected',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'is_protected' => 'boolean',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(ConnectorRolePermission::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ConnectorMembership::class);
    }

    public function permissionKeys(): array
    {
        return $this->permissions->pluck('permission_key')->all();
    }
}
