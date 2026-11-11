<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminEmailVerificationController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole('admin'), 403);
        abort_unless(hash_equals(
            (string) $request->route('hash'),
            sha1($user->getEmailForVerification()),
        ), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->route('admin.login')->with(
            'status',
            'Email verified. Sign in to continue with two-step verification.',
        );
    }
}
