<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AdminMfaService;
use App\Support\AuthenticatedHomeRouteResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        AuthenticatedHomeRouteResolver $homeRouteResolver,
        AdminMfaService $mfa,
    ): RedirectResponse {
        $challenge = $mfa->beginIfAdmin(
            $request,
            (string) $request->input('email'),
            (string) $request->input('password'),
        );

        if ($challenge !== null) {
            return redirect()->route('admin.mfa.show');
        }

        $request->authenticate();

        // Prevent admin users from logging in through learner login
        if (Auth::user()->can('access admin panel')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Admin accounts must use the administrator login portal.',
            ]);
        }

        $request->session()->regenerate();

        // Add success message
        $userName = Auth::user()->first_name ?? Auth::user()->name;

        // Role-based redirect after login
        $user = Auth::user();

        if (! $user->hasVerifiedEmail() && $user->parentChildLink()
            ->whereNotNull('verification_document_path')->where('verification_document_path', '!=', '')->exists()) {
            $user->markEmailAsVerified();
        }

        return redirect()->intended(route($homeRouteResolver->routeName($user)))
            ->with('success', "Welcome back, {$userName}!");
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, AdminMfaService $mfa): RedirectResponse
    {
        $mfa->logout($request);

        return redirect('/');
    }
}
