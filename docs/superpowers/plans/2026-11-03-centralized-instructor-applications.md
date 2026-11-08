# Centralized Instructor Applications Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task with verification checkpoints.

**Goal:** Make one admin Instructor Applications workspace show and moderate both Stage One identity cases and Stage Two instructor applications, including legacy Stage Two records without an identity case.

**Architecture:** Keep the existing identity controller/service as the Stage One authority and the existing instructor application service as the Stage Two authority. Build a read-only queue projection from current identity cases plus standalone applications, render Stage Two through a reusable review partial, and retain old named routes as redirects or compatibility detail routes. No schema changes or copied lifecycle state.

**Tech Stack:** Laravel controllers, FormRequests, Eloquent, Blade, Alpine.js, PHPUnit feature tests.

## Global Constraints

- Persona results remain evidence for manual review; they never approve an instructor or grant instructor access by themselves.
- Stage One approval remains required before a direct applicant can receive final Stage Two approval.
- Only the existing Stage Two approval service can activate the instructor role.
- Existing private document and Persona evidence authorization, no-store response headers, validation, audit, notification, and locking behavior remain unchanged.
- No database reset, destructive migration, data copy, or new persisted lifecycle state is needed.
- Stage One actions require `review instructor identities`; Stage Two actions and document access require `review instructor applications`.

---

### Task 1: Add failing coverage for the unified queue and combined review page

**Files:**
- Modify: `tests/Feature/Admin/InstructorIdentityVerificationAdminTest.php`
- Modify: `tests/Feature/Admin/AdminInstructorApplicationsUiTest.php`
- Create: `tests/Feature/Admin/CentralizedInstructorApplicationsTest.php`

**Interfaces:**
- Consumes: existing `InstructorIdentityVerification`, `PersonaInquiry`, and `InstructorApplication` factories/helpers.
- Produces: executable expectations for queue union, Stage Two rendering, route compatibility, and permission separation.

- [ ] **Step 1: Write the failing tests**

Add tests that create (a) an identity-only case, (b) a linked pending Stage Two application, and (c) a standalone legacy pending application. Assert the canonical identity queue is titled `Instructor Applications`, contains all applicants, and the linked identity detail renders Stage Two documents plus Stage Two moderation controls. Assert the old Stage Two index redirects to the canonical queue and the standalone application detail is reachable from it. Assert a reviewer with only `review instructor applications` can view the queue and Stage Two detail but cannot submit Stage One decisions.

Use the existing route names and this expected shape:

```php
$this->actingAs($reviewer)
    ->get(route('admin.instructor-applications.identities.index'))
    ->assertOk()
    ->assertSee('Instructor Applications')
    ->assertSee($standalone->user->email);

$this->actingAs($reviewer)
    ->get(route('admin.instructor-applications.identities.show', $case))
    ->assertOk()
    ->assertSee('Stage Two application')
    ->assertSee('NBI / Police clearance')
    ->assertSee(route('admin.instructor-applications.approve', $linked), false);

$this->actingAs($reviewer)
    ->get(route('admin.instructor-applications.index'))
    ->assertRedirect(route('admin.instructor-applications.identities.index'));
```

- [ ] **Step 2: Run the focused tests and verify RED**

Run:

```powershell
vendor\bin\phpunit --do-not-cache-result tests\Feature\Admin\CentralizedInstructorApplicationsTest.php tests\Feature\Admin\InstructorIdentityVerificationAdminTest.php tests\Feature\Admin\AdminInstructorApplicationsUiTest.php
```

Expected: failures for the missing unified queue title/rows, missing Stage Two section, and old-route redirect.

---

### Task 2: Make the identity queue the canonical unified queue

**Files:**
- Modify: `app/Http/Controllers/Admin/InstructorIdentityVerificationController.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/admin/instructor-applications/identities/index.blade.php`

**Interfaces:**
- Consumes: current identity cases and applications, with existing permission names.
- Produces: `cases`, `standaloneApplications`, and `canReviewApplications` view data; canonical queue access for either review permission.

- [ ] **Step 1: Change queue authorization without weakening action authorization**

Replace the queue/detail view guard with an admin check requiring either `review instructor identities` or `review instructor applications`. Remove only the route-level identity-only middleware from the `identities` route group. Keep all identity FormRequest authorization unchanged.

- [ ] **Step 2: Load linked applications and standalone applications**

Keep the current identity query and eager-load the applicant’s latest instructor application/review. Add a read-only query for applications whose `instructor_identity_verification_id` is null or whose linked case is superseded. Map the requested status as follows: `pending_review` → pending applications, `approved` → approved applications, `rejected` → rejected applications, and omit standalone rows for `action_required`. Pass `canReviewIdentities` and `canReviewApplications` booleans from the authenticated admin.

- [ ] **Step 3: Rename and render the unified queue**

Change visible title/subtitle/back-link copy to `Instructor Applications`. Keep the current identity table and add a clearly labeled Stage Two-only section below it for standalone applications. Link standalone rows to `admin.instructor-applications.show`. Linked identity rows continue to link to the identity detail route.

- [ ] **Step 4: Run the focused tests**

Run the Task 1 PHPUnit command. Expected: queue and permission assertions pass; Stage Two detail assertions remain RED until Task 3.

---

### Task 3: Render Stage Two on the centralized review detail

**Files:**
- Modify: `app/Http/Controllers/Admin/InstructorIdentityVerificationController.php`
- Modify: `app/Http/Controllers/Admin/InstructorApplicationController.php`
- Create: `resources/views/admin/instructor-applications/partials/stage-two-review.blade.php`
- Create: `resources/views/admin/instructor-applications/show.blade.php`
- Modify: `resources/views/admin/instructor-applications/identities/show.blade.php`
- Modify: `app/Http/Requests/ApproveInstructorApplicationRequest.php`
- Modify: `app/Http/Requests/RejectInstructorApplicationRequest.php`

**Interfaces:**
- Consumes: `InstructorApplicationService::approve/reject`, existing document route, existing identity case data.
- Produces: one reusable Stage Two display/action partial and an app-only compatibility detail view.

- [ ] **Step 1: Load the latest relevant application on identity detail**

Prefer the application linked by `instructor_identity_verification_id`; otherwise use the applicant’s latest non-draft application. Pass `canReviewApplications` and the application to the view without fetching provider secrets.

- [ ] **Step 2: Extract the Stage Two section into a partial**

Render personal/application metadata, education/professional background, private document links through `admin.instructor-applications.documents.show`, moderation history, and approve/reject controls. Only show document URLs and Stage Two forms when `canReviewApplications` is true. Use the existing rejection enum labels and default service messages; do not reimplement service logic.

- [ ] **Step 3: Add app-only detail compatibility**

Change `InstructorApplicationController@show` to authorize `review instructor applications`, redirect to the identity detail when the application has a current identity case and the reviewer can view identity cases, otherwise render `admin.instructor-applications.show` with the Stage Two partial. Change `InstructorApplicationController@index` to redirect to the canonical identity queue, preserving `status`, `search`, and a valid `focus` application by redirecting it to the app detail.

- [ ] **Step 4: Preserve central return targets for Stage Two decisions**

Accept an optional `identity_case_id` hidden field in the Stage Two forms and redirect back to that identity detail after approve/reject when the case belongs to the application. Existing callers without the field continue using the previous back redirect. FormRequest validation remains unchanged except for nullable integer validation of the hidden target.

- [ ] **Step 5: Rename identity detail copy and include Stage Two**

Change visible `Identity Review` headings to `Instructor Applications`, keep the Stage One label for clarity, and include the Stage Two partial after the identity evidence/comparison section. Keep identity controls hidden when the reviewer lacks `review instructor identities`.

- [ ] **Step 6: Run the focused tests and verify GREEN**

Run the Task 1 PHPUnit command. Expected: all new queue/detail/compatibility assertions pass and existing identity decision tests remain green.

---

### Task 4: Remove duplicate navigation and update links/counts

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Services/AdminDashboardService.php`
- Modify: `app/Notifications/InstructorApplicationSubmitted.php`

**Interfaces:**
- Consumes: canonical identity queue and existing moderation count queries.
- Produces: one visible Instructor Applications navigation item and direct Stage Two notification links.

- [ ] **Step 1: Collapse the sidebar items**

Remove the duplicate Identity Review item. Keep one `Instructor Applications` link to `admin.instructor-applications.identities.index` and display the sum of pending Stage One identity reviews and pending Stage Two applications.

- [ ] **Step 2: Collapse dashboard queue cards**

Replace the two instructor moderation queue entries with one combined entry pointing to the canonical queue. Keep the underlying count keys available for other consumers.

- [ ] **Step 3: Update Stage Two notification links**

Point `InstructorApplicationSubmitted` mail/database actions to `admin.instructor-applications.show` with the application ID. Identity notification deep links keep their existing validated identity route.

- [ ] **Step 4: Run navigation/deep-link tests**

Run:

```powershell
vendor\bin\phpunit --do-not-cache-result tests\Feature\Admin\AdminDashboardMetricsTest.php tests\Feature\VerificationNotificationNavigationTest.php tests\Feature\Admin\CentralizedInstructorApplicationsTest.php
```

Expected: one visible queue link, combined count behavior, and safe notification destinations.

---

### Task 5: Full focused verification and cleanup

**Files:**
- Modify only tests or implementation files needed to resolve verified failures.

- [ ] **Step 1: Run PHP and view checks**

```powershell
php artisan view:cache
vendor\bin\phpunit --do-not-cache-result tests\Feature\Admin\InstructorIdentityVerificationAdminTest.php tests\Feature\Admin\AdminInstructorApplicationsUiTest.php tests\Feature\Admin\CentralizedInstructorApplicationsTest.php tests\Feature\Admin\InstructorApplicationRejectionReasonTest.php tests\Feature\InstructorApplication\InstructorApplicationActivationTest.php tests\Feature\InstructorApplication\InstructorApplicationDocumentAccessTest.php
git diff --check
```

- [ ] **Step 2: Inspect the diff for scope and privacy**

Confirm no provider raw values, signed URLs, media bytes, destructive SQL, unrelated dirty files, or permission broadening were introduced. Confirm only the intended files are staged if a commit is requested.

- [ ] **Step 3: Report evidence and remaining gates**

Report exact test counts and explicitly separate unrun browser/provider-media QA from the passing PHPUnit/view checks.
