<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'birthdate',
        'gender',
        'location',
        'avatar',
        'contact',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
