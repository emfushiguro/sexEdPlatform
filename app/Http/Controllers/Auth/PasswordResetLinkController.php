<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AccountEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => AccountEmail::normalize($request->input('email'))]);
        }

        $request->validate([
            'email' => ['required', 'email:rfc'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $exception) {
            Log::warning('Password reset email dispatch failed.', [
                'exception' => $exception::class,
            ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'If an account exists for that email address, a password reset link has been sent.');
    }
}
