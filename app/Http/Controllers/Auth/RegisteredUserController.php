<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountInfoRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Support\AccountEmail;
use App\Support\LearnerAgePolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Step 1: Show personal information form.
     */
    public function create(): View
    {
        // Clear parent-flow contamination only. Do NOT clear pending_personal_info:
        // it is preserved so the user can navigate back from step 2 and see their data.
        session()->forget(['is_parent_registration', 'pending_parent_info']);

        return view('auth.register', [
            'personalInfo' => session('pending_personal_info', []),
        ]);
    }

    /**
     * Step 1 POST: Validate personal info, detect age, branch to correct flow.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $classification = LearnerAgePolicy::classify($validated['birthdate']);

        if ($classification === LearnerAgePolicy::ELIGIBLE_TEENAGER) {
            session(['pending_minor_registration' => Arr::only($validated, ['first_name', 'last_name', 'birthdate'])]);

            return redirect()->route('minor.registration.create');
        }

        if ($classification === LearnerAgePolicy::ELIGIBLE_ADULT) {
            session()->forget('pending_minor_registration');
            session(['pending_personal_info' => array_merge($validated, [
                'age' => LearnerAgePolicy::age($validated['birthdate']),
            ])]);

            return redirect()->route('register.account');
        }

        return back()->withErrors(['birthdate' => 'Learner registration is available for ages 10 through 100.']);
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
    public function storeAccount(AccountInfoRequest $request, LearnerIdentityRequirement $requirement): RedirectResponse
    {
        $personal = session('pending_personal_info');

        if (! $personal) {
            return redirect()->route('register');
        }

        if (LearnerAgePolicy::classify($personal['birthdate'] ?? null) !== LearnerAgePolicy::ELIGIBLE_ADULT) {
            session()->forget('pending_personal_info');

            session(['pending_minor_registration' => Arr::only($personal, ['first_name', 'last_name', 'birthdate'])]);

            return redirect()->route('minor.registration.create');
        }

        $account = $request->validated();

        try {
            $user = DB::transaction(function () use ($personal, $account, $requirement): User {
                $user = User::create([
                    'name' => trim($personal['first_name'].' '.$personal['last_name']),
                    'first_name' => $personal['first_name'],
                    'middle_initial' => $personal['middle_initial'] ?? null,
                    'last_name' => $personal['last_name'],
                    'suffix' => $personal['suffix'] ?? null,
                    'email' => $account['email'],
                    'birthdate' => $personal['birthdate'],
                    'age' => LearnerAgePolicy::age($personal['birthdate']),
                    'password' => Hash::make($account['password']),
                ]);

                Role::findOrCreate('learner', 'web');
                $user->assignRole('learner');
                $requirement->createForNewLearner($user);

                return $user;
            });
        } catch (QueryException $exception) {
            if (AccountEmail::isUsersEmailUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'email' => 'This email address is already registered.',
                ]);
            }

            throw $exception;
        }

        $verificationDispatchFailed = false;

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            $verificationDispatchFailed = true;

            Log::warning('Verification email dispatch failed during registration.', [
                'user_id' => $user->id,
                'exception' => $e::class,
            ]);
        }

        session()->forget('pending_personal_info');

        Auth::login($user);

        if ($verificationDispatchFailed) {
            return redirect()->route('verification.notice')
                ->with('warning', 'Account created, but we could not send the verification email yet. Please click "Resend verification email".');
        }

        return redirect()->route('verification.notice')
            ->with('success', 'Registration successful! Please verify your email address.');
    }
}
