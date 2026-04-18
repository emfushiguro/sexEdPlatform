<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportGenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'generated_at',
        'generated_by_user_id',
        'generated_by_role',
        'report_scope',
        'export_format',
        'filters_json',
        'checksum_hash',
        'row_count',
        'summary_snapshot_json',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'filters_json' => 'array',
            'summary_snapshot_json' => 'array',
        ];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }
}
