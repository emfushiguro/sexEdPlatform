<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminCreatorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'public_display_name',
        'bio',
        'affiliation',
        'avatar_path',
        'show_individual_attribution',
    ];

    protected function casts(): array
    {
        return [
            'show_individual_attribution' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
