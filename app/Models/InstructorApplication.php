<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstructorApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'status',
        'educational_background',
        'government_id_path',
        'clearance_path',
        'bio',
        'teaching_credential_path',
        'sexed_certificate_path',
        'professional_license_path',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'application_metadata',
    ];

    protected function casts(): array
    {
        return [
            'application_metadata' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
