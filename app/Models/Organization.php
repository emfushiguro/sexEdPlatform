<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'contact_info',
        'description',
        'address',
        'verified',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seminars()
    {
        return $this->belongsToMany(Seminar::class, 'seminar_organizations');
    }

    // Scopes

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }
}
