<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeminarRegistrant extends Model
{
    protected $fillable = [
        'seminar_id',
        'user_id',
        'status',
        'participant_type',
        'registered_at',
        'attended_at',
        'cancelled_at',
        'cancellation_reason',
        'rejection_reason',
        'decided_at',
        'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'attended_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    // Relationships

    public function seminar()
    {
        return $this->belongsTo(Seminar::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    public function scopeAttended($query)
    {
        return $query->where('status', 'attended');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'registered')->whereNull('cancelled_at');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
