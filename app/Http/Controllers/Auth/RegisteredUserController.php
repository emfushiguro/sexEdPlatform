<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Calculate age from birthdate
        $birthdate = Carbon::parse($validated['birthdate']);
        $age = $birthdate->age;

        // Check if user is under 13 (requires parental consent)
        if ($age < 13) {
            // Store child data in persistent session (not flash) so it survives through parent registration and email verification
            session([
                'pending_child_registration' => $validated,
                'child_registration_timestamp' => now()->timestamp,
            ]);
            
            return redirect()->route('parent.registration.required')
                ->with('info', 'Children under 13 require a parent or guardian to create their account.');
        }

        // Create user account (13+ only reaches here)
        $user = User::create([
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'first_name' => $validated['first_name'],
            'middle_initial' => $validated['middle_initial'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'email' => strtolower($validated['email']),
            'birthdate' => $validated['birthdate'],
            'age' => $age,
            'password' => Hash::make($validated['password']),
        ]);

        // Assign learner role by default
        $user->assignRole('learner');

        // Fire registered event (triggers email verification)
        event(new Registered($user));

        // Log the user in
        Auth::login($user);

        // Redirect to email verification notice
        return redirect()->route('verification.notice')
            ->with('success', 'Registration successful! Please verify your email address.');
    }
}
