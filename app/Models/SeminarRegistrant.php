<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeminarRegistrant extends Model
{
    protected $fillable = [
        'seminar_id',
        'user_id',
        'status',
        'registered_at',
        'attended_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'attended_at' => 'datetime',
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
}