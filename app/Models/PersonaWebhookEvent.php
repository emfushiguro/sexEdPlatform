<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonaWebhookEvent extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'event_id',
        'event_type',
        'persona_inquiry_id',
        'event_created_at',
        'processing_status',
        'received_at',
        'processed_at',
        'failed_at',
        'failure_category',
    ];

    protected function casts(): array
    {
        return [
            'event_created_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(PersonaInquiry::class, 'persona_inquiry_id', 'persona_inquiry_id');
    }
}
