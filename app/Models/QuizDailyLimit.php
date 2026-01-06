<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class QuizDailyLimit extends Model
{
    protected $fillable = [
        'user_id',
        'quiz_date',
        'attempts_used',
    ];

    protected function casts(): array
    {
        return [
            'quiz_date' => 'date',
            'attempts_used' => 'integer',
        ];
    }

    const MAX_FREE_ATTEMPTS = 3;

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper Methods

    public static function getRemainingAttempts(User $user): int
    {
        if ($user->isPremium()) {
            return PHP_INT_MAX; // unlimited
        }

        $today = Carbon::today();
        $limit = static::firstOrCreate(
            ['user_id' => $user->id, 'quiz_date' => $today],
            ['attempts_used' => 0]
        );

        return max(0, static::MAX_FREE_ATTEMPTS - $limit->attempts_used);
    }

    public static function incrementAttempts(User $user): void
    {
        if ($user->isPremium()) {
            return; // no limits for premium
        }

        $today = Carbon::today();
        $limit = static::firstOrCreate(
            ['user_id' => $user->id, 'quiz_date' => $today],
            ['attempts_used' => 0]
        );

        $limit->increment('attempts_used');
    }
}
