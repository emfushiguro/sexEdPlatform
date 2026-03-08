<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\UserDailyShield;

class GamificationController extends Controller
{
    public function rules()
    {
        $user = auth()->user();
        $gamification = $user->gamification;
        $shieldsRemaining = UserDailyShield::getShields($user);

        return view('learner.gamification.rules', compact('gamification', 'shieldsRemaining'));
    }
}
