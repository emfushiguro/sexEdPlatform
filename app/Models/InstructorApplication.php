<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InstructorApplication extends Model
{
    use SoftDeletes;

    public const EDUCATIONAL_BACKGROUND_LABELS = [
        'high_school' => 'High School Graduate',
        'college_undergrad' => 'College Undergraduate',
        'college_graduate' => 'College Graduate',
        'masters' => "Master's Degree",
        'doctorate' => 'Doctorate Degree',
        'other' => 'Other',
    ];

    protected $fillable = [
        'user_id',
        'status',
        'educational_background',
        'government_id_path',
        'clearance_path',
        'cv_resume_path',
        'bio',
        'teaching_credential_path',
        'sexed_certificate_path',
        'professional_license_path',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejection_reason_code',
        'rejection_reason_note',
        'review_message',
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

    public function reviews()
    {
        return $this->hasMany(InstructorApplicationReview::class);
    }

    public function latestReview()
    {
        return $this->hasOne(InstructorApplicationReview::class)->latestOfMany('reviewed_at');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getEducationalBackgroundLabelAttribute(): ?string
    {
        if ($this->educational_background === null || $this->educational_background === '') {
            return null;
        }

        return self::EDUCATIONAL_BACKGROUND_LABELS[$this->educational_background]
            ?? Str::headline(str_replace('_', ' ', (string) $this->educational_background));
    }
}
