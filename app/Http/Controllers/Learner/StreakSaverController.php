<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Services\GamificationService;

class StreakSaverController extends Controller
{
    public function store(GamificationService $gamification)
    {
        $user = auth()->user();
        $gamificationRecord = $user->gamification;
        $maxSavers = $gamification->maxStreakSaversHeld();
        $saverCost = $gamification->streakSaverCost();

        if (!$gamificationRecord || $gamificationRecord->streak_savers >= $maxSavers) {
            return back()->with('error', "You already have the maximum number of streak savers ({$maxSavers}).");
        }

        if (!$gamification->spendPoints($user, $saverCost)) {
            return back()->with('error', 'Not enough points to buy a streak saver.');
        }

        $gamificationRecord->increment('streak_savers');

        $newCount = $gamificationRecord->fresh()->streak_savers;

        return back()->with('success', "Streak Saver purchased! You now have {$newCount} saver(s).");
    }
}
