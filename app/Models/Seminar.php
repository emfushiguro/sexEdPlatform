<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seminar extends Model
{
    protected $fillable = [
        'connector_id',
        'type',
        'title',
        'description',
        'purpose',
        'category',
        'status',
        'location',
        'schedule',
        'starts_at',
        'ends_at',
        'capacity',
        'target_participants',
        'learner_age_categories',
        'livestream_channel',
        'is_premium',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'completed_at',
        'completed_by',
        'admin_moderation_status',
        'admin_moderation_reason',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'learner_age_categories' => 'array',
            'is_premium' => 'boolean',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(Connector::class);
    }

    public function connectors(): BelongsToMany
    {
        return $this->belongsToMany(Connector::class, 'seminar_organizations', 'seminar_id', 'organization_id');
    }

    public function registrants(): HasMany
    {
        return $this->hasMany(SeminarRegistrant::class);
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(SeminarSpeaker::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SeminarComment::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SeminarQuestion::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SeminarAttendance::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'seminar_registrants')
            ->withPivot('status', 'participant_type', 'registered_at', 'attended_at', 'cancelled_at')
            ->withTimestamps();
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where(function ($query) {
            $query->where('starts_at', '>', now())
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('starts_at')->where('schedule', '>', now());
                });
        });
    }

    public function scopePast($query)
    {
        return $query->where(function ($query) {
            $query->where('ends_at', '<=', now())
                ->orWhere(function ($fallback) {
                    $fallback->whereNull('ends_at')->where('schedule', '<=', now());
                });
        });
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeOwnedByConnector($query, Connector|int $connector)
    {
        return $query->where('connector_id', $connector instanceof Connector ? $connector->id : $connector);
    }
}
