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

        if (!$gamificationRecord || $gamificationRecord->streak_savers >= 3) {
            return back()->with('error', 'You already have the maximum number of streak savers (3).');
        }

        if (!$gamification->spendPoints($user, 75)) {
            return back()->with('error', 'Not enough points to buy a streak saver.');
        }

        $gamificationRecord->increment('streak_savers');

        $newCount = $gamificationRecord->fresh()->streak_savers;

        return back()->with('success', "Streak Saver purchased! You now have {$newCount} saver(s).");
    }
}
