<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModuleSaleLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
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
}
