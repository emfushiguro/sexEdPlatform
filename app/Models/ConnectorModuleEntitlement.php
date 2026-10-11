<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectorModuleEntitlement extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_VOIDED = 'voided';
    public const STATUS_CORRECTED = 'corrected';

    protected $fillable = [
        'connector_id',
        'connector_module_purchase_id',
        'module_marketplace_listing_id',
        'module_id',
        'status',
        'gross_amount',
        'currency',
        'seat_capacity',
        'activated_at',
        'suspended_at',
        'status_reason',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'seat_capacity' => 'integer',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ConnectorModulePurchase::class, 'connector_module_purchase_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ModuleMarketplaceListing::class, 'module_marketplace_listing_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(ConnectorModuleAccessRule::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ConnectorModuleSeatAssignment::class);
    }

    public function isUsable(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_CORRECTED], true);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
