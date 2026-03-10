<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            if ($request->query('verified') == '1') {
                // Show success state with countdown that redirects to profile
                return view('auth.verify-email', ['showSuccess' => true]);
            }
            // Already verified with no context — send to appropriate destination
            return $request->user()->hasCompletedProfile()
                ? redirect()->route('learner.dashboard')
                : redirect()->route('profile.complete');
        }

        return view('auth.verify-email', ['showSuccess' => false]);
    }
}
