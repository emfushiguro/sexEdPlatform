<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModuleSaleLedger extends Model
{
    use HasFactory;

    public const SALE_CHANNEL_INDIVIDUAL = 'individual';
    public const SALE_CHANNEL_CONNECTOR_MARKETPLACE = 'connector_marketplace';

    protected $fillable = [
        'payment_id',
        'sale_channel',
        'connector_module_purchase_id',
        'connector_id',
        'buyer_user_id',
        'seller_type',
        'seller_user_id',
        'seller_name_snapshot',
        'seat_capacity_snapshot',
        'module_purchase_id',
        'module_id',
        'instructor_id',
        'learner_id',
        'learner_name_snapshot',
        'currency',
        'gross_amount',
        'basis_amount',
        'commission_percent_snapshot',
        'commission_amount',
        'instructor_earnings_amount',
        'seller_net_amount',
        'tax_basis_snapshot',
        'refund_policy_snapshot',
        'sale_status',
        'payout_status',
        'payout_batch_reference',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'basis_amount' => 'decimal:2',
            'commission_percent_snapshot' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'instructor_earnings_amount' => 'decimal:2',
            'seller_net_amount' => 'decimal:2',
            'seat_capacity_snapshot' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function modulePurchase(): BelongsTo
    {
        return $this->belongsTo(ModulePurchase::class);
    }

    public function connectorModulePurchase(): BelongsTo
    {
        return $this->belongsTo(ConnectorModulePurchase::class);
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learner_id');
    }

    public function visibility(): HasOne
    {
        return $this->hasOne(InstructorEarningsVisibility::class, 'module_sale_ledger_id');
    }

    public function scopeForInstructor($query, int $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeForConnector($query, int $connectorId)
    {
        return $query->where('connector_id', $connectorId);
    }
}
