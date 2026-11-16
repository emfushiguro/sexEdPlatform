<?php

namespace App\Http\Controllers\Auth;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RemoveTempUploadRequest;
use App\Http\Requests\Auth\ResubmitChildVerificationRequest;
use App\Http\Requests\Auth\StoreChildRelationshipVerificationRequest;
use App\Http\Requests\Auth\UploadChildTempDocumentRequest;
use App\Http\Requests\Auth\UploadParentTempDocumentRequest;
use App\Models\MinorRegistrationRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Auth\MinorAccountHandoffService;
use App\Services\Auth\RegistrationTempUploadService;
use App\Services\GuardianRelationshipEvidenceService;
use App\Services\GuardianRelationshipVerificationService;
use App\Services\ParentChildInvitationService;
use App\Services\ParentChildVerificationService;
use App\Support\AccountEmail;
use App\Support\GuardianRelationshipTypes;
use App\Support\LearnerAgePolicy;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Throwable;

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
        return view('auth.parent-register', [
            'parentInfo' => session('pending_parent_info', []),
            'hasGovernmentIdUpload' => false,
            'tempGovernmentIdUpload' => null,
        ]);
    }

    public function uploadParentTempDocument(
        UploadParentTempDocumentRequest $request,
        RegistrationTempUploadService $tempUploadService
    ): JsonResponse {
        $upload = $tempUploadService->store('parent', 'government_id', $request->file('government_id'));
        $upload['preview_url'] = route('registration.temp-document.preview', ['parent', 'government_id']);

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
        $upload['preview_url'] = route('registration.temp-document.preview', ['child', 'verification_document']);

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
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z.\s]+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'suffix' => ['nullable', 'string', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate' => [
                'required',
                'date',
                'before:'.now()->subYears(18)->format('Y-m-d'),
            ],
        ]);

        session(['pending_parent_info' => $validated]);

        return redirect()->route('parent.register.account');
    }

    /**
     * Show step 2 — account credentials
     */
    public function createAccount(): View|RedirectResponse
    {
        if (! session('pending_parent_info')) {
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
        if (! $personalInfo) {
            return redirect()->route('parent.register')
                ->with('error', 'Session expired. Please start over.');
        }

        if (is_string($request->input('email'))) {
            $request->merge(['email' => AccountEmail::normalize($request->input('email'))]);
        }

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:users,email',
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
                ->with('error', 'You must be at least 18 years old to register as a guardian.');
        }

        try {
            $parent = User::create([
                'name' => trim($personalInfo['first_name'].' '.$personalInfo['last_name']),
                'first_name' => $personalInfo['first_name'],
                'middle_initial' => $personalInfo['middle_initial'] ?? null,
                'last_name' => $personalInfo['last_name'],
                'suffix' => $personalInfo['suffix'] ?? null,
                'email' => $validated['email'],
                'birthdate' => $personalInfo['birthdate'],
                'age' => $birthdate->age,
                'password' => Hash::make($validated['password']),
                'is_parent_registration' => true,
            ]);
        } catch (QueryException $exception) {
            if (AccountEmail::isUsersEmailUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'email' => 'This email address is already registered.',
                ]);
            }

            throw $exception;
        }

        Role::findOrCreate('learner', 'web');
        $parent->assignRole('learner');

        $verificationDispatchFailed = false;

        try {
            event(new Registered($parent));
        } catch (\Throwable $e) {
            $verificationDispatchFailed = true;

            Log::warning('Verification email dispatch failed during guardian registration.', [
                'user_id' => $parent->id,
                'exception' => $e::class,
            ]);
        }

        session()->forget('pending_parent_info');
        session(['is_parent_registration' => true]);

        Auth::login($parent);

        if ($verificationDispatchFailed) {
            return redirect()->route('verification.notice')
                ->with('warning', 'Guardian account submitted and pending admin review, but verification email could not be sent yet. Please click "Resend verification email".');
        }

        return redirect()->route('verification.notice')
            ->with('success', 'Guardian account created! Please verify your email, then submit identity verification.');
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

        if (! session('pending_child_registration') && ($token = session('minor_registration_token'))) {
            return redirect()->route('minor.registration.accept', $token);
        }

        // Clear stale in-progress data from an abandoned session, but keep
        // pending_child_registration — it holds the original child's info for pre-fill
        session()->forget(['child_step1', 'child_step2', 'child_step3', 'child_created_name']);
        if (! session('pending_child_registration')) {
            session()->forget(['minor_registration_request_id', 'minor_registration_token']);
        }

        return view('auth.create-child-account', [
            'pendingChild' => session('pending_child_registration', []),
            'relationshipOptions' => GuardianRelationshipTypes::options(),
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
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z.\s]+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'suffix' => ['nullable', 'string', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate' => [
                'required',
                'date',
                'before_or_equal:'.now()->subYears(10)->toDateString(),
            ],
            'gender' => ['required', 'in:male,female,prefer_not_to_say'],
            'relationship_type' => ['required', Rule::in(GuardianRelationshipTypes::values())],
            'relationship_custom' => ['nullable', 'required_if:relationship_type,other', 'string', 'max:120'],
        ], ['birthdate.before_or_equal' => 'Dependents must be at least 10 years old.']);

        session(['child_step1' => $validated]);

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

        if (! session('child_step1')) {
            return redirect()->route('parent.create-child');
        }

        $cities = \Schoolees\Psgc\Models\City::where('province_code', '402100000')
            ->orderBy('name')->get();

        $parentProfile = Auth::user()?->learnerProfile;
        $preFilledCity = $parentProfile?->city_code;
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

        if (! session('child_step1')) {
            return redirect()->route('parent.create-child');
        }

        $validated = $request->validate([
            'city_code' => ['required', 'string', 'exists:cities,code'],
            'barangay_code' => [
                'required',
                'string',
                'exists:barangays,code',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $cityCode = (string) $request->input('city_code');
                    $barangayCode = (string) $value;

                    $isBarangayInCity = \Schoolees\Psgc\Models\Barangay::query()
                        ->where('code', $barangayCode)
                        ->where('city_code', $cityCode)
                        ->exists();

                    if (! $isBarangayInCity) {
                        $fail('Selected barangay does not belong to the selected city.');
                    }
                },
            ],
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

        if (! session('child_step1') || ! session('child_step2')) {
            return redirect()->route('parent.create-child');
        }

        return view('auth.child.step3-credentials', ['step1' => session('child_step1')]);
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

        if (! $step1 || ! $step2) {
            return redirect()->route('parent.create-child');
        }

        $validated = $request->validate([
            'username' => ['nullable', 'string', 'min:3', 'max:30', 'unique:learner_profiles,username', 'regex:/^[a-z0-9_-]+$/'],
            'email' => ['nullable', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
        ]);

        session(['child_step3' => [
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'] ?? null,
        ]]);

        return redirect()->route('parent.create-child.relationship-verification');
    }

    public function childValidationForm(): View|RedirectResponse
    {
        if (! session('child_step1') || ! session('child_step2') || ! session('child_step3')) {
            return redirect()->route('parent.create-child');
        }

        return redirect()->route('parent.create-child.relationship-verification');
    }

    public function storeChildValidation(Request $request): Response
    {
        return response('This validation step is no longer available. Please restart the Teenager registration.', 410);
    }

    public function childRelationshipVerificationForm(): View|RedirectResponse
    {
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

        $step1 = session('child_step1');
        if (! $step1 || ! session('child_step2') || ! session('child_step3')) {
            return redirect()->route('parent.create-child');
        }

        if (! in_array((string) ($step1['relationship_type'] ?? ''), GuardianRelationshipTypes::selectableValues(), true)) {
            return redirect()->route('parent.create-child')
                ->withErrors(['relationship_type' => 'Select a supported guardian relationship.']);
        }

        return view('auth.child.step5-relationship-verification', [
            'step1' => $step1,
            'relationshipDocumentTypes' => GuardianRelationshipTypes::documentTypeOptions($step1['relationship_type'] ?? null),
            'pathwayLabel' => GuardianRelationshipTypes::pathwayLabel($step1['relationship_type'] ?? null),
            'requiredDocumentTypes' => GuardianRelationshipTypes::requiredDocumentTypes($step1['relationship_type'] ?? null),
            'requiresCircumstances' => GuardianRelationshipTypes::requiresCircumstances($step1['relationship_type'] ?? null),
        ]);
    }

    public function storeChildRelationshipVerification(StoreChildRelationshipVerificationRequest $request): RedirectResponse
    {
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

        $step1 = session('child_step1');
        if (! $step1 || ! session('child_step2') || ! session('child_step3')) {
            return redirect()->route('parent.create-child');
        }

        abort_unless(in_array((string) ($step1['relationship_type'] ?? ''), GuardianRelationshipTypes::selectableValues(), true), 404);

        return $this->createChildAccountFromSession($request, [
            'documents' => $request->validated('documents'),
            'relationship_notes' => $request->validated('relationship_notes'),
        ]);
    }

    private function createChildAccountFromSession(
        Request $request,
        ?array $relationshipVerificationPayload = null,
    ): RedirectResponse {
        $step1 = session('child_step1');
        $step2 = session('child_step2');
        $step3 = session('child_step3');

        if (! $step1 || ! $step2 || ! $step3) {
            return redirect()->route('parent.create-child');
        }

        if (! in_array(LearnerAgePolicy::classify($step1['birthdate']), [LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::ELIGIBLE_ADULT], true)) {
            return redirect()->route('parent.create-child')->withErrors(['birthdate' => 'Dependents must be at least 10 years old.']);
        }

        $relationshipType = (string) ($step1['relationship_type'] ?? '');
        $requiresRelationshipVerification = in_array($relationshipType, GuardianRelationshipTypes::selectableValues(), true);

        $parent = Auth::user();
        $minorHandoffs = app(MinorAccountHandoffService::class);
        $barangay = \Schoolees\Psgc\Models\Barangay::query()
            ->where('code', $step2['barangay_code'])
            ->where('city_code', $step2['city_code'])
            ->first();

        if (! $barangay) {
            return redirect()->route('parent.create-child.location')
                ->withErrors(['barangay_code' => 'Selected barangay does not belong to the selected city.'])
                ->withInput();
        }
        $relationshipEvidencePaths = [];

        try {
            [$child, $verification] = DB::transaction(function () use (
                $parent,
                $minorHandoffs,
                $step1,
                $step2,
                $step3,
                $barangay,
                $relationshipVerificationPayload,
                $relationshipType,
                $requiresRelationshipVerification,
                &$relationshipEvidencePaths,
            ): array {
                $requestId = session('minor_registration_request_id');
                $requestToken = (string) session('minor_registration_token');
                $minorRequest = $requestId
                    ? MinorRegistrationRequest::query()->lockForUpdate()->findOrFail($requestId)
                    : null;
                $child = $minorRequest?->existing_child_user_id
                    ? User::query()->lockForUpdate()->findOrFail($minorRequest->existing_child_user_id)
                    : null;

                if ($minorRequest !== null) {
                    if ($minorRequest->status !== MinorRegistrationRequest::STATUS_PENDING
                        || $minorRequest->expires_at?->isPast()
                        || $requestToken === ''
                        || ! hash_equals($minorRequest->token_hash, $minorHandoffs->digestForStorage($requestToken))
                        || ! is_string($parent->email)
                        || $minorRequest->accepted_by_user_id !== $parent->id
                        || ! $parent->hasVerifiedEmail()
                        || ! $parent->isParentRegistration()
                        || ! $parent->isParentVerificationApproved()
                        || ! $parent->hasCompletedGuardianOnboarding()) {
                        throw ValidationException::withMessages(['email' => 'This registration request is no longer available.']);
                    }
                }

                if ($child !== null && Carbon::parse($step1['birthdate'])->toDateString() !== $child->birthdate?->toDateString()) {
                    throw ValidationException::withMessages([
                        'birthdate' => 'The dependent birthdate must match the existing account.',
                    ]);
                }
                if ($child === null) {
                    $child = User::query()->create([
                        'name' => trim($step1['first_name'].' '.$step1['last_name']),
                        'first_name' => $step1['first_name'],
                        'middle_initial' => $step1['middle_initial'] ?? null,
                        'last_name' => $step1['last_name'],
                        'suffix' => $step1['suffix'] ?? null,
                        'email' => $step3['email'] ?? null,
                        'birthdate' => $step1['birthdate'],
                        'status' => User::STATUS_ACTIVE,
                        'password' => Hash::make(Str::random(64)),
                        'minor_account_activation_required' => true,
                    ]);

                    Role::findOrCreate('learner', 'web');
                    $child->assignRole('learner');

                    $child->learnerProfile()->create([
                        'username' => $step3['username'] ?? null,
                        'birthdate' => $child->birthdate,
                        'gender' => $step1['gender'],
                        'city_code' => $step2['city_code'],
                        'barangay_code' => $step2['barangay_code'],
                        'barangay' => $barangay->name,
                        'province_code' => '402100000',
                        'requires_parental_consent' => $child->requiresGuardian(),
                    ]);
                }

                $child->refreshClassificationCache();

                if (ParentChildAccount::query()
                    ->where('parent_user_id', $parent->id)
                    ->where('child_user_id', $child->id)
                    ->whereIn('relationship_status', [ParentChildAccount::STATUS_PENDING, ParentChildAccount::STATUS_ACTIVE])
                    ->exists()) {
                    throw ValidationException::withMessages(['relationship_type' => 'This guardian relationship is already being processed.']);
                }

                $verification = ParentChildAccount::query()->create([
                    'parent_user_id' => $parent->id,
                    'child_user_id' => $child->id,
                    'can_view_progress' => LearnerAgePolicy::classify($step1['birthdate']) === LearnerAgePolicy::ELIGIBLE_TEENAGER,
                    'can_view_quiz_answers' => LearnerAgePolicy::classify($step1['birthdate']) === LearnerAgePolicy::ELIGIBLE_TEENAGER,
                    'can_approve_content' => false,
                    'can_manage_support_information' => LearnerAgePolicy::classify($step1['birthdate']) === LearnerAgePolicy::ELIGIBLE_TEENAGER,
                    'relationship_type' => $relationshipType,
                    'relationship_custom' => $relationshipType === GuardianRelationshipTypes::OTHER
                        ? ($step1['relationship_custom'] ?? null)
                        : null,
                    'verification_pathway' => GuardianRelationshipTypes::pathway($relationshipType),
                    'relationship_status' => ParentChildAccount::STATUS_PENDING,
                    'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
                    'current_evidence_round' => 0,
                    'relationship_notes' => $relationshipVerificationPayload['relationship_notes'] ?? null,
                    'is_legacy_relationship' => false,
                    'verification_status' => VerificationStatus::Pending->value,
                    'verification_document_path' => null,
                    'relationship_verified_at' => null,
                ]);

                if ($requiresRelationshipVerification) {
                    app(GuardianRelationshipVerificationService::class)->submit(
                        $verification,
                        $parent,
                        $relationshipVerificationPayload['documents'],
                        $relationshipVerificationPayload['relationship_notes'] ?? null,
                        $relationshipEvidencePaths,
                    );
                }

                if ($minorRequest !== null) {
                    $minorRequest->forceFill([
                        'status' => MinorRegistrationRequest::STATUS_COMPLETED,
                        'accepted_by_user_id' => $parent->id,
                        'accepted_at' => $minorRequest->accepted_at ?? now(),
                    ])->save();
                }

                return [$child, $verification->fresh()];
            });
        } catch (Throwable $exception) {
            app(GuardianRelationshipEvidenceService::class)->deleteStoredPaths($relationshipEvidencePaths);

            throw $exception;
        }

        session()->forget([
            'child_step1',
            'child_step2',
            'child_step3',
            'pending_child_registration',
            'minor_registration_request_id',
            'minor_registration_token',
        ]);
        session([
            DependentSupportRegistrationController::SESSION_KEY => [
                'dependent_user_id' => $child->id,
                'parent_child_account_id' => $verification->id,
                'expires_at' => now()->addMinutes(DependentSupportRegistrationController::MARKER_MINUTES)->timestamp,
            ],
            'child_created_name' => $step1['first_name'],
            'child_registration_result' => [
                'status' => VerificationStatus::Pending->value,
            ],
        ]);

        return redirect()->route('parent.create-child.support-information');
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
        $supportInformationResult = session('support_information_result');
        session()->forget([
            'child_created_name',
            'child_registration_result',
            'support_information_result',
            DependentSupportRegistrationController::SESSION_KEY,
        ]);

        return view('auth.child.done', [
            'childName' => $childName,
            'registrationResult' => $registrationResult,
            'supportInformationResult' => $supportInformationResult,
        ]);
    }

    /**
     * Show parent's children list
     */
    public function childrenIndex(ParentChildInvitationService $invitationService): View|RedirectResponse
    {
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

        $parent = Auth::user();
        if (! $parent instanceof User) {
            abort(403);
        }

        $children = $parent->children()
            ->with('learnerProfile')
            ->get();

        $pendingApprovalNotifications = $parent
            ->unreadNotifications()
            ->where('data->type', 'child_enrollment_approval_requested')
            ->latest()
            ->limit(5)
            ->get();

        $outgoingInvitations = $invitationService
            ->getOutgoingInvitations($parent)
            ->take(1)
            ->values();

        return view('parent.children.index', compact('children', 'pendingApprovalNotifications', 'outgoingInvitations'));
    }

    public function verificationStatus(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->isParentRegistration()) {
            return redirect()->route('learner.dashboard');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (! $user->parent_verification_status) {
            return redirect()->route('guardian.verification.create');
        }

        if ($user->isParentVerificationApproved() && ! $user->hasCompletedGuardianOnboarding()) {
            return redirect()->route('guardian.onboarding.show');
        }

        return view('auth.parent-verification-status', [
            'user' => $user,
            'isApproved' => $user->isParentVerificationApproved(),
        ]);
    }

    public function resubmitParentVerification(
        Request $request,
    ): RedirectResponse {
        $parent = $request->user();

        if (! $parent->isParentRegistration()) {
            return redirect()->route('learner.dashboard');
        }

        if (! $parent->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Please verify your email first.');
        }

        if (! $parent->isParentVerificationRejected()) {
            return redirect()->route('parent.verification.status')
                ->with('error', 'Only rejected guardian verification records can be resubmitted.');
        }

        return redirect()->route('guardian.verification.create')
            ->with('error', 'Please resubmit through the Guardian verification form.');
    }

    public function childVerificationStatus(): View|RedirectResponse
    {
        $user = Auth::user();
        $verification = ParentChildAccount::query()
            ->where('child_user_id', $user->id)
            ->whereNotNull('verification_document_path')
            ->latest('id')
            ->with('parent')
            ->first();

        if (! $verification) {
            return redirect()->route('learner.dashboard');
        }

        if ($verification->verification_status === 'approved') {
            return redirect()->route('learner.dashboard');
        }

        return view('auth.child-verification-status', [
            'verification' => $verification,
        ]);
    }

    public function resubmitChildVerification(
        ResubmitChildVerificationRequest $request,
        User $child,
        RegistrationTempUploadService $tempUploadService,
        ParentChildVerificationService $verificationService,
    ): RedirectResponse {
        if ($redirect = $this->ensureApprovedParent()) {
            return $redirect;
        }

        $parent = $request->user();

        $verification = ParentChildAccount::query()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->first();

        abort_if(! $verification, 403, 'You are not allowed to resubmit this child verification record.');

        if ($verification->verification_status !== VerificationStatus::Rejected->value) {
            return redirect()->route('parent.children.index')
                ->with('error', 'Only rejected child verification records can be resubmitted.');
        }

        $tempUploadService->store('child', 'verification_document', $request->file('verification_document'));
        $finalizedPath = $tempUploadService->finalize(
            'child',
            'verification_document',
            'child-verifications/'.$parent->id,
            'verification-document'
        );

        if ($finalizedPath === null) {
            return back()->withErrors([
                'verification_document' => 'The uploaded verification document could not be processed. Please upload again.',
            ]);
        }

        try {
            $verificationService->resubmitChild($verification, $finalizedPath);
        } catch (InvalidArgumentException $exception) {
            return redirect()->route('parent.children.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('parent.children.index')
            ->with('success', 'Child verification resubmitted successfully. We will review the updated document.');
    }

    private function ensureApprovedParent(): ?RedirectResponse
    {
        $parent = Auth::user();

        if (! $parent->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Please verify your email first.');
        }

        if (! $parent->canBeParent()) {
            abort(403, 'You must be 18 or older to create a child account.');
        }

        if (! $parent->isParentRegistration() || ! $parent->isParentVerificationApproved()) {
            return redirect()->route('parent.verification.status')
                ->with('warning', 'Your guardian account is still under admin review.');
        }

        if (! $parent->hasCompletedGuardianOnboarding()) {
            return redirect()->route('guardian.onboarding.show')
                ->with('warning', 'Please complete Guardian onboarding before creating a child account.');
        }

        return null;
    }

    private function ensureApprovedParentForJson(): ?JsonResponse
    {
        $parent = Auth::user();

        if (! $parent->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email first.',
            ], 403);
        }

        if (! $parent->canBeParent()) {
            return response()->json([
                'message' => 'You must be 18 or older to create a child account.',
            ], 403);
        }

        if (! $parent->isParentRegistration() || ! $parent->isParentVerificationApproved()) {
            return response()->json([
                'message' => 'Guardian verification is required before child registration uploads.',
            ], 403);
        }

        if (! $parent->hasCompletedGuardianOnboarding()) {
            return response()->json([
                'message' => 'Please complete Guardian onboarding before creating a child account.',
            ], 403);
        }

        return null;
    }
}
