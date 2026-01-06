<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    const UPDATED_AT = null; // Only use created_at

    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper Methods

    public static function log(string $activityType, ?string $description = null, ?array $metadata = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'activity_type' => $activityType,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
