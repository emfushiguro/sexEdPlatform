<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\UserDailyShield;
use App\Services\GamificationService;
use Illuminate\Http\Request;

class ShieldRefillController extends Controller
{
    public function store(Request $request, GamificationService $gamification)
    {
        $request->validate(['type' => 'required|in:single,full']);

        $user = auth()->user();
        $type = $request->input('type');
        $cost = $gamification->shieldRefillCost($type);

        if (!$gamification->spendPoints($user, $cost)) {
            return back()->with('error', 'Not enough points to refill shields.');
        }

        if ($type === 'full') {
            UserDailyShield::refillFull($user);
        } else {
            UserDailyShield::refillOne($user);
        }

        $remaining = UserDailyShield::getShields($user);
        session()->flash('shield_refilled', ['type' => $type, 'remaining' => $remaining]);

        $fullTarget = $gamification->shieldFullRefillTarget();

        return back()->with('success', $type === 'full'
            ? "Full shield refill! You're back to {$fullTarget} shields."
            : '+1 Shield restored.');
    }
}
