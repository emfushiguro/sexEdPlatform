# Learner Identity Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** Require manual, age-appropriate identity review for newly registered teen and adult learners while preserving child/guardian workflows and securing identity evidence.

**Architecture:** Create a learner verification case per covered user and age pathway, with typed private evidence and audit events. Route verified learners through one submission/status flow, enforce a central access gate, and extend the existing admin verification workspace. Move existing sensitive public documents to private storage through a verified, idempotent transition.

**Tech Stack:** PHP 8.2, Laravel 12, MySQL, PHPUnit 11, Blade, Alpine.js 3, Tailwind CSS 3, JavaScript Node test runner, Vite 7.

**Design reference:** docs/superpowers/specs/2026-09-24-learner-identity-verification-design.md

## Global Constraints

- Preserve existing development data. Use additive migrations and ordinary php artisan migrate only. Never run migrate:fresh, db:wipe, TRUNCATE, DROP, destructive seeders, or tests against the development database.
- Before any database test, confirm phpunit.xml targets cc_db_test and that this is distinct from the development database. Stop if the test database is not isolated.
- Keep existing learner registration, email verification, Guardian/Dependent verification, guardian relationship/invitation rules, authentication, and unrelated profile functions authoritative.
- New learner identity requirements apply only to registrations created after this change. Existing accounts have no learner identity case and remain exempt.
- A new teen may self-register; linked guardian checks remain separate. Every teen submits a school ID, institution ID, or government ID plus a selfie. Every adult submits a government ID plus a selfie.
- Use User::calculateAge and User::deriveAgeBracketCache on the server. A covered teen needs a new adult review at 18 before learner access; no grace period.
- Reuse config/guardian_identity.php for government-ID choices and back-side rules. Other requires a description and manual confirmation by the reviewer.
- Images are JPEG, PNG, or WebP, at most 5120 KB each, with both dimensions between 320 and 6000 pixels. Check actual image content server-side.
- Store evidence on the private local disk. No public identity URLs, external AI service, face matching, liveness detection, facial embedding, React, Vue, or new image-upload framework.
- Never invent a retention period. Keep review metadata; remove superseded raw files after verified replacement and preserve current raw evidence under the applicable policy.
- Preserve keyboard operation, clear errors, visible focus, status text, at least 44-by-44-pixel touch controls, and camera upload fallback.
- The admin's decision is manual. Evidence upload or checklist state cannot approve a case.

## Planned File Map

**Create**

- database/migrations/2026_09_24_000001_create_learner_identity_verification_tables.php — additive case, current evidence, and audit tables.
- app/Models/LearnerIdentityVerification.php — case relationships and status helpers.
- app/Models/LearnerIdentityEvidence.php — typed current evidence slot and metadata.
- app/Models/LearnerIdentityAudit.php — append-only review history.
- app/Services/Identity/LearnerIdentityRequirement.php — cohort and DOB-derived current pathway.
- app/Services/Identity/LearnerIdentitySubmission.php — validated private submission and resubmission.
- app/Services/Identity/LearnerIdentityReview.php — admin-only decisions and notifications.
- app/Http/Requests/Auth/SubmitLearnerIdentityRequest.php — age-aware initial and replacement validation.
- app/Http/Controllers/Auth/LearnerIdentityVerificationController.php — learner form, submission, and status.
- app/Http/Middleware/EnsureLearnerIdentityVerified.php — server access gate.
- app/Http/Controllers/Admin/LearnerIdentityVerificationController.php — learner review and private preview.
- app/Http/Controllers/Auth/RegistrationTempDocumentController.php — session-bound legacy temporary preview.
- app/Console/Commands/MoveLegacyIdentityDocumentsToPrivateStorage.php — dry-run and verified apply.
- app/Notifications/Admin/LearnerIdentitySubmittedNotification.php — admin queue alert.
- app/Notifications/LearnerIdentitySubmittedNotification.php — learner pending alert.
- app/Notifications/LearnerIdentityApprovedNotification.php — learner approval alert.
- app/Notifications/LearnerIdentityRejectedNotification.php — learner rejection reason.
- resources/views/auth/learner-identity-verification.blade.php — shared teen/adult evidence form.
- resources/views/auth/learner-identity-status.blade.php — pending/rejected/approved status.
- resources/views/admin/parent-verifications/show-learner.blade.php — manual review detail.
- resources/js/identity-selfie.js — camera/upload/preview state.
- tests/Feature/Identity/LearnerIdentitySchemaTest.php
- tests/Feature/Identity/LearnerIdentityRequirementTest.php
- tests/Feature/Identity/LearnerIdentitySubmissionTest.php
- tests/Feature/Identity/LearnerIdentityAccessTest.php
- tests/Feature/Identity/LearnerIdentityReviewTest.php
- tests/Feature/Identity/LegacyIdentityStorageTest.php
- tests/JavaScript/identity-selfie.test.mjs
- docs/superpowers/verification/2026-09-24-learner-identity-verification.md — final verification evidence.

**Modify**

- app/Models/User.php — learner case relationship.
- app/Http/Controllers/Auth/RegisteredUserController.php — create initial case with account.
- app/Http/Controllers/Auth/EmailVerificationPromptController.php — email-first learner redirect.
- app/View/Components/WizardStepper.php — show Identity Verification for the new learner flow while retaining legacy/guardian/dependent sequences.
- resources/views/auth/register.blade.php, resources/views/auth/register-account.blade.php, and resources/views/auth/verify-email.blade.php — keep explicit learner steps aligned.
- bootstrap/app.php — append learner identity gate after the current suspension guard.
- routes/auth.php and routes/admin.php — owner/admin endpoints.
- resources/js/app.js — register shared selfie component.
- resources/views/admin/parent-verifications/index.blade.php — learner queue tab and filters.
- resources/views/layouts/admin.blade.php — add the pending learner count to the existing verification navigation.
- resources/views/legal/privacy.blade.php — concise evidence and manual-review explanation.
- app/Services/Auth/RegistrationTempUploadService.php — private staging and legacy session compatibility.
- app/Http/Controllers/Auth/ParentRegistrationController.php — session preview URLs.
- app/Services/ParentChildVerificationService.php — private child replacement cleanup.
- app/Http/Controllers/Admin/ParentChildVerificationController.php — add the learner queue type and authorized child file response.
- tests/Feature/Auth/RegistrationTest.php, tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php, tests/Unit/Services/RegistrationTempUploadServiceTest.php — focused regressions.
- tests/Feature/WizardStepperTest.php — age-flow stepper regression.

## Task 1: Add learner verification case, evidence, and audit schema

**Files:** Create the migration and three models listed above; modify app/Models/User.php; test tests/Feature/Identity/LearnerIdentitySchemaTest.php.

**Interfaces:** Produces User::identityVerifications(): HasMany and case relationships evidence(): HasMany, audits(): HasMany, learner(): BelongsTo. A null case status means unsubmitted; case absence means legacy exemption.

- [ ] **Step 1: Write failing schema and relationship tests.** Verify unique user/pathway, allowed nullable initial status, three evidence slots, no duplicate slot, and case-linked audits. Include this representative test with imports for the four models and QueryException:

~~~php
public function test_a_learner_has_one_case_per_pathway_and_three_distinct_evidence_slots(): void
{
    $user = User::factory()->create();
    $case = LearnerIdentityVerification::query()->create([
        'user_id' => $user->id,
        'pathway' => 'teen',
        'submission_round' => 0,
    ]);
    $this->assertNull($case->status);
    $this->assertCount(1, $user->identityVerifications);
    $this->assertSame($user->id, $case->learner->id);

    foreach (['identity_front', 'identity_back', 'selfie'] as $slot) {
        $case->evidence()->create([
            'slot' => $slot, 'storage_path' => 'learner-verifications/test/'.$slot.'.jpg',
            'mime_type' => 'image/jpeg', 'byte_size' => 1000,
            'width' => 640, 'height' => 640, 'submitted_at' => now(),
        ]);
    }
    $this->assertCount(3, $case->fresh()->evidence);
    $this->expectException(QueryException::class);
    $case->evidence()->create([
        'slot' => 'selfie', 'storage_path' => 'another.jpg',
        'mime_type' => 'image/jpeg', 'byte_size' => 1000,
        'width' => 640, 'height' => 640, 'submitted_at' => now(),
    ]);
}
~~~

- [ ] **Step 2: Run the test against the isolated test database.** Run: php artisan test tests/Feature/Identity/LearnerIdentitySchemaTest.php. Expected: FAIL because the case model/table does not exist.
- [ ] **Step 3: Add the incremental migration and model relationships.** Create learner_identity_verifications with user_id FK, pathway string(16), document_type string(32) nullable, government_id_type string(40) nullable, government_id_type_other string(80) nullable, status string(16) nullable, submission_round unsigned integer default 0, submitted_at/reviewed_at/approved_at/superseded_at nullable timestamps, reviewed_by nullable user FK, rejection_reason nullable text, timestamps, and unique(user_id,pathway). Create learner_identity_evidence with verification_id FK, slot string(24), storage_path string, mime_type string(64), byte_size unsigned big integer, width/height unsigned integers, submitted_at, timestamps, unique(verification_id,slot). Create learner_identity_audits with verification_id FK, actor_id nullable user FK, action string(32), from_status/to_status nullable strings, submission_round unsigned integer, reason nullable text, created_at. Use this migration structure; make down() throw LogicException so a routine rollback cannot delete records or falsely mark the migration reversed:

~~~php
Schema::create('learner_identity_verifications', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('pathway', 16);
    $table->string('document_type', 32)->nullable();
    $table->string('government_id_type', 40)->nullable();
    $table->string('government_id_type_other', 80)->nullable();
    $table->string('status', 16)->nullable();
    $table->unsignedInteger('submission_round')->default(0);
    $table->timestamp('submitted_at')->nullable();
    $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamp('superseded_at')->nullable();
    $table->timestamps();
    $table->unique(['user_id', 'pathway']);
    $table->index(['pathway', 'status', 'superseded_at']);
});
Schema::create('learner_identity_evidence', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('verification_id')->constrained('learner_identity_verifications')->cascadeOnDelete();
    $table->string('slot', 24);
    $table->string('storage_path');
    $table->string('mime_type', 64);
    $table->unsignedBigInteger('byte_size');
    $table->unsignedInteger('width');
    $table->unsignedInteger('height');
    $table->timestamp('submitted_at');
    $table->timestamps();
    $table->unique(['verification_id', 'slot']);
});
Schema::create('learner_identity_audits', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('verification_id')->constrained('learner_identity_verifications')->cascadeOnDelete();
    $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('action', 32);
    $table->string('from_status', 16)->nullable();
    $table->string('to_status', 16)->nullable();
    $table->unsignedInteger('submission_round');
    $table->text('reason')->nullable();
    $table->timestamp('created_at');
});
~~~

Define fillable fields and datetime casts matching the columns; audit has UPDATED_AT = null. Use VerificationStatus values for non-null status writes. Add the User hasMany relationship. No existing table is altered.
- [ ] **Step 4: Run the focused test and inspect migration SQL.** Run: php artisan test tests/Feature/Identity/LearnerIdentitySchemaTest.php. Expected: PASS. Run: php artisan migrate --pretend. Expected for this migration: three CREATE TABLE statements and no statement altering existing data; inspect any other pending migrations separately. Apply php artisan migrate incrementally only when the environment and existing schema have been inspected; do not reset data.
- [ ] **Step 5: Commit this independently testable schema.** Stage only Task 1 files. Commit: feat: add learner identity case schema.

## Task 2: Create cohort requirement and DOB-derived pathway

**Files:** Create app/Services/Identity/LearnerIdentityRequirement.php; modify app/Http/Controllers/Auth/RegisteredUserController.php; test tests/Feature/Identity/LearnerIdentityRequirementTest.php and tests/Feature/Auth/RegistrationTest.php.

**Interfaces:** LearnerIdentityRequirement::current(User $user): ?LearnerIdentityVerification returns null only for exempt accounts/roles; ::createForNewLearner(User $user): LearnerIdentityVerification creates the first teen/adult case; ::pathwayFor(User $user): string returns teen or adult from current DOB and throws for a covered child. For a covered learner whose DOB is corrected below 13, current() throws a domain exception mapped by the learner controller/gate to a contact-support hold; it never returns null.

- [ ] **Step 1: Write failing age, cohort, and birthday tests.** Freeze time and cover ages 12, 13, 17, and 18, plus a legacy teen with no case. Test a corrected DOB that changes teen to adult, back to teen, then adult again: exactly one case is active each time and no earlier approval is reused. Include the birthday transition assertion:

~~~php
Carbon::setTestNow('2026-09-24 10:00:00');
$teen = User::factory()->create([
    'role' => 'learner', 'birthdate' => '2008-09-25',
    'email_verified_at' => now(),
]);
$teen->assignRole('learner');
$requirement = app(LearnerIdentityRequirement::class);
$first = $requirement->createForNewLearner($teen);
$this->assertSame('teen', $first->pathway);

Carbon::setTestNow('2026-09-25 10:00:00');
$adult = $requirement->current($teen->fresh());
$this->assertSame('adult', $adult->pathway);
$this->assertNull($adult->status);
$this->assertNotNull($first->fresh()->superseded_at);
$this->assertCount(2, $teen->identityVerifications()->get());
Carbon::setTestNow();
~~~

- [ ] **Step 2: Run the focused tests.** Run: php artisan test tests/Feature/Identity/LearnerIdentityRequirementTest.php tests/Feature/Auth/RegistrationTest.php. Expected: FAIL because new registration does not create a case and the requirement service is absent.
- [ ] **Step 3: Implement the requirement service and narrow registration write.** Check User::isLearner, isParentRegistration, calculateAge, and deriveAgeBracketCache. Existing accounts with no case remain exempt; a covered account never becomes exempt through a DOB change. Use a transaction and unique(user_id,pathway) to create the adult case once and mark the teen case superseded. Add a created audit entry for initial and newly opened cases and a superseded entry for the replaced case. Create the initial case in the same transaction as user creation and learner role assignment; fire Registered only after commit. The core transition is:

~~~php
$pathsToDelete = [];
$case = DB::transaction(function () use ($user, &$pathsToDelete): LearnerIdentityVerification {
    $pathway = $this->pathwayFor($user);
    $current = $user->identityVerifications()
        ->whereNull('superseded_at')->lockForUpdate()->firstOrFail();
    if ($current->pathway === $pathway) {
        return $current;
    }
    $pathsToDelete = array_merge(
        $pathsToDelete,
        $current->evidence()->pluck('storage_path')->all(),
    );
    $current->evidence()->delete();
    $current->forceFill(['superseded_at' => now()])->save();
    $current->audits()->create([
        'actor_id' => null, 'action' => 'superseded',
        'from_status' => $current->status, 'to_status' => $current->status,
        'submission_round' => $current->submission_round, 'created_at' => now(),
    ]);
    $target = $user->identityVerifications()->firstOrCreate(
        ['pathway' => $pathway],
        ['submission_round' => 0],
    );
    if ($target->wasRecentlyCreated) {
        $target->audits()->create([
            'actor_id' => null, 'action' => 'created',
            'from_status' => null, 'to_status' => null,
            'submission_round' => 0, 'created_at' => now(),
        ]);
    }
    if ($target->superseded_at !== null) {
        $pathsToDelete = array_merge(
            $pathsToDelete,
            $target->evidence()->pluck('storage_path')->all(),
        );
        $target->evidence()->delete();
        $target->forceFill([
            'status' => null, 'document_type' => null,
            'government_id_type' => null, 'government_id_type_other' => null,
            'submitted_at' => null, 'reviewed_by' => null,
            'reviewed_at' => null, 'approved_at' => null,
            'rejection_reason' => null, 'superseded_at' => null,
        ])->save();
        $target->audits()->create([
            'actor_id' => null, 'action' => 'reactivated',
            'from_status' => null, 'to_status' => null,
            'submission_round' => $target->submission_round,
            'created_at' => now(),
        ]);
    }
    return $target;
});
Storage::disk('local')->delete($pathsToDelete);
return $case;
~~~

Delete those private paths only after the transaction commits; log any deletion failure for a retry. Define pathwayFor with a match on User::deriveAgeBracketCache for teens/adults and DomainException for kids/missing DOB. If a DOB correction returns to an earlier superseded pathway, clear its former approval and evidence as shown, require fresh evidence, and audit reactivation; never reuse a prior approval. The under-13 hold is rendered without granting learner access.
- [ ] **Step 4: Run focused tests and check non-regression.** Run: php artisan test tests/Feature/Identity/LearnerIdentityRequirementTest.php tests/Feature/Auth/RegistrationTest.php. Expected: PASS, including one case for a new 13-year-old and none for a new child or any pre-existing account.
- [ ] **Step 5: Commit.** Stage only Task 2 files. Commit: feat: route new learners by DOB.

## Task 3: Submit and resubmit private identity evidence

**Files:** Create app/Services/Identity/LearnerIdentitySubmission.php and app/Http/Requests/Auth/SubmitLearnerIdentityRequest.php; test tests/Feature/Identity/LearnerIdentitySubmissionTest.php.

**Interfaces:** LearnerIdentitySubmission::submit(User $actor, LearnerIdentityVerification $case, array $data, array $files): LearnerIdentityVerification accepts only the current owned case. File keys are identity_front, identity_back, and selfie. Form request authorizes the owner with verified email and a null/rejected case status.

- [ ] **Step 1: Write failing service and validation tests.** Cover teen school/institution/government documents, adult government choices including Other, front/back config, required selfie for both, age mismatch, pending/approved replacement denial, corrupt/wrong-MIME/over-5120-KB/out-of-range images, storage failure cleanup, selective replacement, and removal of superseded bytes. The central assertion is:

~~~php
Storage::fake('local');
$case = $requirement->createForNewLearner($adult);
$submitted = app(LearnerIdentitySubmission::class)->submit($adult, $case, [
    'document_type' => 'government_id',
    'government_id_type' => 'philhealth',
], [
    'identity_front' => UploadedFile::fake()->image('id.jpg', 800, 600),
    'selfie' => UploadedFile::fake()->image('selfie.jpg', 800, 800),
]);
$this->assertSame('pending', $submitted->status);
$this->assertSame(1, $submitted->submission_round);
$this->assertEqualsCanonicalizing(
    ['identity_front', 'selfie'],
    $submitted->evidence->pluck('slot')->all(),
);
foreach ($submitted->evidence as $evidence) {
    Storage::disk('local')->assertExists($evidence->storage_path);
}
~~~

- [ ] **Step 2: Run the focused test.** Run: php artisan test tests/Feature/Identity/LearnerIdentitySubmissionTest.php. Expected: FAIL because the submission service and request are absent.
- [ ] **Step 3: Implement request and transactional submission.** Validate files with file, image, mimes:jpg,jpeg,png,webp, max:5120, and dimensions:min_width=320,min_height=320,max_width=6000,max_height=6000. Validate accepted document types from the current pathway, government ID subtype via config/guardian_identity.php, the Other description at max 80 characters, confirmation accepted, front/back per selected type, and selfie always. On resubmission allow an unchanged valid slot to remain; if document type changes, require new identity_front and the newly required back. Store each new file under learner-verifications/{user id}/{pathway} on local with generated names, check exists and actual image metadata, lock the case, write current slots, set pending and increment round, append submitted/resubmitted audit, commit, then delete replaced old paths. On any failure delete only newly stored paths and leave old evidence/status unchanged. Use this slot update rule:

~~~php
foreach ($files as $slot => $file) {
    if (! in_array($slot, ['identity_front', 'identity_back', 'selfie'], true)) {
        throw new InvalidArgumentException('Unsupported evidence slot.');
    }
    $image = getimagesize($file->getRealPath());
    if ($image === false) {
        throw new InvalidArgumentException('Invalid identity image.');
    }
    $path = $file->store('learner-verifications/'.$actor->id.'/'.$case->pathway, 'local');
    if (! $path || ! Storage::disk('local')->exists($path)) {
        throw new RuntimeException('Identity evidence could not be stored.');
    }
    $newPaths[] = $path;
    $old = $case->evidence()->where('slot', $slot)->first();
    if ($old) {
        $replacedPaths[] = $old->storage_path;
    }
    $case->evidence()->updateOrCreate(['slot' => $slot], [
        'storage_path' => $path, 'mime_type' => $file->getMimeType(),
        'byte_size' => $file->getSize(),
        'width' => $image[0], 'height' => $image[1],
        'submitted_at' => now(),
    ]);
}
~~~

Reject spoofed images when getimagesize fails. Clear an obsolete back slot if the new document type no longer permits it, after the transaction succeeds. Wrap file writes and database operations with cleanup in a catch block. Keep notifications out of this service until Task 7.
- [ ] **Step 4: Run focused tests.** Run: php artisan test tests/Feature/Identity/LearnerIdentitySubmissionTest.php. Expected: PASS; Storage::disk('public') contains no new learner evidence.
- [ ] **Step 5: Commit.** Stage only Task 3 files. Commit: feat: submit private learner evidence.

## Task 4: Route verified learners and enforce the account gate

**Files:** Create app/Http/Controllers/Auth/LearnerIdentityVerificationController.php, app/Http/Middleware/EnsureLearnerIdentityVerified.php, resources/views/auth/learner-identity-verification.blade.php, and resources/views/auth/learner-identity-status.blade.php; modify app/Http/Controllers/Auth/EmailVerificationPromptController.php, app/View/Components/WizardStepper.php, resources/views/auth/register.blade.php, resources/views/auth/register-account.blade.php, resources/views/auth/verify-email.blade.php, bootstrap/app.php, routes/auth.php; test tests/Feature/Identity/LearnerIdentityAccessTest.php and tests/Feature/WizardStepperTest.php.

**Interfaces:** Routes learner.identity.create (GET), learner.identity.store (POST), and learner.identity.status (GET) are authenticated; create/store/status require verified email and the current owned case. The middleware calls LearnerIdentityRequirement::current and redirects only covered unapproved learners. Task 5 enhances the initial upload-only form with camera capture.

- [ ] **Step 1: Write failing access, route, and stepper tests.** Cover unverified email, unsubmitted, pending, rejected, approved, old account with no case, guardian account, guardian-created child, an unlinked teen learner, a teen with a pending guardian relationship, direct profile completion, dashboard, chat, seminar, and subscription URLs. Assert the unlinked teen is governed by identity review alone and the linked teen's guardian-specific access remains governed by the relationship state. Assert verification and account-deletion routes stay available to pending learners. Verify guest/new learner steppers show Identity Verification between Verify Email and Profile, while existing learner profile, guardian, and dependent steppers retain their prior sequences. Representative check:

~~~php
$learner = User::factory()->create([
    'role' => 'learner', 'birthdate' => now()->subYears(17)->toDateString(),
    'email_verified_at' => now(),
]);
$learner->assignRole('learner');
$case = app(LearnerIdentityRequirement::class)->createForNewLearner($learner);
$this->actingAs($learner)->get(route('profile.complete'))
    ->assertRedirect(route('learner.identity.create'));
$case->update(['status' => 'pending']);
$this->actingAs($learner)->get(route('learner.dashboard'))
    ->assertRedirect(route('learner.identity.status'));
$case->update(['status' => 'approved']);
$this->actingAs($learner)->get(route('profile.complete'))->assertOk();
~~~

- [ ] **Step 2: Run focused tests.** Run: php artisan test tests/Feature/Identity/LearnerIdentityAccessTest.php tests/Feature/WizardStepperTest.php. Expected: FAIL because the routes and new step do not exist.
- [ ] **Step 3: Add controller, routes, two basic Blade views, and gate.** The form renders ID type, front/back image inputs, selfie upload input, confirmation, privacy link, field errors, and CSRF token. The status view renders pending/rejected/approved text and rejection reason; rejected users link back to the form. The controller gets its case from LearnerIdentityRequirement, never a client-supplied user ID, then passes validated data/files to LearnerIdentitySubmission. Add the learner redirect after all guardian redirects and before profile completion in EmailVerificationPromptController. Append middleware to the web group after the existing suspension guard in bootstrap/app.php. Use this gate structure:

~~~php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();
    if (! $user || ! $user->isLearner() || $user->isParentRegistration()
        || $request->routeIs(
            'verification.*', 'learner.identity.*', 'logout',
            'password.*', 'profile.password.update', 'profile.account.delete',
            'privacy', 'terms', 'moderation.suspension-status',
            'moderation.appeals.*',
        )) {
        return $next($request);
    }
    if (! $user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }
    try {
        $case = $this->requirement->current($user);
    } catch (DomainException) {
        return redirect()->route('learner.identity.status');
    }
    if ($case === null || $case->status === VerificationStatus::Approved->value) {
        return $next($request);
    }
    return redirect()->route($case->status === null
        ? 'learner.identity.create' : 'learner.identity.status');
}
~~~

Inject LearnerIdentityRequirement in the middleware constructor. On a covered DOB correction below 13, the status action renders a guardian-support hold rather than redirecting back into the gate. The route allowlist must cover email link verification, resending, logout, password actions, privacy, account deletion, and the existing suspension appeal route. Check every authenticated route group for direct access bypass. Add the learner identity step to WizardStepper only for new registration/covered accounts, then align the three explicit Blade step arrays without changing parent/dependent maps.
- [ ] **Step 4: Run focused access and existing guardian tests.** Run: php artisan test tests/Feature/Identity/LearnerIdentityAccessTest.php tests/Feature/WizardStepperTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Auth/EmailVerificationTest.php. Expected: PASS, including no redirect loops.
- [ ] **Step 5: Commit.** Stage only Task 4 files. Commit: feat: gate new learner accounts on identity.

## Task 5: Add the shared responsive selfie capture and upload UI

**Files:** Create resources/js/identity-selfie.js and tests/JavaScript/identity-selfie.test.mjs; modify resources/js/app.js and resources/views/auth/learner-identity-verification.blade.php; extend tests/Feature/Identity/LearnerIdentityAccessTest.php for form markup.

**Interfaces:** Export createIdentitySelfie(dependencies = {}) for Alpine registration and Node tests. It owns only client image state; the server Form Request remains authoritative. It writes the selected File into the selfie file input only when the user chooses Use Photo, and it never uploads during retake.

- [ ] **Step 1: Write failing JavaScript state tests.** Stub getUserMedia, MediaStream tracks, canvas.toBlob, object URLs, and DataTransfer. Cover available camera, unavailable API, permission denied, disconnected stream, capture, retake, cancel, upload replacement, and stopping all tracks. Representative test:

~~~js
test('permission denial leaves upload available and stops no active stream', async () => {
    const component = createIdentitySelfie({
        mediaDevices: { getUserMedia: async () => { throw new Error('NotAllowedError'); } },
    });
    await component.startCamera();
    assert.match(component.cameraError, /upload/i);
    assert.equal(component.cameraActive, false);
    assert.equal(component.uploadAvailable, true);
});
~~~

- [ ] **Step 2: Run the JavaScript test.** Run: node --test tests/JavaScript/identity-selfie.test.mjs. Expected: FAIL because the exported factory is absent.
- [ ] **Step 3: Build the single Alpine component and form layout.** Register the factory in resources/js/app.js. The component requests video with facingMode user after a click, binds a responsive playsinline video element, captures to a Blob/File through a canvas, and previews with an object URL. Retake discards the candidate locally; Replace picks an uploaded image; Cancel revokes object URLs, clears input, and stops tracks. If camera, canvas, or DataTransfer is unavailable, keep the native file upload visible. Use this core shutdown method and call it on every exit path:

~~~js
stopCamera() {
    this.stream?.getTracks().forEach((track) => track.stop());
    this.stream = null;
    this.cameraActive = false;
    if (this.$refs?.video) this.$refs.video.srcObject = null;
},
destroy() {
    this.stopCamera();
    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
},
~~~

The Blade form must include separate Take a selfie and Upload a selfie controls, a photo-quality guide (one person, clear/recent, lighting, visible face, no sunglasses/covering/heavy filters), privacy/manual-review text, preview and Use Photo/Retake/Replace/Cancel actions, data-testid hooks, aria-live error/status text, focus states, keyboard controls, and 44-pixel targets. Keep the upload input usable even when camera permissions fail. Use the existing auth layout, colors, icons, Alpine, and Tailwind; do not add a frontend dependency.
- [ ] **Step 4: Run UI and build verification.** Run: node --test tests/JavaScript/identity-selfie.test.mjs. Run: php artisan test tests/Feature/Identity/LearnerIdentityAccessTest.php. Run: npm run build. Expected: PASS and no JavaScript build error. On a supported phone and desktop browser, manually confirm camera preview, upload fallback, retake, keyboard focus, and no horizontal overflow.
- [ ] **Step 5: Commit.** Stage only Task 5 files. Commit: feat: add responsive verification selfie.

## Task 6: Add learner cases to the existing admin review workspace

**Files:** Create app/Services/Identity/LearnerIdentityReview.php, app/Http/Controllers/Admin/LearnerIdentityVerificationController.php, resources/views/admin/parent-verifications/show-learner.blade.php; modify app/Http/Controllers/Admin/ParentChildVerificationController.php, routes/admin.php, resources/views/admin/parent-verifications/index.blade.php, resources/views/layouts/admin.blade.php; test tests/Feature/Identity/LearnerIdentityReviewTest.php.

**Interfaces:** LearnerIdentityReview::approve(User $reviewer, LearnerIdentityVerification $case): void and ::reject(User $reviewer, LearnerIdentityVerification $case, string $reason): void require an admin and a current pending case. Admin routes are inside the existing auth, role:admin group. LearnerIdentityVerificationController::evidence(LearnerIdentityVerification $case, string $slot) serves a private current evidence slot; no storage path is accepted from the URL.

- [ ] **Step 1: Write failing review and evidence security tests.** Cover the learner queue's pending/approved/rejected and teen/adult filters; admin-only details and image preview; non-admin denial; case/slot mismatch; approve/reject transitions; reviewer/timestamps; required rejection reason; explicit government-issued confirmation before approving an Other adult ID; repeat decision conflict; superseded teen decision denial; and audit entries. Representative denial:

~~~php
$url = route('admin.parent-verifications.learners.evidence', [$case, 'selfie']);
$this->actingAs($case->learner)->get($url)->assertForbidden();
$this->actingAs($admin)->get($url)
    ->assertOk()
    ->assertHeader('Cache-Control', 'private, no-store');
~~~

- [ ] **Step 2: Run focused review tests.** Run: php artisan test tests/Feature/Identity/LearnerIdentityReviewTest.php. Expected: FAIL because review routes/service do not exist.
- [ ] **Step 3: Implement narrow review service, controller, and workspace extension.** Add a learners type to the existing admin parent-verifications index and pass bounded eager-loaded learner cases with status/category filters; avoid queries in Blade loops. The detail view places account name, DOB, document, selfie, status, timestamps, reviewer, rejection reason, and audits together. Render adult and teen checklist guidance from the spec without treating ticks as approval. When the adult selected Other, require a separate checked government-issued confirmation on the approval request; validate it server-side without persisting checklist ticks. Use row locking to accept only a pending, nonsuperseded case whose pathway matches current DOB and whose required private files still exist. Save reviewer, decision time, approved_at or reason, and append audit. Keep notifications for Task 7. This decision guard is mandatory:

~~~php
$locked = LearnerIdentityVerification::query()->lockForUpdate()->findOrFail($case->id);
abort_unless($reviewer->hasRole('admin'), 403);
abort_if($locked->superseded_at !== null
    || $locked->status !== VerificationStatus::Pending->value
    || $locked->pathway !== $this->requirement->pathwayFor($locked->learner), 409);
abort_unless($this->requiredEvidenceExists($locked), 422);
~~~

For evidence response, look up one of identity_front/identity_back/selfie through the case relationship, check Storage::disk('local')->exists, and stream that file with Content-Type from validated metadata, X-Content-Type-Options: nosniff, Cache-Control: private, no-store, and Content-Disposition: inline. No public URL or arbitrary path parameter.
- [ ] **Step 4: Run review and existing admin tests.** Run: php artisan test tests/Feature/Identity/LearnerIdentityReviewTest.php tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php. Expected: PASS; existing guardian and child queues still work.
- [ ] **Step 5: Commit.** Stage only Task 6 files. Commit: feat: review learner identity in admin queue.

## Task 7: Notify participants and explain evidence use

**Files:** Create the four notification classes in the file map; modify app/Services/Identity/LearnerIdentitySubmission.php, app/Services/Identity/LearnerIdentityReview.php, resources/views/auth/learner-identity-verification.blade.php, resources/views/legal/privacy.blade.php; extend tests/Feature/Identity/LearnerIdentitySubmissionTest.php and tests/Feature/Identity/LearnerIdentityReviewTest.php.

**Interfaces:** Admin submission notification uses the existing database channel and links to the learner queue. Learner submitted/approved/rejected notifications use the existing mail and database conventions. Notifications include case ID/status and a safe action route; they never include image bytes, storage paths, or original file names.

- [ ] **Step 1: Write failing notification and privacy tests.** Fake Notification. Assert submission and resubmission notify the learner and administrators after commit; approve/reject notify the learner once; a rejection includes the reason; no notification contains a private path. Assert the form links to privacy and says manual review; assert the privacy page explains ID/selfie use and does not claim automated biometrics. Representative test:

~~~php
Notification::fake();
$case = $this->submitCompleteAdultCase();
Notification::assertSentTo(
    $admin,
    \App\Notifications\Admin\LearnerIdentitySubmittedNotification::class,
);
Notification::assertSentTo(
    $case->learner,
    LearnerIdentitySubmittedNotification::class,
    fn ($notification) => ! str_contains(
        json_encode($notification->toArray($case->learner)),
        'learner-verifications/',
    ),
);
~~~

The admin notification uses its fully qualified class name so the two submitted-notification classes do not collide in test imports.
- [ ] **Step 2: Run focused tests.** Run: php artisan test tests/Feature/Identity/LearnerIdentitySubmissionTest.php tests/Feature/Identity/LearnerIdentityReviewTest.php. Expected: FAIL on missing notification assertions/copy.
- [ ] **Step 3: Add classes and dispatch after committed state.** Mirror ParentVerificationSubmittedNotification for mail/database and Admin\ParentVerificationRequestSubmittedNotification for the admin database payload. The admin action_url targets the learner review page in the existing parent-verifications route group. Use the existing emails.moderation-status view; keep rejection copy age appropriate and concise. Dispatch with DB::afterCommit or after the transaction returns, catch transport failures, and log only user/case IDs and notification class. Core payload:

~~~php
public function toDatabase(object $notifiable): array
{
    return [
        'type' => 'learner_identity_submitted',
        'status' => 'pending',
        'title' => 'New Learner Identity Review',
        'message' => 'A learner submitted identity evidence for manual review.',
        'verification_id' => $this->case->id,
        'action_url' => route('admin.parent-verifications.learners.show', $this->case),
        'severity' => 'info',
    ];
}
~~~

The submission page explains collected ID and selfie, purpose, authorized reviewers, manual use, and privacy-policy retention text. Update resources/views/legal/privacy.blade.php with the same facts; do not add a period absent from approved policy.
- [ ] **Step 4: Run tests.** Run: php artisan test tests/Feature/Identity/LearnerIdentitySubmissionTest.php tests/Feature/Identity/LearnerIdentityReviewTest.php tests/Feature/Notifications/NotificationUiSemanticsTest.php. Expected: PASS and no sensitive path in notification payloads.
- [ ] **Step 5: Commit.** Stage only Task 7 files. Commit: feat: notify learner identity decisions.

## Task 8: Move existing sensitive temporary and child documents to private storage

**Files:** Create app/Http/Controllers/Auth/RegistrationTempDocumentController.php and app/Console/Commands/MoveLegacyIdentityDocumentsToPrivateStorage.php; modify app/Services/Auth/RegistrationTempUploadService.php, app/Http/Controllers/Auth/ParentRegistrationController.php, app/Services/ParentChildVerificationService.php, app/Http/Controllers/Admin/ParentChildVerificationController.php, routes/auth.php, routes/admin.php, resources/views/admin/parent-verifications/index.blade.php; test tests/Feature/Identity/LegacyIdentityStorageTest.php, tests/Unit/Services/RegistrationTempUploadServiceTest.php, tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php, tests/Feature/Auth/ParentChildVerificationResubmissionTest.php.

**Interfaces:** RegistrationTempUploadService stores/finalizes/removes on local. RegistrationTempDocumentController::show(Request $request, string $flow, string $step) serves only a matching session temp file; it does not accept a file path. The admin child evidence route uses the child verification record's stored path and role:admin. The Artisan command identity:move-legacy-documents defaults to dry run and moves only with --apply.

- [ ] **Step 1: Write failing storage and authorization tests.** Update temp service tests to fake local and assert public missing. Test guest parent temp preview is session-bound, child temp preview requires the owning approved guardian, another session cannot fetch either, and files are no-store. Test final child admin preview is private and user/non-admin requests are forbidden. Test dry-run leaves public files, --apply copies and hashes before deleting public originals, second run changes nothing, conflicts leave both files untouched, missing files are reported. Representative command check:

~~~php
Storage::fake('public');
Storage::fake('local');
Storage::disk('public')->put('registration-temp/child/verification_document/proof.pdf', 'original');
$this->artisan('identity:move-legacy-documents')->assertExitCode(0);
Storage::disk('public')->assertExists('registration-temp/child/verification_document/proof.pdf');
$this->artisan('identity:move-legacy-documents --apply')->assertExitCode(0);
Storage::disk('local')->assertExists('registration-temp/child/verification_document/proof.pdf');
Storage::disk('public')->assertMissing('registration-temp/child/verification_document/proof.pdf');
~~~

- [ ] **Step 2: Run focused tests.** Run: php artisan test tests/Feature/Identity/LegacyIdentityStorageTest.php tests/Unit/Services/RegistrationTempUploadServiceTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php. Expected: FAIL on current public-disk behavior.
- [ ] **Step 3: Switch new uploads and previews to private.** Change RegistrationTempUploadService to local for new files; preserve session shape, and if a legacy session refers to a public file, copy/verify it into local on first use before removing the public original. Replace the three ParentRegistrationController asset('storage/...') preview URLs with route('registration.temp-document.preview', [flow,step]). The preview route reads the path only from session metadata, checks the expected parent/child flow and child guardian authorization, serves local with no-store/nosniff, and permits guest parent-registration preview only in its own session. Change child finalization, cleanup, and rejected replacement to local. Add an admin-only child document route; replace the public asset URL in the existing queue with that route. Transitional admin read may fall back to public solely until the command has moved a document, but no public URL is generated.

~~~php
$metadata = app(RegistrationTempUploadService::class)->get($flow, $step);
abort_unless($metadata && in_array([$flow, $step], [
    ['parent', 'government_id'], ['child', 'verification_document'],
], true), 404);
$path = (string) $metadata['path'];
abort_unless(Storage::disk('local')->exists($path), 404);
return response()->file(Storage::disk('local')->path($path), [
    'Cache-Control' => 'private, no-store',
    'X-Content-Type-Options' => 'nosniff',
]);
~~~

- [ ] **Step 4: Add idempotent public-file migration command.** Inventory all public registration-temp files and DB-referenced parent ID front/back and child verification paths under allowlisted prefixes. Reject absolute paths, traversal, and paths outside registration-temp/, parent-verifications/, guardian-verifications/, and child-verifications/. Default run reports counts and conflicts without writes. With --apply, copy each missing private file to the same relative path, compare SHA-256 via the local filesystem paths, delete its public source only when hashes match, and report missing/conflicting paths without deleting them. Existing private paths are left alone; a second run is a no-op. Do not run this command during a schema migration. The copy/verify/delete decision is:

~~~php
if ($private->exists($path)) {
    if (hash_file('sha256', $private->path($path))
        !== hash_file('sha256', $public->path($path))) {
        $conflicts++;
        continue;
    }
} else {
    $stream = $public->readStream($path);
    try {
        if (! $stream || ! $private->writeStream($path, $stream)) {
            $failures++;
            continue;
        }
    } finally {
        if (is_resource($stream)) fclose($stream);
    }
    if (hash_file('sha256', $private->path($path))
        !== hash_file('sha256', $public->path($path))) {
        $failures++;
        continue;
    }
}
$public->delete($path);
~~~

Protect a pre-existing private file with a different hash; never overwrite it. If a copy checksum fails, retain the public source and report it. Check the generated command output and migration counts before and after --apply. Do not run any destructive database command.
- [ ] **Step 5: Run storage and existing guardian/child tests.** Run: php artisan test tests/Feature/Identity/LegacyIdentityStorageTest.php tests/Unit/Services/RegistrationTempUploadServiceTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php. Expected: PASS; identity document URLs are authorized routes and new files exist only on local.
- [ ] **Step 6: Commit.** Stage only Task 8 files. Commit: fix: keep verification documents private.

## Task 9: Verify integrated flows and record results

**Files:** Add docs/superpowers/verification/2026-09-24-learner-identity-verification.md; refine only a file with a concrete test or device failure found in this task.

**Interfaces:** No new production interface. The verification document records commands, pass/fail results, manual device findings, and any remaining limitation.

- [ ] **Step 1: Check test isolation and schema before running.** Compare phpunit.xml DB_DATABASE=cc_db_test with the local development DB name without printing secrets. Inspect current tables and php artisan migrate --pretend before any normal incremental php artisan migrate. Never use development data as test fixtures.
- [ ] **Step 2: Run the new feature tests.** Run: php artisan test tests/Feature/Identity. Expected: PASS, including age 12/13/17/18, new/old cohort, adult transition, all document types, email/access gates, unauthorized preview/decision/replacement, invalid upload, resubmission, notifications, and private migration.
- [ ] **Step 3: Run relevant existing regressions.** Run: php artisan test tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/EmailVerificationTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/WizardStepperTest.php. Expected: PASS with no child, guardian, email, stepper, or invitation regression.
- [ ] **Step 4: Verify JavaScript, build, routes, and files.** Run: node --test tests/JavaScript/identity-selfie.test.mjs. Run: npm run build. Run: php artisan route:list --name=learner.identity. Expected: PASS, build success, and only the three owner routes. In an isolated storage fixture run identity:move-legacy-documents dry-run, then --apply, then dry-run again; expected final report has no public referenced evidence or conflicts.
- [ ] **Step 5: Perform manual device and accessibility checks.** On mobile, tablet, laptop webcam, and desktop without a webcam: confirm capture/upload, denial fallback, preview, retake, cancel, final submit, no duplicate upload, keyboard focus, screen-reader labels, visible status, and no horizontal scroll. Confirm no network request sends evidence to a third-party AI endpoint.
- [ ] **Step 6: Write verification report and commit.** Record actual command output summaries, test DB name, device/browser checks, public-to-private migration counts, unresolved conflicts, and any known limits in docs/superpowers/verification/2026-09-24-learner-identity-verification.md. Stage only task files and commit: docs: verify learner identity flows.
