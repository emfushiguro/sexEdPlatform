<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InstructorAuthController extends Controller
{
    /**
     * Show the instructor login form.
     */
    public function showLoginForm()
    {
        return view('auth.instructor-login');
    }

    /**
     * Handle instructor login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $user = Auth::user();
            
            // Check if user has instructor role
            if ($user->hasRole('instructor')) {
                $request->session()->regenerate();
                return redirect()->intended(route('instructor.dashboard'));
            }
            
            // If not instructor, logout and show error
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'You do not have instructor access.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Logout the instructor.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('instructor.login');
    }
}
