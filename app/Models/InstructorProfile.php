<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstructorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'educational_background',
        'professional_background',
        'specialization',
        'primary_expertise',
        'expertise_tags',
        'years_experience',
        'certifications',
        'profile_photo_path',
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'expertise_tags' => 'array',
            'certifications' => 'array',
            'credentials' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
