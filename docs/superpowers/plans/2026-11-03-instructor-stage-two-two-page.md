# Instructor Stage Two Two-Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split the direct instructor Stage Two application into a saved-document page and a teaching-expertise page while preserving the existing draft, storage, and final approval workflow.

**Architecture:** Keep `InstructorApplicationService::saveDraft()` and the existing `submitApplication()` as the persistence boundary. Add one document-step request/action that validates and saves required files before redirecting to a new expertise GET page; add a separate expertise draft action for the second page. Render both pages with the existing auth split layout, a small two-step stage-two stepper, and a reusable Alpine upload-card partial.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade, Alpine.js, Tailwind CSS, Eloquent, PHPUnit 11, existing private local document storage.

**Approved design:** [`docs/superpowers/specs/2026-11-03-instructor-stage-two-two-page-design.md`](../specs/2026-11-03-instructor-stage-two-two-page-design.md)

## Global Constraints

- Work in the current dirty checkout; stage only files owned by this feature.
- Do not reset, wipe, truncate, recreate, or destructively seed any database.
- Do not add migrations, new application statuses, or provider integrations.
- Preserve the current Stage One identity gate, private document disk, file types, 5 MB limits, admin review data, and final role-activation flow.
- Use the existing `InstructorApplicationService` for all draft file persistence.
- Keep direct applicant authorization behind the existing authenticated, verified, and feature-enabled route group.
- Follow test-first red/green cycles for the new server behavior.

---

### Task 1: Add failing coverage for the two-page contract

**Files:**
- Create: `tests/Feature/InstructorApplication/InstructorApplicationTwoPageTest.php`
- Inspect: `tests/Feature/InstructorApplication/InstructorApplicationDraftTest.php`

**Interfaces:**
- Consumes the existing applicant factory setup, `RoleSyncService`, `InstructorIdentityVerification`, `InstructorApplication`, and `UploadedFile` helpers.
- Produces the expected route names and persistence assertions used by later tasks.

- [x] **Step 1: Write the failing feature tests**

Add tests for these behaviors:

```php
public function test_document_step_requires_missing_required_files(): void
{
    $user = $this->approvedApplicant();

    $this->actingAs($user)
        ->post(route('instructor-application.application.documents.save'), [])
        ->assertSessionHasErrors(['clearance', 'cv_resume']);

    $this->assertDatabaseMissing('instructor_applications', ['user_id' => $user->id]);
}

public function test_document_step_saves_files_and_redirects_to_expertise(): void
{
    $user = $this->approvedApplicant();

    $this->actingAs($user)
        ->post(route('instructor-application.application.documents.save'), [
            'clearance' => UploadedFile::fake()->create('clearance.pdf', 20, 'application/pdf'),
            'cv_resume' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
        ])
        ->assertRedirect(route('instructor-application.application.expertise'));

    $this->assertDatabaseHas('instructor_applications', [
        'user_id' => $user->id,
        'status' => 'draft',
    ]);
}

public function test_expertise_requires_a_saved_document_draft(): void
{
    $user = $this->approvedApplicant();

    $this->actingAs($user)
        ->get(route('instructor-application.application.expertise'))
        ->assertRedirect(route('instructor-application.application.edit'))
        ->assertSessionHas('status');
}

public function test_expertise_page_can_save_and_finally_submit_using_saved_documents(): void
{
    $user = $this->approvedApplicant();
    $admin = User::factory()->create(['role' => 'admin']);
    app(RoleSyncService::class)->assignPrimaryRole($admin, 'admin');

    $this->actingAs($user)->post(route('instructor-application.application.documents.save'), [
        'clearance' => UploadedFile::fake()->create('clearance.pdf', 20, 'application/pdf'),
        'cv_resume' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
    ])->assertRedirect(route('instructor-application.application.expertise'));

    $this->actingAs($user)->post(route('instructor-application.application.expertise.save-draft'), [
        'educational_background' => 'college_graduate',
        'bio' => 'Saved teaching background',
    ])->assertRedirect(route('instructor-application.application.expertise'));

    $this->actingAs($user)->post(route('instructor-application.application.submit'), [
        'educational_background' => 'college_graduate',
        'teaching_credential' => UploadedFile::fake()->create('teaching.pdf', 20, 'application/pdf'),
        'confirmation' => '1',
    ])->assertRedirect(route('instructor-application.application.submitted'));

    $this->assertDatabaseHas('instructor_applications', [
        'user_id' => $user->id,
        'status' => 'pending',
    ]);
}
```

Use a private `approvedApplicant()` helper that creates the existing applicant role and an approved `InstructorIdentityVerification`, matching the current draft test setup. Do not assert raw storage paths or provider data.

- [x] **Step 2: Run the new test and confirm the expected red failure**

Run:

```powershell
vendor\bin\phpunit --do-not-cache-result tests/Feature/InstructorApplication/InstructorApplicationTwoPageTest.php
```

Expected: failure because the document-save and expertise routes/actions/views do not yet exist.

---

### Task 2: Add document-step validation, controller actions, and routes

**Files:**
- Create: `app/Http/Requests/InstructorApplication/SaveInstructorApplicationDocumentsRequest.php`
- Modify: `app/Http/Controllers/InstructorApplication/ApplicationController.php`
- Modify: `routes/auth.php`

**Interfaces:**
- Consumes the existing `InstructorApplicationService::saveDraft()`, `InstructorApplication` paths, and identity access query.
- Produces `instructor-application.application.documents.save`, `instructor-application.application.expertise`, and `instructor-application.application.expertise.save-draft` route behavior.

- [x] **Step 1: Add the document request with server-side required-file checks**

Implement `SaveInstructorApplicationDocumentsRequest` with:

```php
public function authorize(): bool
{
    return (bool) $this->user()?->can('saveDraft', InstructorApplication::class);
}

public function rules(): array
{
    return [
        'government_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        'clearance' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
    ];
}
```

Add an `after` validator that loads the current draft, skips Government ID when the user has an access-approved current instructor identity, and adds field errors when a required file is absent from both the request and the existing draft path. Use the existing messages: Government-issued ID, NBI/police clearance, and CV/resume.

- [x] **Step 2: Add controller actions without duplicating service persistence**

Keep `edit()` as the document page and return the existing `form` view with the loaded user, draft/pending application, and `requiresGovernmentId` value.

Add:

```php
public function saveDocuments(SaveInstructorApplicationDocumentsRequest $request): RedirectResponse
{
    $this->service->saveDraft($request->user(), $request->validated());

    return redirect()->route('instructor-application.application.expertise')
        ->with('status', 'Required documents saved. Continue with your teaching expertise.');
}

public function expertise(Request $request): View|RedirectResponse
{
    $user = $request->user();
    if (! $user->can('view', InstructorApplication::class)) {
        return redirect()->route('instructor-application.dashboard')
            ->with('error', 'Complete Stage One identity approval before starting the instructor application.');
    }

    $application = $user->instructorApplications()->whereIn('status', ['draft', 'pending'])->latest('id')->first();
    if ($application?->status === 'pending') {
        return redirect()->route('instructor-application.application.submitted');
    }

    if (! $this->hasRequiredDocuments($user, $application)) {
        return redirect()->route('instructor-application.application.edit')
            ->with('status', 'Save your required documents before continuing.');
    }

    return view('instructor-application.expertise', [
        'user' => $user->load('profile'),
        'application' => $application,
    ]);
}

public function saveExpertiseDraft(SaveInstructorApplicationDraftRequest $request): RedirectResponse
{
    $this->service->saveDraft($request->user(), $request->validated());

    return redirect()->route('instructor-application.application.expertise')
        ->with('status', 'Your teaching expertise draft was saved.');
}
```

Add a private `hasRequiredDocuments(User $user, ?InstructorApplication $application): bool` helper that checks clearance and CV paths plus either an access-approved identity or a Government ID path. Keep the existing `saveDraft()` action for compatibility with older callers.

- [x] **Step 3: Add routes inside the existing auth/verified/feature-enabled group**

Inside the current application prefix, add:

```php
Route::post('/documents', [InstructorApplicationController::class, 'saveDocuments'])->name('documents.save');
Route::get('/expertise', [InstructorApplicationController::class, 'expertise'])->name('expertise');
Route::post('/expertise/draft', [InstructorApplicationController::class, 'saveExpertiseDraft'])->name('expertise.save-draft');
```

- [x] **Step 4: Run the feature test and confirm the server contract turns green after views are temporarily allowed to resolve**

Run the focused test after Task 3 completes; before Task 3, expected failures should be limited to missing Blade views.

---

### Task 3: Build the two-page UI and reusable upload card

**Files:**
- Create: `resources/views/instructor-application/expertise.blade.php`
- Create: `resources/views/instructor-application/partials/stage-two-stepper.blade.php`
- Create: `resources/views/instructor-application/partials/upload-field.blade.php`
- Modify: `resources/views/instructor-application/form.blade.php`

**Interfaces:**
- Consumes controller view data (`user`, `application`, `requiresGovernmentId`) and the new route names.
- Produces the document page, expertise page, accessible two-step navigation, and selected/saved file states.

- [x] **Step 1: Add the two-step stage tracker partial**

Render a compact `nav` with `aria-label="Stage Two application steps"`, two numbered circles, connector state, and labels. On the expertise page, make the completed Required documents step an accessible link to `application.edit`; on the document page, make it the active non-link. Use the existing purple gradient/check/active-ring treatment from `x-wizard-stepper`.

- [x] **Step 2: Add the upload-field partial**

Accept `id`, `name`, `label`, `accept`, `help`, `required`, and `existingName` variables. Render a hidden file input tied to a labelled purple dashed drop zone with keyboard focus styling. Use local Alpine state for drag-over, selected file name/type/size, clearing a newly selected file, and a saved-file status. Never render a stored path or private document URL.

- [x] **Step 3: Refactor `form.blade.php` into the required-documents page**

Keep the existing auth split layout and personal-information summary. Add the stage-two stepper below the page heading. Replace native file controls with three upload cards and submit to `documents.save` using **Save and continue**. Keep the Government ID explanatory success panel when Stage One covers it. Preserve the current error summary, live submit status, and loading state.

- [x] **Step 4: Add `expertise.blade.php`**

Use the same split layout, personal-information summary, and stepper. Render education, conditional “If other, specify,” professional background, and three optional Tier 2 upload cards. Submit draft saves to `expertise.save-draft`; submit final application to the existing `application.submit` route with the confirmation checkbox. Add **Back to required documents** and clear helper text that at least one Tier 2 document is required.

- [x] **Step 5: Run Blade compilation and the new feature test**

Run:

```powershell
php artisan view:cache
vendor\bin\phpunit --do-not-cache-result tests/Feature/InstructorApplication/InstructorApplicationTwoPageTest.php
```

Expected: Blade cache succeeds and all new two-page tests pass.

---

### Task 4: Regression checks and focused cleanup

**Files:**
- Inspect: `resources/views/instructor-application/dashboard.blade.php`, existing instructor application tests, and the focused diff.
- Modify only if a regression is proven.

- [x] **Step 1: Run the existing instructor application workflow tests**

```powershell
vendor\bin\phpunit --do-not-cache-result tests/Feature/InstructorApplication/InstructorApplicationDraftTest.php tests/Feature/InstructorApplication/InstructorIdentityWorkflowTest.php
```

Expected: all tests pass without altering existing Stage One behavior.

- [x] **Step 2: Run the JavaScript unit tests if the upload partial adds shared helpers**

```powershell
node --test tests/JavaScript/instructor-application.test.mjs
```

If no shared JavaScript helper changes, record that this suite was not needed for the Blade-only upload state.

- [x] **Step 3: Run owned-file whitespace checks and inspect the diff**

```powershell
git diff --check -- app/Http/Controllers/InstructorApplication/ApplicationController.php app/Http/Requests/InstructorApplication/SaveInstructorApplicationDocumentsRequest.php routes/auth.php resources/views/instructor-application/form.blade.php resources/views/instructor-application/expertise.blade.php resources/views/instructor-application/partials/stage-two-stepper.blade.php resources/views/instructor-application/partials/upload-field.blade.php tests/Feature/InstructorApplication/InstructorApplicationTwoPageTest.php
git diff --stat -- app/Http/Controllers/InstructorApplication/ApplicationController.php app/Http/Requests/InstructorApplication/SaveInstructorApplicationDocumentsRequest.php routes/auth.php resources/views/instructor-application tests/Feature/InstructorApplication/InstructorApplicationTwoPageTest.php
```

- [x] **Step 4: Report verification boundaries**

Report passed PHPUnit/View checks separately from browser visual QA, live Persona/provider behavior, and any unrelated dirty-worktree warnings. Do not claim screenshot-level layout verification without running the browser.
