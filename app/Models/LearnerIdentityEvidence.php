<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerIdentityEvidence extends Model
{
    protected $fillable = [
        'verification_id',
        'slot',
        'storage_path',
        'mime_type',
        'byte_size',
        'width',
        'height',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function verification(): BelongsTo
    {
        return $this->belongsTo(LearnerIdentityVerification::class, 'verification_id');
    }
}
