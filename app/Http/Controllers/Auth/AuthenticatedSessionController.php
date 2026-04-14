<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
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
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Prevent admin users from logging in through learner login
        if (Auth::user()->can('access admin panel')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return back()->withErrors([
                'email' => 'Admin accounts must use the administrator login portal.'
            ]);
        }

        $request->session()->regenerate();

        // Add success message
        $userName = Auth::user()->first_name ?? Auth::user()->name;

        // Role-based redirect after login
        $user = Auth::user();

        if ($user->can('access instructor panel')) {
            return redirect()->intended(route('instructor.dashboard'))
                ->with('success', "Welcome back, {$userName}!");
        }

        // Learner (default)
        return redirect()->intended(route('learner.dashboard'))
            ->with('success', "Welcome back, {$userName}!");
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
