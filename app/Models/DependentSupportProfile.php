<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DependentSupportProfile extends Model
{
    public const NOTICE_VERSION = '2026-09-09-v1';

    public const CONTENT_FIELDS = [
        'relevant_health_considerations',
        'accessibility_support_needs',
        'additional_relevant_information',
    ];

    protected $fillable = [
        'dependent_user_id',
        'relevant_health_considerations',
        'accessibility_support_needs',
        'additional_relevant_information',
        'privacy_notice_version',
        'purpose_acknowledged_at',
        'purpose_acknowledged_by_user_id',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'relevant_health_considerations' => 'encrypted',
            'accessibility_support_needs' => 'encrypted',
            'additional_relevant_information' => 'encrypted',
            'purpose_acknowledged_at' => 'datetime',
        ];
    }

    public function dependent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dependent_user_id');
    }

    public function purposeAcknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purpose_acknowledged_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
