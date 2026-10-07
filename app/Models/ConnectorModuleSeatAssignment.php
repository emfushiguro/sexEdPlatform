<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectorModuleSeatAssignment extends Model
{
    use HasFactory;

    public const SOURCE_DIRECT = 'direct';
    public const SOURCE_INHERITED = 'inherited';
    public const STATUS_PENDING_GUARDIAN = 'pending_guardian';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INDEPENDENT = 'independent';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'connector_module_entitlement_id',
        'user_id',
        'connector_membership_id',
        'connector_module_access_rule_id',
        'source_type',
        'status',
        'inactive_reason',
        'consumes_seat',
        'assigned_at',
        'released_at',
        'revoked_at',
        'reactivated_at',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'consumes_seat' => 'boolean',
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
            'revoked_at' => 'datetime',
            'reactivated_at' => 'datetime',
        ];
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(ConnectorModuleEntitlement::class, 'connector_module_entitlement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(ConnectorMembership::class, 'connector_membership_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ConnectorModuleAccessRule::class, 'connector_module_access_rule_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
