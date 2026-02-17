<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ParentChildAccount;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

class ParentRegistrationController extends Controller
{
    /**
     * Show the parent registration required page
     */
    public function requiredPage(): View
    {
        return view('auth.parent-registration-required');
    }

    /**
     * Show the parent registration form
     */
    public function create(): View
    {
        return view('auth.parent-register');
    }

    /**
     * Handle parent registration request
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z.\s]+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'suffix' => ['nullable', 'string', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate' => [
                'required',
                'date',
                'before:' . now()->subYears(18)->format('Y-m-d'), // Must be 18+
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
                'ends_with:@gmail.com',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        // Calculate age
        $birthdate = Carbon::parse($validated['birthdate']);
        $age = $birthdate->age;

        // Double-check age is 18+
        if ($age < 18) {
            return back()->withErrors([
                'birthdate' => 'You must be at least 18 years old to register as a parent.'
            ])->withInput();
        }

        // Create parent account
        $parent = User::create([
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

        // Assign learner role (parent is also a learner who can take courses)
        $parent->assignRole('learner');

        // Fire registered event (triggers email verification)
        event(new Registered($parent));

        // Store session flag to identify parent account during profile completion
        session(['is_parent_registration' => true]);

        // Log the parent in
        Auth::login($parent);

        // Redirect to email verification notice
        return redirect()->route('verification.notice')
            ->with('success', 'Parent account created! Please verify your email before creating a child account.');
    }

    /**
     * Show create child account form (only for verified parents)
     */
    public function createChildForm(): View
    {
        // Ensure user is verified
        if (!auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Please verify your email first.');
        }

        // Ensure user is 18+
        if (!auth()->user()->canBeParent()) {
            abort(403, 'You must be 18 or older to create a child account.');
        }

        // Get parent's profile for location auto-fill
        $parentProfile = auth()->user()->learnerProfile;
        
        // Get Cavite cities for dropdown
        $cities = \Schoolees\Psgc\Models\City::where('province_code', '402100000')
            ->orderBy('name')
            ->get();
        
        // Get barangays for parent's city
        $barangays = [];
        if ($parentProfile && $parentProfile->city_code) {
            $barangays = \Schoolees\Psgc\Models\Barangay::where('city_code', $parentProfile->city_code)
                ->orderBy('name')
                ->get();
        }

        return view('auth.create-child-account', compact('parentProfile', 'cities', 'barangays'));
    }

    /**
     * Create child account
     */
    public function storeChild(Request $request): RedirectResponse
    {
        // Ensure parent is verified
        if (!auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Please verify your email first.');
        }

        // Ensure user can be parent
        if (!auth()->user()->canBeParent()) {
            abort(403, 'You must be 18 or older to create a child account.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z.\s]+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'suffix' => ['nullable', 'string', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate' => [
                'required',
                'date',
                'before:today',
                'after:' . now()->subYears(18)->format('Y-m-d'), // Must be under 18
            ],
            'username' => ['required', 'string', 'min:3', 'max:30', 'unique:learner_profiles,username', 'regex:/^[a-z0-9_-]+$/'],
            'gender' => ['required', 'in:male,female,prefer_not_to_say'],
            'city_code' => ['required', 'string', 'exists:cities,code'],
            'barangay_code' => ['required', 'string', 'exists:barangays,code'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ]);

        // Calculate age
        $birthdate = Carbon::parse($validated['birthdate']);
        $age = $birthdate->age;

        // Ensure child is under 18
        if ($age >= 18) {
            return back()->withErrors([
                'birthdate' => 'Child must be under 18 years old. For 18+, please use regular registration.'
            ])->withInput();
        }

        // Get parent's location for child to inherit (same household)
        $parent = auth()->user();
        $parentProfile = $parent->learnerProfile;
        
        // Get barangay name
        $barangay = \Schoolees\Psgc\Models\Barangay::where('code', $validated['barangay_code'])->first();
        
        // Create child account
        $child = User::create([
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
            'first_name' => $validated['first_name'],
            'middle_initial' => $validated['middle_initial'] ?? null,
            'last_name' => $validated['last_name'],
            'suffix' => $validated['suffix'] ?? null,
            'email' => $validated['username'] . '@child.sexed-platform.local',
            'birthdate' => $validated['birthdate'],
            'age' => $age,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(), // Auto-verify child accounts
        ]);

        // Assign learner role
        $child->assignRole('learner');

        // Create COMPLETE learner profile (no profile completion needed)
        $child->learnerProfile()->create([
            'username' => $validated['username'],
            'birthdate' => $child->birthdate,
            'gender' => $validated['gender'],
            'city_code' => $validated['city_code'],
            'barangay_code' => $validated['barangay_code'],
            'barangay' => $barangay->name,
            'province_code' => '402100000', // Cavite
            'requires_parental_consent' => true,
        ]);

        // Create parent-child relationship (monitoring always enabled for safety)
        ParentChildAccount::create([
            'parent_user_id' => auth()->id(),
            'child_user_id' => $child->id,
            'can_view_progress' => true, // Always ON for COPPA compliance
            'can_view_quiz_answers' => true, // Always ON for safety monitoring
            'can_approve_content' => false, // Future feature
            'relationship_verified_at' => now(),
        ]);

        return redirect()->route('parent.children.index')
            ->with('success', "Child account created successfully! Username: {$validated['username']} | Password: (as you set) | Your child can now log in and start learning!");
    }

    /**
     * Show parent's children list
     */
    public function childrenIndex(): View
    {
        $children = auth()->user()->children()
            ->with('learnerProfile')
            ->get();

        return view('parent.children.index', compact('children'));
    }
}
