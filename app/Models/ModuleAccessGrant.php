<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleAccessGrant extends Model
{
    use HasFactory;

    public const SOURCE_DIRECT_ENROLLMENT = 'direct_enrollment';
    public const SOURCE_INDIVIDUAL_PURCHASE = 'individual_purchase';
    public const SOURCE_CONNECTOR_ASSIGNMENT = 'connector_assignment';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id',
        'module_id',
        'source_type',
        'source_id',
        'connector_id',
        'status',
        'granted_at',
        'ended_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'granted_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
