<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seminar extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'schedule',
        'is_premium',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'datetime',
            'is_premium' => 'boolean',
        ];
    }

    // Relationships

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'seminar_organizations');
    }

    public function registrants()
    {
        return $this->hasMany(SeminarRegistrant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'seminar_registrants')
            ->withPivot('status', 'registered_at', 'attended_at')
            ->withTimestamps();
    }

    // Scopes

    public function scopeUpcoming($query)
    {
        return $query->where('schedule', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('schedule', '<=', now());
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }
}
