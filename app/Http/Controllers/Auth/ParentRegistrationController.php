<?php

namespace App\Http\Controllers\Auth;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RemoveTempUploadRequest;
use App\Http\Requests\Auth\UploadChildTempDocumentRequest;
use App\Http\Requests\Auth\UploadParentTempDocumentRequest;
use App\Notifications\Admin\ChildVerificationRequestSubmittedNotification;
use App\Notifications\Admin\ParentVerificationRequestSubmittedNotification;
use App\Models\User;
use App\Models\ParentChildAccount;
use App\Services\Auth\RegistrationTempUploadService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

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
        $tempUpload = app(RegistrationTempUploadService::class)->get('parent', 'government_id');
        if (is_array($tempUpload) && !empty($tempUpload['path'])) {
            $tempUpload['preview_url'] = asset('storage/'.$tempUpload['path']);
        }

        return view('auth.parent-register', [
            'parentInfo' => session('pending_parent_info', []),
            'hasGovernmentIdUpload' => !empty($tempUpload['path']) || !empty(session('pending_parent_info.government_id_path')),
            'tempGovernmentIdUpload' => $tempUpload,
        ]);
    }

    public function uploadParentTempDocument(
        UploadParentTempDocumentRequest $request,
        RegistrationTempUploadService $tempUploadService
    ): JsonResponse {
        $upload = $tempUploadService->store('parent', 'government_id', $request->file('government_id'));
        $upload['preview_url'] = asset('storage/'.$upload['path']);

        return response()->json([
            'message' => 'Temporary upload saved.',
            'upload' => $upload,
        ]);
    }

    public function removeParentTempDocument(
        RemoveTempUploadRequest $request,
        RegistrationTempUploadService $tempUploadService
    ): JsonResponse {
        $tempUploadService->remove('parent', 'government_id');

        $pendingInfo = session('pending_parent_info', []);
        if (is_array($pendingInfo) && array_key_exists('government_id_path', $pendingInfo)) {
            unset($pendingInfo['government_id_path']);
            session(['pending_parent_info' => $pendingInfo]);
        }

        return response()->json([
            'message' => 'Temporary upload removed.',
        ]);
    }

    public function uploadChildTempDocument(
        UploadChildTempDocumentRequest $request,
        RegistrationTempUploadService $tempUploadService
    ): JsonResponse {
        if ($errorResponse = $this->ensureApprovedParentForJson()) {
            return $errorResponse;
        }

        $upload = $tempUploadService->store('child', 'verification_document', $request->file('verification_document'));
        $upload['preview_url'] = asset('storage/'.$upload['path']);

        return response()->json([
            'message' => 'Temporary upload saved.',
            'upload' => $upload,
        ]);
    }

    public function removeChildTempDocument(
        RemoveTempUploadRequest $request,
        RegistrationTempUploadService $tempUploadService
    ): JsonResponse {
        if ($errorResponse = $this->ensureApprovedParentForJson()) {
            return $errorResponse;
        }

        $tempUploadService->remove('child', 'verification_document');

        return response()->json([
            'message' => 'Temporary upload removed.',
        ]);
    }

    /**
     * Store personal info in session and redirect to step 2 (credentials)
     */
    public function storePersonal(Request $request): RedirectResponse
    {
        $tempUploadService = app(RegistrationTempUploadService::class);
        $tempUpload = $tempUploadService->get('parent', 'government_id');
        $hasExistingGovernmentId = !empty(session('pending_parent_info.government_id_path')) || !empty($tempUpload['path']);

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
            'government_id' => [
                $hasExistingGovernmentId ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ]);

        $existingPath = session('pending_parent_info.government_id_path');

        if ($request->hasFile('government_id')) {
            $upload = $tempUploadService->store('parent', 'government_id', $request->file('government_id'));
            $validated['government_id_path'] = $upload['path'];
        } elseif (is_array($tempUpload) && !empty($tempUpload['path'])) {
            $validated['government_id_path'] = (string) $tempUpload['path'];
        } elseif ($existingPath) {
            $validated['government_id_path'] = $existingPath;
        }

        unset($validated['government_id']);

        session(['pending_parent_info' => $validated]);

        return redirect()->route('parent.register.account');
    }

    /**
     * Show step 2 — account credentials
     */
    public function createAccount(): View|RedirectResponse
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
        $tempUploadService = app(RegistrationTempUploadService::class);

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
            'is_parent_registration' => true,
            'parent_verification_status' => 'pending',
            'parent_id_document_path' => $personalInfo['government_id_path'] ?? null,
        ]);

        $finalizedPath = $tempUploadService->finalize('parent', 'government_id', 'parent-verifications/' . $parent->id, 'government-id');

        if ($finalizedPath !== null) {
            $parent->update(['parent_id_document_path' => $finalizedPath]);
        } elseif (!empty($personalInfo['government_id_path']) && Storage::disk('public')->exists($personalInfo['government_id_path'])) {
            $extension = pathinfo($personalInfo['government_id_path'], PATHINFO_EXTENSION);
            $finalPath = 'parent-verifications/' . $parent->id . '/government-id-' . now()->format('YmdHis') . '.' . $extension;
            Storage::disk('public')->move($personalInfo['government_id_path'], $finalPath);
            $parent->update(['parent_id_document_path' => $finalPath]);
        }

        $this->notifyAdminsSafely(new ParentVerificationRequestSubmittedNotification($parent));

        Role::findOrCreate('learner', 'web');
        $parent->assignRole('learner');

        $verificationDispatchFailed = false;

        try {
            event(new Registered($parent));
        } catch (\Throwable $e) {
            $verificationDispatchFailed = true;

            Log::warning('Verification email dispatch failed during parent registration.', [
                'user_id' => $parent->id,
                'email' => $parent->email,
                'error' => $e->getMessage(),
            ]);
        }

        session()->forget('pending_parent_info');
        session(['is_parent_registration' => true]);

        Auth::login($parent);

        if ($verificationDispatchFailed) {
            return redirect()->route('verification.notice')
                ->with('warning', 'Parent account submitted and pending admin review, but verification email could not be sent yet. Please click "Resend verification email".');
        }

        return redirect()->route('verification.notice')
            ->with('success', 'Parent account submitted! Please verify your email. After verification, your application will remain pending admin review until approved.');
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
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

        // Clear stale in-progress data from an abandoned session, but keep
        // pending_child_registration — it holds the original child's info for pre-fill
        session()->forget(['child_step1', 'child_step2', 'child_created_name']);

        return view('auth.create-child-account', [
            'pendingChild' => session('pending_child_registration', []),
        ]);
    }

    /**
     * Step 1 POST: Save child personal info to session
     */
    public function storeChildInfo(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
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
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

        if (!session('child_step1')) {
            return redirect()->route('parent.create-child');
        }

        $cities = \Schoolees\Psgc\Models\City::where('province_code', '402100000')
            ->orderBy('name')->get();

        $parentProfile  = auth()->user()->learnerProfile;
        $preFilledCity  = $parentProfile?->city_code;
        $preFilledBarangay = $parentProfile?->barangay_code;

        return view('auth.child.step2-location', compact('cities', 'preFilledCity', 'preFilledBarangay'));
    }

    /**
     * Step 2 POST: Save location to session
     */
    public function storeChildLocation(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

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
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

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

        $tempUpload = app(RegistrationTempUploadService::class)->get('child', 'verification_document');
        if (is_array($tempUpload) && !empty($tempUpload['path'])) {
            $tempUpload['preview_url'] = asset('storage/'.$tempUpload['path']);
        }

        return view('auth.child.step3-credentials', [
            'step1' => $step1,
            'suggestedEmail' => $suggestedEmail,
            'tempChildVerificationUpload' => $tempUpload,
            'hasChildVerificationUpload' => !empty($tempUpload['path']),
        ]);
    }

    /**
     * Step 3 POST: Create the child account
     */
    public function storeChildCredentials(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

        $step1 = session('child_step1');
        $step2 = session('child_step2');

        if (!$step1 || !$step2) {
            return redirect()->route('parent.create-child');
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'unique:learner_profiles,username', 'regex:/^[a-z0-9_-]+$/'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'verification_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $tempUploadService = app(RegistrationTempUploadService::class);

        if ($request->hasFile('verification_document')) {
            $tempUploadService->store('child', 'verification_document', $request->file('verification_document'));
        }

        $tempUpload = $tempUploadService->get('child', 'verification_document');
        if (!is_array($tempUpload) || empty($tempUpload['path'])) {
            return back()
                ->withErrors(['verification_document' => 'Please upload a PSA birth certificate before continuing.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $parent = auth()->user();
        $parentEmail = $parent->email;
        $childEmail = $validated['username'] . '@child.sexed-platform.local';

        if (preg_match('/^(.+)@gmail\.com$/i', $parentEmail, $matches)) {
            $childEmail = $matches[1] . '+' . $validated['username'] . '@gmail.com';
        }

        $barangay = \Schoolees\Psgc\Models\Barangay::where('code', $step2['barangay_code'])->first();
        $verificationDocumentPath = $tempUploadService->finalize(
            'child',
            'verification_document',
            'child-verifications/' . $parent->id,
            'verification-document'
        );

        if ($verificationDocumentPath === null) {
            return back()
                ->withErrors(['verification_document' => 'The uploaded verification document could not be processed. Please upload again.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

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

        Role::findOrCreate('learner', 'web');
        $child->assignRole('learner');

        $child->learnerProfile()->create([
            'username'                 => $validated['username'],
            'birthdate'                => $child->birthdate,
            'gender'                   => $step1['gender'],
            'city_code'                => $step2['city_code'],
            'barangay_code'            => $step2['barangay_code'],
            'barangay'                 => $barangay?->name,
            'province_code'            => '402100000',
            'requires_parental_consent'=> true,
        ]);

        $verification = ParentChildAccount::create([
            'parent_user_id'          => $parent->id,
            'child_user_id'           => $child->id,
            'can_view_progress'       => true,
            'can_view_quiz_answers'   => true,
            'can_approve_content'     => true,
            'verification_status'     => VerificationStatus::Pending->value,
            'verification_document_path' => $verificationDocumentPath,
            'relationship_verified_at'=> null,
        ]);

        $this->notifyAdminsSafely(new ChildVerificationRequestSubmittedNotification($parent, $child, $verification));

        session()->forget(['child_step1', 'child_step2', 'pending_child_registration']);
        session([
            'child_created_name' => $step1['first_name'],
            'child_registration_result' => [
                'status' => VerificationStatus::Pending->value,
            ],
        ]);

        return redirect()->route('parent.create-child.done');
    }

    /**
     * Step 4: Done page (monitoring info)
     */
    public function childDone(): View
    {
        $childName = session('child_created_name', 'your child');
        $registrationResult = session('child_registration_result', [
            'status' => VerificationStatus::Pending->value,
        ]);
        session()->forget(['child_created_name', 'child_registration_result']);

        return view('auth.child.done', [
            'childName' => $childName,
            'registrationResult' => $registrationResult,
        ]);
    }

    /**
     * Show parent's children list
     */
    public function childrenIndex(): View|RedirectResponse
    {
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

        $children = auth()->user()->children()
            ->with('learnerProfile')
            ->get();

        return view('parent.children.index', compact('children'));
    }

    public function verificationStatus(): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isParentRegistration()) {
            return redirect()->route('learner.dashboard');
        }

        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if ($user->isParentVerificationApproved() && !$user->hasCompletedProfile()) {
            return redirect()->route('profile.complete')
                ->with('success', 'Your parent verification is approved. Please complete your profile.');
        }

        return view('auth.parent-verification-status', [
            'user' => $user,
            'isApproved' => $user->isParentVerificationApproved(),
        ]);
    }

    public function childVerificationStatus(): View|RedirectResponse
    {
        $verification = ParentChildAccount::query()
            ->where('child_user_id', auth()->id())
            ->with('parent')
            ->first();

        if (!$verification) {
            return redirect()->route('learner.dashboard');
        }

        if ($verification->verification_status === 'approved') {
            return redirect()->route('learner.dashboard');
        }

        return view('auth.child-verification-status', [
            'verification' => $verification,
        ]);
    }

    private function notifyAdminsSafely(Notification $notification): void
    {
        try {
            User::query()
                ->role('admin')
                ->get()
                ->each(fn (User $admin) => $admin->notify($notification));
        } catch (\Throwable $exception) {
            Log::warning('Failed to send admin parent-child verification submission notification.', [
                'notification' => $notification::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function ensureApprovedParent(): ?RedirectResponse
    {
        $parent = auth()->user();

        if (! $parent->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Please verify your email first.');
        }

        if (! $parent->canBeParent()) {
            abort(403, 'You must be 18 or older to create a child account.');
        }

        if (! $parent->isParentRegistration() || ! $parent->isParentVerificationApproved()) {
            return redirect()->route('parent.verification.status')
                ->with('warning', 'Your parent account is still under admin review.');
        }

        if (! $parent->hasCompletedProfile()) {
            return redirect()->route('profile.complete')
                ->with('warning', 'Please complete your profile before creating a child account.');
        }

        return null;
    }

    private function ensureApprovedParentForJson(): ?JsonResponse
    {
        $parent = auth()->user();

        if (!$parent->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email first.',
            ], 403);
        }

        if (!$parent->canBeParent()) {
            return response()->json([
                'message' => 'You must be 18 or older to create a child account.',
            ], 403);
        }

        if (!$parent->isParentRegistration() || !$parent->isParentVerificationApproved()) {
            return response()->json([
                'message' => 'Parent verification is required before child registration uploads.',
            ], 403);
        }

        if (!$parent->hasCompletedProfile()) {
            return response()->json([
                'message' => 'Please complete your profile before creating a child account.',
            ], 403);
        }

        return null;
    }
}
