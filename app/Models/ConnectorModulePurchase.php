<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConnectorModulePurchase extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ATTENTION_REQUIRED = 'attention_required';
    public const STATUS_CORRECTED = 'corrected';
    public const TERMS_VERSION = '2026-10-05';

    protected $fillable = [
        'connector_id',
        'module_marketplace_listing_id',
        'module_id',
        'purchased_by',
        'payment_id',
        'status',
        'gross_amount',
        'currency',
        'seat_capacity',
        'seller_type_snapshot',
        'seller_user_id_snapshot',
        'module_title_snapshot',
        'terms_version',
        'terms_accepted_at',
        'capacity_hold_expires_at',
        'provider_paid_at',
        'purchased_at',
        'failed_at',
        'failure_reason',
        'corrected_at',
        'correction_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'seat_capacity' => 'integer',
            'terms_accepted_at' => 'datetime',
            'capacity_hold_expires_at' => 'datetime',
            'provider_paid_at' => 'datetime',
            'purchased_at' => 'datetime',
            'failed_at' => 'datetime',
            'corrected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ModuleMarketplaceListing::class, 'module_marketplace_listing_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function entitlement(): HasOne
    {
        return $this->hasOne(ConnectorModuleEntitlement::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
