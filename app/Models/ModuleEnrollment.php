<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Model;

class ModuleEnrollment extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'status',
        'enrolled_at',
        'completed_at',
        'completion_percentage',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
            'completion_percentage' => 'integer',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    // Scopes

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNull('completed_at')->where('completion_percentage', '>', 0);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', EnrollmentStatus::Approved);
    }

    public function scopePending($query)
    {
        return $query->where('status', EnrollmentStatus::Pending);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', EnrollmentStatus::Rejected);
    }

    // Helper Methods

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
