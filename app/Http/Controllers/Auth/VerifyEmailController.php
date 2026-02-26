<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            // Check if profile is completed
            if (!$request->user()->hasCompletedProfile()) {
                return redirect()->route('profile.complete');
            }
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // After verification, redirect to profile completion (not dashboard)
        if (!$request->user()->hasCompletedProfile()) {
            return redirect()->route('profile.complete')
                ->with('success', 'Email verified! Please complete your profile to continue.');
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
