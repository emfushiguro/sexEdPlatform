<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstructorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'specialization',
        'credentials',
        'educational_background',
        'professional_background',
        'primary_expertise',
        'expertise_tags',
        'years_experience',
        'certifications',
        'profile_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'expertise_tags' => 'array',
            'certifications' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
