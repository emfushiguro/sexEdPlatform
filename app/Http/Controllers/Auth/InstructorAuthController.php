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
            
            // Check if user has instructor panel permission.
            if ($user->can('access instructor panel')) {
                $request->session()->regenerate();
                
                // Personalized success message
                $userName = $user->first_name ?? $user->name;
                return redirect()->intended(route('instructor.dashboard'))
                    ->with('success', "Welcome back, {$userName}!");
            }
            
            // If not instructor, logout and show error
            Auth::logout();
            return back()->withErrors([
                'email' => 'You do not have instructor access.',
            ]);
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->withInput($request->only('email'));
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
