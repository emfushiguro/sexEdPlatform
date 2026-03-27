<?php

namespace App\Services;

use App\Models\User;

class GamificationService
{
    public function awardPoints(User $user, string $reason, int $points): void
    {
        $gamification = $user->gamification;
        if (!$gamification) {
            return;
        }

        $gamification->increment('score', $points);
        $gamification->increment('total_points', $points);

        $newScore = $gamification->fresh()->score;
        $newLevel = (int) floor($newScore / 100) + 1;
        if ($newLevel > $gamification->level) {
            $gamification->update(['level' => $newLevel]);
        }
    }

    public function spendPoints(User $user, int $points): bool
    {
        $gamification = $user->gamification;
        if (!$gamification || $gamification->score < $points) {
            return false;
        }

        $gamification->decrement('score', $points);
        // total_points is lifetime total — never decremented

        return true;
    }

    public function updateStreak(User $user): void
    {
        $gamification = $user->gamification;
        if (!$gamification) {
            return;
        }

        $lastAct = $gamification->last_act_at;

        if ($lastAct === null || $lastAct->isYesterday()) {
            // Normal increment
            $gamification->increment('streak_count');
        } elseif ($lastAct->isToday()) {
            // Already counted today — no change needed
            return;
        } else {
            // Missed one or more days
            if ($gamification->streak_savers > 0) {
                // Consume a streak saver and preserve streak
                $gamification->decrement('streak_savers');
                $remainingSavers = $gamification->fresh()->streak_savers;
                session()->flash('streak_saved', [
                    'streak'      => $gamification->streak_count,
                    'savers_left' => $remainingSavers,
                ]);
                // Update last_act_at without overwriting other fields
                $gamification->update(['last_act_at' => now()]);
                return;
            } else {
                // Reset streak
                $gamification->update(['streak_count' => 1, 'last_act_at' => now()]);
                return;
            }
        }

        // Refresh to get the incremented streak_count from DB
        $gamification->refresh();

        // Update last_act_at
        $gamification->last_act_at = now();

        // Update longest streak if exceeded
        if ($gamification->streak_count > ($gamification->longest_streak ?? 0)) {
            $gamification->longest_streak = $gamification->streak_count;
        }

        $gamification->save();

        // Check for milestone bonus
        $bonus = $this->checkStreakMilestone($user);
        if ($bonus !== null) {
            $this->awardPoints($user, 'streak_milestone', $bonus);
            session()->flash('streak_milestone', [
                'days'  => $gamification->streak_count,
                'bonus' => $bonus,
            ]);
        }
    }

    public function checkStreakMilestone(User $user): ?int
    {
        $gamification = $user->gamification;
        if (!$gamification || $gamification->streak_count === 0) {
            return null;
        }

        $count = $gamification->streak_count;

        if ($count % 30 === 0) {
            return 200;
        }

        if ($count % 7 === 0) {
            return 50;
        }

        return null;
    }
}
