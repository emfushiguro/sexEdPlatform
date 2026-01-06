<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserGamification extends Model
{
    protected $fillable = [
        'user_id',
        'level',
        'score',
        'streak_count',
        'last_act_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'score' => 'integer',
            'streak_count' => 'integer',
            'last_act_at' => 'datetime',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper Methods

    public function addPoints(int $points): void
    {
        $this->increment('score', $points);
        $this->updateLevel();
    }

    public function updateStreak(): void
    {
        if ($this->last_act_at === null || $this->last_act_at->isYesterday()) {
            $this->increment('streak_count');
        } elseif (!$this->last_act_at->isToday()) {
            $this->streak_count = 1;
        }

        $this->last_act_at = Carbon::now();
        $this->save();
    }

    protected function updateLevel(): void
    {
        // Simple level calculation: level = score / 100
        $newLevel = (int) floor($this->score / 100) + 1;
        if ($newLevel > $this->level) {
            $this->level = $newLevel;
            $this->save();
        }
    }
}
