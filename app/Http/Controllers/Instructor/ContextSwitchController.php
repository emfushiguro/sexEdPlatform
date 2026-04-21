<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContextSwitchController extends Controller
{
    public function toLearner(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->canSwitchToLearnerView(), 403);

        return redirect()->route('learner.dashboard');
    }
}
