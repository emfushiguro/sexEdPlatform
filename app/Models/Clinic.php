<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'contact',
        'services',
        'operating_hours',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'verified',
        'is_premium',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'is_premium' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    // Helper Methods

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }
}
