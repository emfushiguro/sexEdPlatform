<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileCompletionRequest;
use App\Models\LearnerProfile;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Models\Barangay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileCompletionController extends Controller
{
    /**
     * Show the profile completion form.
     */
    public function show()
    {
        $user = Auth::user();

        // If profile already completed, redirect to dashboard
        if ($user->hasCompletedProfile()) {
            if ($user->hasRole('instructor')) {
                return redirect()->route('instructor.dashboard');
            }
            return redirect()->route('learner.dashboard');
        }

        $learnerProfile = $user->learnerProfile;
        
        // Get Cavite cities/municipalities only
        // Cavite province code: 402100000 (PSGC format)
        $cities = City::where('province_code', '402100000')
            ->orderBy('name')
            ->get();
        
        // If editing and has city, load barangays
        $barangays = [];
        if ($learnerProfile && $learnerProfile->city_code) {
            $barangays = Barangay::where('city_code', $learnerProfile->city_code)
                ->orderBy('name')
                ->get();
        }

        return view('profile.complete', compact('learnerProfile', 'cities', 'barangays'));
    }

    /**
     * Store the completed profile.
     * Note: Birthdate is already set during registration, so we don't collect it here.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Validate the request (removed birthdate and grade_level - using age brackets instead)
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('learner_profiles', 'username')->ignore($user->learnerProfile?->id),
            ],
            'gender' => 'nullable|in:male,female,prefer_not_to_say',
            'city_code' => 'required|string|exists:cities,code',
            'barangay_code' => 'required|string|exists:barangays,code',
            'bio' => 'nullable|string|max:500',
        ]);
        
        // Automatically set Cavite province code
        $validated['province_code'] = '402100000';
        
        // Copy birthdate from User model (stored during registration)
        $validated['birthdate'] = $user->birthdate;
        
        // Check if this is a parent account registration
        $isParentRegistration = session('is_parent_registration', false);
        if ($isParentRegistration) {
            $validated['is_parent_account'] = true;
            session()->forget('is_parent_registration');
        }
        
        // Get city and barangay names for display purposes
        $city = City::where('code', $validated['city_code'])->first();
        $barangay = Barangay::where('code', $validated['barangay_code'])->first();
        
        $validated['barangay'] = $barangay->name;

        // Create or update learner profile
        $learnerProfile = $user->learnerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        // If parent account, redirect to child account creation
        if ($learnerProfile->is_parent_account) {
            return redirect()->route('parent.create-child')
                ->with('success', 'Profile completed! Now create an account for your child.');
        }

        // Redirect based on user role
        if ($user->hasRole('instructor')) {
            return redirect()->route('instructor.dashboard')
                ->with('success', 'Profile completed successfully! Welcome to the instructor dashboard.');
        }

        return redirect()->route('learner.dashboard')
            ->with('success', 'Profile completed successfully! Welcome to Concious Connections.');
    }

    /**
     * Show the profile edit form for learners.
     */
    public function edit()
    {
        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;

        if (!$learnerProfile) {
            return redirect()->route('profile.complete');
        }

        return view('profile.learner-edit', compact('learnerProfile'));
    }

    /**
     * Update the learner profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;

        if (!$learnerProfile) {
            return redirect()->route('profile.complete');
        }

        // Validate (excluding grade_level and gender from editing)
        $validated = $request->validate([
            'username' => 'nullable|string|min:3|max:30|unique:learner_profiles,username,' . $learnerProfile->id,
            'school' => 'nullable|string|max:255',
            'about' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // 2MB max
        ]);

        // Handle username change with premium/free logic
        if ($request->filled('username') && $request->username !== $learnerProfile->username) {
            $isPremium = $user->isPremium();
            
            if (!$isPremium) {
                // Free users: Check 7-day limit
                if ($learnerProfile->username_changed_at) {
                    $daysSinceChange = now()->diffInDays($learnerProfile->username_changed_at);
                    if ($daysSinceChange < 7) {
                        $daysRemaining = 7 - $daysSinceChange;
                        $nextChangeDate = $learnerProfile->username_changed_at->addDays(7)->format('M d, Y');
                        return back()->with('error', "You can change your username again in {$daysRemaining} day(s) (on {$nextChangeDate}). Upgrade to Premium for unlimited changes!");
                    }
                }
            }
            
            // Update username and timestamp
            $validated['username_changed_at'] = now();
        } else {
            // Remove username from validated if not changing
            unset($validated['username']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($learnerProfile->avatar_path && \Storage::disk('public')->exists($learnerProfile->avatar_path)) {
                \Storage::disk('public')->delete($learnerProfile->avatar_path);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_path'] = $avatarPath;
        }

        $learnerProfile->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]+$/',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        $user = Auth::user();
        $user->password = bcrypt($request->password);
        $user->save();

        return back()->with('success', 'Password updated successfully!');
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();

        // Delete avatar if exists
        if ($user->learnerProfile && $user->learnerProfile->avatar_path) {
            \Storage::disk('public')->delete($user->learnerProfile->avatar_path);
        }

        // Logout and delete account
        Auth::logout();
        $user->delete();

        return redirect()->route('home')->with('success', 'Your account has been deleted.');
    }
}