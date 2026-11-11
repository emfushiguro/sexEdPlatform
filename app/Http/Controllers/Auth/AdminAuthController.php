<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AdminMfaService;
use App\Support\AccountEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm()
    {
        return view('auth.admin-login');
    }

    /**
     * Handle admin login request.
     */
    public function login(Request $request, AdminMfaService $mfa): RedirectResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => AccountEmail::normalize($request->input('email'))]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
        ]);

        $mfa->beginFromAdminPortal(
            $request,
            (string) $validated['email'],
            (string) $validated['password'],
        );

        return redirect()->route('admin.mfa.show');
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request, AdminMfaService $mfa): RedirectResponse
    {
        $mfa->logout($request);

        return redirect()->route('admin.login');
    }
}
