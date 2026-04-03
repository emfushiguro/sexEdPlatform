<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorEarningsVisibility extends Model
{
    use HasFactory;

    protected $table = 'instructor_earnings_visibility';

    protected $fillable = [
        'module_sale_ledger_id',
        'instructor_id',
        'deleted_at',
        'deleted_by',
        'delete_reason',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(ModuleSaleLedger::class, 'module_sale_ledger_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
