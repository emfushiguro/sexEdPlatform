<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGamification extends Model
{
    protected $fillable = [
        'user_id',
        'level',
        'score',
        'total_points',
        'streak_count',
        'last_act_at',
        'longest_streak',
        'streak_savers',
    ];

    protected function casts(): array
    {
        return [
            'level'          => 'integer',
            'score'          => 'integer',
            'total_points'   => 'integer',
            'streak_count'   => 'integer',
            'longest_streak' => 'integer',
            'streak_savers'  => 'integer',
            'last_act_at'    => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
