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
     * Show the parent registration form (Step 1 — personal info)
     */
    public function create(): View
    {
        return view('auth.parent-register');
    }

    /**
     * Store personal info in session and redirect to step 2 (credentials)
     */
    public function storePersonal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'   => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z.\s]+$/'],
            'last_name'    => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'suffix'       => ['nullable', 'string', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate'    => [
                'required',
                'date',
                'before:' . now()->subYears(18)->format('Y-m-d'),
            ],
        ]);

        session(['pending_parent_info' => $validated]);

        return redirect()->route('parent.register.account');
    }

    /**
     * Show step 2 — account credentials
     */
    public function createAccount(): View
    {
        if (!session('pending_parent_info')) {
            return redirect()->route('parent.register');
        }

        return view('auth.parent-register-account');
    }

    /**
     * Create the parent account from session + credentials
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $personalInfo = session('pending_parent_info');

        if (!$personalInfo) {
            return redirect()->route('parent.register')
                ->with('error', 'Session expired. Please start over.');
        }

        $validated = $request->validate([
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

        $birthdate = Carbon::parse($personalInfo['birthdate']);

        if ($birthdate->age < 18) {
            session()->forget('pending_parent_info');
            return redirect()->route('parent.register')
                ->with('error', 'You must be at least 18 years old to register as a parent.');
        }

        $parent = User::create([
            'name'           => trim($personalInfo['first_name'] . ' ' . $personalInfo['last_name']),
            'first_name'     => $personalInfo['first_name'],
            'middle_initial' => $personalInfo['middle_initial'] ?? null,
            'last_name'      => $personalInfo['last_name'],
            'suffix'         => $personalInfo['suffix'] ?? null,
            'email'          => strtolower($validated['email']),
            'birthdate'      => $personalInfo['birthdate'],
            'age'            => $birthdate->age,
            'password'       => Hash::make($validated['password']),
        ]);

        $parent->assignRole('learner');

        event(new Registered($parent));

        session()->forget('pending_parent_info');
        session(['is_parent_registration' => true]);

        Auth::login($parent);

        return redirect()->route('verification.notice')
            ->with('success', 'Parent account created! Please verify your email before creating a child account.');
    }

    /**
     * Handle parent registration request (legacy — kept for backward compat)
     */
    public function store(Request $request): RedirectResponse
    {
        return $this->storePersonal($request);
    }

    /**
     * Step 1: Show create child form (personal info)
     */
    public function createChildForm(): View|RedirectResponse
    {
        if (!auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Please verify your email first.');
        }

        if (!auth()->user()->canBeParent()) {
            abort(403, 'You must be 18 or older to create a child account.');
        }

        return view('auth.create-child-account');
    }

    /**
     * Step 1 POST: Save child personal info to session
     */
    public function storeChildInfo(Request $request): RedirectResponse
    {
        if (!auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (!auth()->user()->canBeParent()) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name'    => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial'=> ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z.\s]+$/'],
            'last_name'     => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'suffix'        => ['nullable', 'string', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate'     => [
                'required',
                'date',
                'before:today',
                'after:' . now()->subYears(18)->format('Y-m-d'),
            ],
            'gender'        => ['required', 'in:male,female,prefer_not_to_say'],
        ]);

        $birthdate = Carbon::parse($validated['birthdate']);
        if ($birthdate->age >= 18) {
            return back()->withErrors([
                'birthdate' => 'Child must be under 18 years old.'
            ])->withInput();
        }

        session(['child_step1' => array_merge($validated, ['age' => $birthdate->age])]);

        return redirect()->route('parent.create-child.location');
    }

    /**
     * Step 2: Show location form
     */
    public function childLocationForm(): View|RedirectResponse
    {
        if (!session('child_step1')) {
            return redirect()->route('parent.create-child');
        }

        $cities = \Schoolees\Psgc\Models\City::where('province_code', '402100000')
            ->orderBy('name')->get();

        return view('auth.child.step2-location', compact('cities'));
    }

    /**
     * Step 2 POST: Save location to session
     */
    public function storeChildLocation(Request $request): RedirectResponse
    {
        if (!session('child_step1')) {
            return redirect()->route('parent.create-child');
        }

        $validated = $request->validate([
            'city_code'     => ['required', 'string', 'exists:cities,code'],
            'barangay_code' => ['required', 'string', 'exists:barangays,code'],
        ]);

        session(['child_step2' => $validated]);

        return redirect()->route('parent.create-child.credentials');
    }

    /**
     * Step 3: Show credentials form
     */
    public function childCredentialsForm(): View|RedirectResponse
    {
        if (!session('child_step1') || !session('child_step2')) {
            return redirect()->route('parent.create-child');
        }

        $step1 = session('child_step1');
        $parentEmail = auth()->user()->email;
        $suggestedEmail = null;
        if (preg_match('/^(.+)@gmail\.com$/i', $parentEmail, $matches)) {
            $childFirstName = strtolower(preg_replace('/[^a-z0-9]/', '', $step1['first_name'] ?? ''));
            if ($childFirstName) {
                $suggestedEmail = $matches[1] . '+' . $childFirstName . '@gmail.com';
            }
        }

        return view('auth.child.step3-credentials', compact('step1', 'suggestedEmail'));
    }

    /**
     * Step 3 POST: Create the child account
     */
    public function storeChildCredentials(Request $request): RedirectResponse
    {
        $step1 = session('child_step1');
        $step2 = session('child_step2');

        if (!$step1 || !$step2) {
            return redirect()->route('parent.create-child');
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'unique:learner_profiles,username', 'regex:/^[a-z0-9_-]+$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $parent = auth()->user();
        $parentEmail = $parent->email;
        $childEmail = $validated['username'] . '@child.sexed-platform.local';

        if (preg_match('/^(.+)@gmail\.com$/i', $parentEmail, $matches)) {
            $childEmail = $matches[1] . '+' . $validated['username'] . '@gmail.com';
        }

        $barangay = \Schoolees\Psgc\Models\Barangay::where('code', $step2['barangay_code'])->first();

        $child = User::create([
            'name'           => trim($step1['first_name'] . ' ' . $step1['last_name']),
            'first_name'     => $step1['first_name'],
            'middle_initial' => $step1['middle_initial'] ?? null,
            'last_name'      => $step1['last_name'],
            'suffix'         => $step1['suffix'] ?? null,
            'email'          => $childEmail,
            'birthdate'      => $step1['birthdate'],
            'age'            => $step1['age'],
            'password'       => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $child->assignRole('learner');

        $child->learnerProfile()->create([
            'username'                 => $validated['username'],
            'birthdate'                => $child->birthdate,
            'gender'                   => $step1['gender'],
            'city_code'                => $step2['city_code'],
            'barangay_code'            => $step2['barangay_code'],
            'barangay'                 => $barangay->name,
            'province_code'            => '402100000',
            'requires_parental_consent'=> true,
        ]);

        ParentChildAccount::create([
            'parent_user_id'          => $parent->id,
            'child_user_id'           => $child->id,
            'can_view_progress'       => true,
            'can_view_quiz_answers'   => true,
            'can_approve_content'     => true,
            'relationship_verified_at'=> now(),
        ]);

        session()->forget(['child_step1', 'child_step2']);
        session(['child_created_name' => $step1['first_name']]);

        return redirect()->route('parent.create-child.done');
    }

    /**
     * Step 4: Done page (monitoring info)
     */
    public function childDone(): View
    {
        $childName = session('child_created_name', 'your child');
        session()->forget('child_created_name');

        return view('auth.child.done', compact('childName'));
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
