# Admin Content Governance Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build an admin-governed content lifecycle where instructor-authored module packages require admin approval before learner publication, while admins can also author and publish admin-owned modules directly.

**Architecture:** Extend the existing instructor content domain instead of replacing it. Add moderation lifecycle records, approval/rejection services, learner-facing published-version resolution, and admin review UI on top of the current Laravel Blade + service-layer structure. Keep route ownership split between `routes/instructor.php`, `routes/admin.php`, and learner/public flows.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS, PHPUnit, Spatie Laravel Permission

---

## Implementation Notes

- Follow TDD strictly: test first, confirm failure, implement minimal code, rerun, then commit.
- Keep controllers thin. Put moderation logic in a new service such as `app/Services/ContentGovernanceService.php`.
- Prefer Form Requests for new moderation and submission actions.
- Do not let instructor edits directly overwrite learner-visible approved content once moderation is introduced.
- Reuse the existing admin activity logging pattern through `app/Services/AdminActivityLogService.php`.

## Task 1: Add Moderation Schema Foundation

**Files:**
- Create: `database/migrations/2026_03_27_000001_create_module_revisions_table.php`
- Create: `database/migrations/2026_03_27_000002_create_module_review_requests_table.php`
- Create: `database/migrations/2026_03_27_000003_add_content_governance_columns_to_modules_table.php`
- Test: `tests/Feature/Admin/AdminContentGovernanceSchemaTest.php`

**Step 1: Write the failing test**

Create a migration test that asserts the schema supports:
- module authorship and publication ownership
- a stable published revision pointer
- revision snapshots
- review request states and feedback

Example assertions:

```php
public function test_content_governance_schema_exists(): void
{
    $this->assertTrue(Schema::hasTable('module_revisions'));
    $this->assertTrue(Schema::hasTable('module_review_requests'));

    $this->assertTrue(Schema::hasColumns('modules', [
        'content_owner_type',
        'published_revision_id',
        'published_by_admin_id',
    ]));
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=content_governance_schema_exists`

Expected: FAIL because the tables and columns do not exist yet.

**Step 3: Write minimal implementation**

Add migrations that support the approved design:
- `modules`
  - `content_owner_type` string or enum-like string with values such as `instructor` and `admin`
  - `published_revision_id` nullable foreign key to `module_revisions`
  - `published_by_admin_id` nullable foreign key to `users`
  - optional moderation helper field such as `current_review_status`
- `module_revisions`
  - `id`
  - `module_id`
  - `revision_number`
  - `snapshot_payload` JSON
  - `submitted_by`
  - `status` with states like `draft`, `in_review`, `needs_revision`, `approved`
  - `submitted_at`, `reviewed_at`
  - `reviewed_by`
  - `review_feedback`
- `module_review_requests`
  - `id`
  - `module_id`
  - `module_revision_id`
  - `status`
  - `submitted_by`
  - `reviewed_by`
  - `submitted_at`, `reviewed_at`
  - `feedback`

Keep the schema focused on module-package governance only. Do not create lesson/topic-specific review tables in this phase.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=content_governance_schema_exists`

Expected: PASS

**Step 5: Commit**

```bash
git add database/migrations/2026_03_27_000001_create_module_revisions_table.php database/migrations/2026_03_27_000002_create_module_review_requests_table.php database/migrations/2026_03_27_000003_add_content_governance_columns_to_modules_table.php tests/Feature/Admin/AdminContentGovernanceSchemaTest.php
git commit -m "feat: add admin content governance schema"
```

## Task 2: Add Models and Relationships

**Files:**
- Create: `app/Models/ModuleRevision.php`
- Create: `app/Models/ModuleReviewRequest.php`
- Modify: `app/Models/Module.php`
- Modify: `app/Models/User.php`
- Test: `tests/Unit/Models/ModuleGovernanceRelationshipsTest.php`

**Step 1: Write the failing test**

Add model relationship tests for:
- `Module::revisions()`
- `Module::reviewRequests()`
- `Module::publishedRevision()`
- `Module::publisher()`
- `User::submittedModuleRevisions()`
- `User::reviewedModuleRevisions()`

Example:

```php
public function test_module_exposes_revision_and_review_relationships(): void
{
    $module = Module::factory()->create();

    $this->assertInstanceOf(HasMany::class, $module->revisions());
    $this->assertInstanceOf(HasMany::class, $module->reviewRequests());
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=module_exposes_revision_and_review_relationships`

Expected: FAIL because the models and relationships do not exist yet.

**Step 3: Write minimal implementation**

Create:
- `App\Models\ModuleRevision`
- `App\Models\ModuleReviewRequest`

Update `App\Models\Module` to add:
- fillable/casts for governance columns
- `revisions()`
- `reviewRequests()`
- `publishedRevision()`
- `publisher()`

Update `App\Models\User` to add:
- `moduleReviewsSubmitted()`
- `moduleReviewsReviewed()`
- optional authoring helpers such as `authoredModules()`

Keep naming consistent with current model style.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=module_exposes_revision_and_review_relationships`

Expected: PASS

**Step 5: Commit**

```bash
git add app/Models/ModuleRevision.php app/Models/ModuleReviewRequest.php app/Models/Module.php app/Models/User.php tests/Unit/Models/ModuleGovernanceRelationshipsTest.php
git commit -m "feat: add module governance models"
```

## Task 3: Introduce the Content Governance Service

**Files:**
- Create: `app/Services/ContentGovernanceService.php`
- Modify: `app/Services/AdminActivityLogService.php`
- Test: `tests/Unit/ContentGovernanceServiceTest.php`

**Step 1: Write the failing test**

Create service tests for the three core behaviors:
- submit module package for review
- reject with required feedback
- approve and mark a revision as published

Example:

```php
public function test_submit_for_review_creates_revision_snapshot_and_review_request(): void
{
    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');

    $module = Module::factory()->create([
        'created_by' => $instructor->id,
        'content_owner_type' => 'instructor',
    ]);

    app(ContentGovernanceService::class)->submitForReview($module, $instructor);

    $this->assertDatabaseHas('module_revisions', [
        'module_id' => $module->id,
        'status' => 'in_review',
    ]);

    $this->assertDatabaseHas('module_review_requests', [
        'module_id' => $module->id,
        'status' => 'in_review',
    ]);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ContentGovernanceServiceTest`

Expected: FAIL because the service class and methods do not exist yet.

**Step 3: Write minimal implementation**

Create `ContentGovernanceService` with methods such as:
- `submitForReview(Module $module, User $actor): ModuleReviewRequest`
- `approveReview(ModuleReviewRequest $reviewRequest, User $admin, ?string $notes = null): ModuleRevision`
- `rejectReview(ModuleReviewRequest $reviewRequest, User $admin, string $feedback): ModuleRevision`
- `createRevisionSnapshot(Module $module, User $actor): ModuleRevision`

Implementation requirements:
- wrap approve/reject in transactions
- snapshot full package shape needed for module, lessons, topics, and quizzes
- set `modules.published_revision_id` on approval
- set `modules.published_by_admin_id` on approval of instructor-owned content
- log admin actions through `AdminActivityLogService`

Keep the first implementation minimal and correct. Avoid adding background jobs or notifications in this phase unless required by an existing pattern.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ContentGovernanceServiceTest`

Expected: PASS

**Step 5: Commit**

```bash
git add app/Services/ContentGovernanceService.php app/Services/AdminActivityLogService.php tests/Unit/ContentGovernanceServiceTest.php
git commit -m "feat: add content governance service"
```

## Task 4: Protect Learner Visibility with Published-Version Resolution

**Files:**
- Modify: `app/Models/Module.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/Learner/LessonController.php`
- Modify: `app/Http/Controllers/Learner/QuizController.php`
- Test: `tests/Feature/Learner/LearnerPublishedModuleVisibilityTest.php`

**Step 1: Write the failing test**

Add a learner feature test that proves:
- learners do not see instructor drafts
- learners continue seeing the old approved revision while a new one is pending

Example:

```php
public function test_learner_only_sees_approved_published_module_versions(): void
{
    $learner = User::factory()->create(['role' => 'learner']);
    $module = Module::factory()->create([
        'is_published' => true,
        'content_owner_type' => 'instructor',
    ]);

    // Seed one approved revision and one in_review revision here.

    $this->actingAs($learner)
        ->get(route('learner.modules.show', $module))
        ->assertOk()
        ->assertSee('Approved Title')
        ->assertDontSee('Pending Revision Title');
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=learner_only_sees_approved_published_module_versions`

Expected: FAIL because learner queries still use direct module state.

**Step 3: Write minimal implementation**

Add helper methods/scopes in `Module` and update learner-facing controllers so learner reads resolve:
- module is learner-visible only when it has an approved published revision
- content reads prefer `publishedRevision` data over current in-progress authoring state where applicable

If full payload hydration from `snapshot_payload` is too large for the first pass, implement a minimal strategy that gates visibility correctly first, then expand read-side composition in the next task if necessary.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=learner_only_sees_approved_published_module_versions`

Expected: PASS

**Step 5: Commit**

```bash
git add app/Models/Module.php app/Http/Controllers/Learner/ModuleController.php app/Http/Controllers/Learner/LessonController.php app/Http/Controllers/Learner/QuizController.php tests/Feature/Learner/LearnerPublishedModuleVisibilityTest.php
git commit -m "feat: gate learner content by approved revisions"
```

## Task 5: Add Instructor Submission and Resubmission Workflow

**Files:**
- Create: `app/Http/Requests/Instructor/SubmitModuleForReviewRequest.php`
- Create: `app/Http/Controllers/Instructor/ModuleReviewController.php`
- Modify: `routes/instructor.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Test: `tests/Feature/Instructor/InstructorModuleReviewSubmissionTest.php`

**Step 1: Write the failing test**

Cover:
- instructor can submit a full module package for review
- rejected module can be resubmitted
- instructor cannot directly publish learner-visible content after moderation is active

Example:

```php
public function test_instructor_can_submit_module_for_admin_review(): void
{
    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');

    $module = Module::factory()->create([
        'created_by' => $instructor->id,
        'content_owner_type' => 'instructor',
    ]);

    $this->actingAs($instructor)
        ->post(route('instructor.modules.review.submit', $module))
        ->assertRedirect();

    $this->assertDatabaseHas('module_review_requests', [
        'module_id' => $module->id,
        'status' => 'in_review',
    ]);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorModuleReviewSubmissionTest`

Expected: FAIL because the route/controller/request do not exist yet.

**Step 3: Write minimal implementation**

Create:
- `Instructor\ModuleReviewController`
- `SubmitModuleForReviewRequest`

Add routes in `routes/instructor.php` such as:
- `POST /instructor/modules/{module}/review/submit`
- optional `POST /instructor/modules/{module}/review/resubmit`

Update `Instructor\ModuleController` so direct `is_published` toggling no longer bypasses the review workflow for instructor-owned content. Keep admin-owned publishing behavior out of this controller.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorModuleReviewSubmissionTest`

Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Requests/Instructor/SubmitModuleForReviewRequest.php app/Http/Controllers/Instructor/ModuleReviewController.php app/Http/Controllers/Instructor/ModuleController.php routes/instructor.php tests/Feature/Instructor/InstructorModuleReviewSubmissionTest.php
git commit -m "feat: add instructor module review submission flow"
```

## Task 6: Add Admin Review Queue and Decision Endpoints

**Files:**
- Create: `app/Http/Requests/Admin/RejectModuleReviewRequest.php`
- Create: `app/Http/Controllers/Admin/ContentReviewController.php`
- Modify: `routes/admin.php`
- Modify: `app/Http/Controllers/Admin/DashboardController.php`
- Test: `tests/Feature/Admin/AdminContentReviewWorkflowTest.php`

**Step 1: Write the failing test**

Cover:
- admin can see pending module review queue
- admin can approve a review request
- admin can reject with required feedback
- non-admin users cannot access the queue

Example:

```php
public function test_admin_can_approve_instructor_module_submission(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $reviewRequest = ModuleReviewRequest::factory()->create([
        'status' => 'in_review',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.content-reviews.approve', $reviewRequest))
        ->assertRedirect();

    $this->assertDatabaseHas('module_review_requests', [
        'id' => $reviewRequest->id,
        'status' => 'approved',
    ]);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminContentReviewWorkflowTest`

Expected: FAIL because the admin content review routes and controller do not exist yet.

**Step 3: Write minimal implementation**

Create:
- `Admin\ContentReviewController`
- `RejectModuleReviewRequest`

Add admin routes in `routes/admin.php` for:
- review queue index
- review detail page
- approve action
- reject action

Update `Admin\DashboardController` to include pending content review count if needed for the dashboard card.

Use `ContentGovernanceService` for approve/reject logic. Do not duplicate business rules inside the controller.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminContentReviewWorkflowTest`

Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Requests/Admin/RejectModuleReviewRequest.php app/Http/Controllers/Admin/ContentReviewController.php app/Http/Controllers/Admin/DashboardController.php routes/admin.php tests/Feature/Admin/AdminContentReviewWorkflowTest.php
git commit -m "feat: add admin module review workflow"
```

## Task 7: Build Instructor and Admin Governance UI

**Files:**
- Create: `resources/views/admin/content-reviews/index.blade.php`
- Create: `resources/views/admin/content-reviews/show.blade.php`
- Create: `resources/views/admin/content-reviews/_approve-modal.blade.php`
- Create: `resources/views/admin/content-reviews/_reject-modal.blade.php`
- Modify: `resources/views/admin/dashboard.blade.php`
- Modify: `resources/views/instructor/modules/index.blade.php`
- Modify: `resources/views/instructor/modules/show.blade.php`
- Modify: `resources/views/instructor/modules/edit.blade.php`
- Test: `tests/Feature/Admin/AdminContentReviewUiTest.php`
- Test: `tests/Feature/Instructor/InstructorModuleGovernanceUiTest.php`

**Step 1: Write the failing tests**

Add UI tests that assert:
- admin review queue renders submission status and actions
- admin show page renders author attribution and feedback area
- instructor module pages render moderation status, review feedback, and submit/resubmit actions

Example:

```php
public function test_admin_review_queue_displays_pending_submissions(): void
{
    $admin = $this->createAdmin();

    $this->actingAs($admin)
        ->get(route('admin.content-reviews.index'))
        ->assertOk()
        ->assertSee('Pending Content Reviews');
}
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AdminContentReviewUiTest`

Run: `php artisan test --filter=InstructorModuleGovernanceUiTest`

Expected: FAIL because the views do not render governance elements yet.

**Step 3: Write minimal implementation**

Build:
- admin review queue list using the current admin visual language
- admin review detail page for full package inspection
- approval/rejection modal partials
- instructor-side moderation badges, feedback panel, and submit/resubmit controls
- dashboard card linking admins to pending content reviews

Re-use existing admin/instructor table and card styling conventions. Avoid redesigning unrelated layout patterns.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=AdminContentReviewUiTest`

Run: `php artisan test --filter=InstructorModuleGovernanceUiTest`

Expected: PASS

**Step 5: Commit**

```bash
git add resources/views/admin/content-reviews/index.blade.php resources/views/admin/content-reviews/show.blade.php resources/views/admin/content-reviews/_approve-modal.blade.php resources/views/admin/content-reviews/_reject-modal.blade.php resources/views/admin/dashboard.blade.php resources/views/instructor/modules/index.blade.php resources/views/instructor/modules/show.blade.php resources/views/instructor/modules/edit.blade.php tests/Feature/Admin/AdminContentReviewUiTest.php tests/Feature/Instructor/InstructorModuleGovernanceUiTest.php
git commit -m "feat: add content governance review interfaces"
```

## Task 8: Enable Admin-Owned Module Authoring

**Files:**
- Create: `app/Http/Controllers/Admin/AdminModuleController.php`
- Modify: `routes/admin.php`
- Create: `resources/views/admin/modules/index.blade.php`
- Create: `resources/views/admin/modules/create.blade.php`
- Create: `resources/views/admin/modules/show.blade.php`
- Create: `resources/views/admin/modules/edit.blade.php`
- Modify: `app/Models/Module.php`
- Test: `tests/Feature/Admin/AdminModuleAuthoringTest.php`

**Step 1: Write the failing test**

Cover:
- admin can create a module credited to admin ownership
- admin-authored module is publishable from admin flows
- module stores correct attribution metadata

Example:

```php
public function test_admin_can_create_platform_owned_module(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.modules.store'), [
            'title' => 'Platform Module',
            'description' => 'Admin-owned content',
            'age_bracket' => 'teens',
            'enrollment_mode' => 'auto',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('modules', [
        'title' => 'Platform Module',
        'created_by' => $admin->id,
        'content_owner_type' => 'admin',
    ]);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminModuleAuthoringTest`

Expected: FAIL because the admin module authoring controller and routes do not exist yet.

**Step 3: Write minimal implementation**

Create `AdminModuleController` using the existing instructor module flow as a reference, but adapt it for:
- admin route namespace
- `content_owner_type = 'admin'`
- admin attribution
- direct admin publication path

Prefer extracting shared persistence helpers into `ContentGovernanceService` or a small content authoring service instead of copying large controller blocks.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminModuleAuthoringTest`

Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/AdminModuleController.php routes/admin.php resources/views/admin/modules/index.blade.php resources/views/admin/modules/create.blade.php resources/views/admin/modules/show.blade.php resources/views/admin/modules/edit.blade.php app/Models/Module.php tests/Feature/Admin/AdminModuleAuthoringTest.php
git commit -m "feat: add admin platform module authoring"
```

## Task 9: Add Audit and Regression Coverage

**Files:**
- Modify: `tests/Feature/Admin/AdminActivityLogTest.php`
- Create: `tests/Feature/Admin/AdminContentGovernanceAuditTest.php`
- Modify: `tests/Feature/Instructor/InstructorModuleStatusLifecycleTest.php`
- Modify: `tests/Feature/Learner/ModuleAgeBracketUpdateVisibilityTest.php`

**Step 1: Write the failing tests**

Add assertions for:
- approval writes admin activity logs
- rejection writes admin activity logs with feedback context
- instructor lifecycle tests now reflect moderation instead of direct live publication for instructor-owned content
- learner visibility regressions stay protected

Example:

```php
public function test_admin_approval_creates_activity_log_record(): void
{
    $admin = $this->createAdmin();

    // Seed pending review request here.

    $this->actingAs($admin)
        ->post(route('admin.content-reviews.approve', $reviewRequest))
        ->assertRedirect();

    $this->assertDatabaseHas('admin_activity_logs', [
        'admin_user_id' => $admin->id,
        'action' => 'content_reviews.approve',
    ]);
}
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=AdminContentGovernanceAuditTest`

Run: `php artisan test --filter=InstructorModuleStatusLifecycleTest`

Expected: FAIL until the new lifecycle behavior and logging are wired correctly.

**Step 3: Write minimal implementation**

Update logging and regression expectations:
- admin approvals log action like `content_reviews.approve`
- admin rejections log action like `content_reviews.reject`
- instructor-owned modules should no longer default to learner-visible publication if governance is active

Adjust any existing test helpers or fixtures needed to reflect the approved design.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=AdminContentGovernanceAuditTest`

Run: `php artisan test --filter=InstructorModuleStatusLifecycleTest`

Expected: PASS

**Step 5: Commit**

```bash
git add tests/Feature/Admin/AdminActivityLogTest.php tests/Feature/Admin/AdminContentGovernanceAuditTest.php tests/Feature/Instructor/InstructorModuleStatusLifecycleTest.php tests/Feature/Learner/ModuleAgeBracketUpdateVisibilityTest.php
git commit -m "test: cover content governance audit and regressions"
```

## Task 10: Full Verification and Documentation Pass

**Files:**
- Modify: `docs/plans/2026-03-27-admin-content-governance-design.md`
- Create or Modify: `docs/changelogs/2026-03-27-admin-content-governance.md`
- Test: existing touched feature and unit tests

**Step 1: Write the failing verification target**

There is no new test file here. The failure condition is any red test in the impacted suite.

Verification suite:
- `php artisan test --filter=AdminContentGovernance`
- `php artisan test --filter=AdminModuleAuthoring`
- `php artisan test --filter=InstructorModuleReviewSubmission`
- `php artisan test --filter=LearnerPublishedModuleVisibility`

Then run the broader regression pass:
- `php artisan test`

**Step 2: Run tests to verify current status**

Run the commands above.

Expected: all targeted tests PASS, then full suite PASS.

**Step 3: Write minimal implementation**

If anything fails:
- fix the smallest issue in the owning layer
- rerun the focused test first
- rerun the full suite last

Then document:
- final lifecycle states
- admin review entry points
- admin-authored module behavior
- notable migration or rollout notes

**Step 4: Run test suite to verify it passes**

Run: `php artisan test`

Expected: PASS

**Step 5: Commit**

```bash
git add docs/plans/2026-03-27-admin-content-governance-design.md docs/changelogs/2026-03-27-admin-content-governance.md
git commit -m "docs: finalize admin content governance rollout notes"
```

## Rollout Order

Implement in this exact order:
1. schema
2. models
3. service layer
4. learner visibility gate
5. instructor submission flow
6. admin review flow
7. UI
8. admin authoring
9. audit/regression coverage
10. full verification

## Open Implementation Choices to Resolve During Execution

- Whether `snapshot_payload` is enough for the first release or whether a deeper per-entity revision table is needed immediately
- Whether `current_review_status` belongs directly on `modules` or can be derived from revision/request records
- Whether admin-owned content should still create a `module_revision` on publish for read consistency

Default recommendation during execution:
- use `snapshot_payload` for v1
- keep one derivable source of truth where possible
- create revision records for admin-owned publication too, so learner reads stay consistent

## Manual QA Checklist

- Instructor creates a module, adds lessons/topics/quiz, and submits for review
- Admin sees the module in the review queue
- Admin rejects with feedback and instructor sees the feedback
- Instructor edits and resubmits
- Admin approves and learner can see the module
- Instructor edits an already approved module and learner still sees the old approved version until re-approval
- Admin creates a platform-owned module and publishes it under admin credit
- Admin dashboard shows content review work without breaking existing instructor application metrics

