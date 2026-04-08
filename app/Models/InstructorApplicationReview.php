<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstructorApplicationReview extends Model
{
    protected $fillable = [
        'instructor_application_id',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_message',
        'reason_code',
        'reason_label',
        'reason_note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function application()
    {
        return $this->belongsTo(InstructorApplication::class, 'instructor_application_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
