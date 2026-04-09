<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the email address as verified from a signed verification URL.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            $message = 'This verification link is invalid or expired. Please request a new verification email.';

            if ($request->user()) {
                return redirect()->route('verification.notice')->with('verification_error', $message);
            }

            return redirect()->route('login')->with('verification_error', $message);
        }

        $user = User::find((int) $request->route('id'));

        if (! $user) {
            return redirect()->route('login')->with('verification_error', 'The account for this verification link no longer exists.');
        }

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->with('verification_error', 'This verification link is invalid. Please request a new one.');
        }

        $currentUser = $request->user();
        if ($currentUser && $currentUser->getAuthIdentifier() !== $user->getAuthIdentifier()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (! $request->user()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('verified', true);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Show the verify-email page with success state + countdown -> profile
        return redirect()->route('verification.notice')->with('verified', true);
    }
}
