<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamificationPolicyVersion extends Model
{
    protected $fillable = [
        'policy_id',
        'policy_payload',
        'version_label',
        'change_summary',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'policy_payload' => 'array',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(GamificationPolicy::class, 'policy_id');
    }
}
