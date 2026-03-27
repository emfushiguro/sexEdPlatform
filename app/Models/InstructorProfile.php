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
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
