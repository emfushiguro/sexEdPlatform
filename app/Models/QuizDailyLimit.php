<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class QuizDailyLimit extends Model
{
    protected $fillable = [
        'user_id',
        'quiz_id',
        'attempts',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'attempts' => 'integer',
        ];
    }

    const MAX_FREE_ATTEMPTS = 3;

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    // Helper Methods

    public static function getRemainingAttempts(User $user, ?int $quizId = null): int
    {
        // Premium users have unlimited attempts
        if ($user->isPremium()) {
            return PHP_INT_MAX;
        }

        // If no quiz specified, count total attempts today across all quizzes
        $today = Carbon::today();
        
        if ($quizId) {
            // Get attempts for specific quiz
            $limit = static::firstOrCreate(
                ['user_id' => $user->id, 'quiz_id' => $quizId, 'date' => $today],
                ['attempts' => 0]
            );
            return max(0, static::MAX_FREE_ATTEMPTS - $limit->attempts);
        } else {
            // Get total attempts today
            $totalAttempts = static::where('user_id', $user->id)
                ->where('date', $today)
                ->sum('attempts');
            return max(0, static::MAX_FREE_ATTEMPTS - $totalAttempts);
        }
    }

    public static function incrementAttempts(User $user, int $quizId): void
    {
        // Don't increment for premium users
        if ($user->isPremium()) {
            return;
        }

        $today = Carbon::today();
        $limit = static::firstOrCreate(
            ['user_id' => $user->id, 'quiz_id' => $quizId, 'date' => $today],
            ['attempts' => 0]
        );

        $limit->increment('attempts');
    }
}
