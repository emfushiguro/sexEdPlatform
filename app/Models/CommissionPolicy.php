<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPolicy extends Model
{
    use HasFactory;

    public const SCOPE_GLOBAL = 'global';
    public const SCOPE_INSTRUCTOR = 'instructor';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'commission_percent',
        'tax_basis',
        'refund_policy',
        'is_active',
        'effective_from',
        'effective_to',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'commission_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scope_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->where('scope_type', self::SCOPE_GLOBAL);
    }

    public function scopeInstructor($query, int $instructorId)
    {
        return $query
            ->where('scope_type', self::SCOPE_INSTRUCTOR)
            ->where('scope_id', $instructorId);
    }

    public function scopeEffectiveAt($query, $at = null)
    {
        $at = $at ?? now();

        return $query
            ->where('effective_from', '<=', $at)
            ->where(function ($inner) use ($at) {
                $inner->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $at);
            });
    }

    public function isEffectiveAt($at = null): bool
    {
        $at = $at ?? now();

        if (!$this->is_active) {
            return false;
        }

        if ($this->effective_from && $this->effective_from->gt($at)) {
            return false;
        }

        if ($this->effective_to && $this->effective_to->lt($at)) {
            return false;
        }

        return true;
    }
}
