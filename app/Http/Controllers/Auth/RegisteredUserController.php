<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountInfoRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Step 1: Show personal information form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Step 1 POST: Validate personal info, detect age, branch to correct flow.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $birthdate = Carbon::parse($validated['birthdate']);
        $age = $birthdate->age;

        if ($age < 13) {
            session([
                'pending_child_registration'   => $validated,
                'child_registration_timestamp' => now()->timestamp,
                'is_parent_registration'       => true,
            ]);

            return redirect()->route('parent.registration.required')
                ->with('info', 'Children under 13 require a parent or guardian to create their account.');
        }

        // Store personal info in session and advance to account info step
        session(['pending_personal_info' => array_merge($validated, ['age' => $age])]);

        return redirect()->route('register.account');
    }

    /**
     * Step 2: Show account information form (email + password).
     */
    public function showAccount(): View|RedirectResponse
    {
        if (! session('pending_personal_info')) {
            return redirect()->route('register');
        }

        return view('auth.register-account');
    }

    /**
     * Step 2 POST: Create the user account from session + submitted data.
     */
    public function storeAccount(AccountInfoRequest $request): RedirectResponse
    {
        $personal = session('pending_personal_info');

        if (! $personal) {
            return redirect()->route('register');
        }

        $account = $request->validated();

        $user = User::create([
            'name'           => trim($personal['first_name'] . ' ' . $personal['last_name']),
            'first_name'     => $personal['first_name'],
            'middle_initial' => $personal['middle_initial'] ?? null,
            'last_name'      => $personal['last_name'],
            'suffix'         => $personal['suffix'] ?? null,
            'email'          => $account['email'],
            'birthdate'      => $personal['birthdate'],
            'age'            => $personal['age'],
            'password'       => Hash::make($account['password']),
        ]);

        $user->assignRole('learner');

        event(new Registered($user));

        session()->forget('pending_personal_info');

        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', 'Registration successful! Please verify your email address.');
    }
}

