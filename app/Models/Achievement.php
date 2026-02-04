<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'badge_icon',
        'points',
    ];

    // Relationships

    public function rewardLogs()
    {
        return $this->hasMany(RewardLog::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'reward_logs')
            ->withPivot('earned_at')
            ->withTimestamps();
    }
}