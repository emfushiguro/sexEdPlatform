<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectorModuleAccessRule extends Model
{
    use HasFactory;

    public const TARGET_ALL_MEMBERS = 'all_members';
    public const TARGET_ROLE = 'role';
    public const TARGET_MEMBER = 'member';

    protected $fillable = [
        'connector_module_entitlement_id',
        'target_type',
        'target_id',
        'rule_key',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(ConnectorModuleEntitlement::class, 'connector_module_entitlement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ConnectorModuleSeatAssignment::class);
    }
}
