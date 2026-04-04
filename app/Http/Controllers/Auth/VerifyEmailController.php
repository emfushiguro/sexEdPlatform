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
        $user = User::findOrFail((int) $request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403);
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
            return redirect(route('verification.notice') . '?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Show the verify-email page with success state + countdown → profile
        return redirect(route('verification.notice') . '?verified=1');
    }
}
