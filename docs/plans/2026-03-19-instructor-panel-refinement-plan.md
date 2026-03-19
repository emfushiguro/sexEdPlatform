# Instructor Panel Refinement Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement instructor-side workflow fixes, UX consistency updates, activation lifecycle behavior, enrollment decision notifications, and assessment monitoring improvements without regressions.

**Architecture:** Use Approach A from the approved design: implement domain behavior and persistence first, then reusable UI standards, then page-level migrations and visual updates, then monitoring/notification polish. Controllers remain thin, ownership checks stay server-enforced, and notifications are emitted via app notifications/events.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, Spatie Permission, PHPUnit.

---

## Task 1: Add instructor/refinement test scaffolding and ownership fixtures

**Files:**
- Create: `tests/Feature/Instructor/LearnerVisibilityTest.php`
- Create: `tests/Feature/Instructor/EnrollmentDecisionNotificationTest.php`
- Create: `tests/Feature/Instructor/SearchRoutingTest.php`
- Modify: `tests/TestCase.php` (only if helper methods are needed)

**Step 1: Write the failing test**
- Add tests that assert:
  - instructor sees only learners enrolled in own modules
  - reject requires reason payload and emits learner notification
  - search URLs point to show/info routes, not edit routes

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=LearnerVisibilityTest`
- Expected: FAIL due to current broad user query and route mapping

**Step 3: Write minimal implementation support helpers**
- Add minimal reusable setup helpers in test classes (module owner, enrollment status setup)

**Step 4: Run test to verify failure signal is specific**
- Run: `php artisan test --filter=EnrollmentDecisionNotificationTest`
- Expected: FAIL with missing reason fields/notification behavior

**Step 5: Commit**
- `git add tests/Feature/Instructor/LearnerVisibilityTest.php tests/Feature/Instructor/EnrollmentDecisionNotificationTest.php tests/Feature/Instructor/SearchRoutingTest.php`
- `git commit -m "test(instructor): add refinement coverage scaffolding"`

## Task 2: Add persistence for enrollment rejection reasons and audit data

**Files:**
- Create: `database/migrations/2026_03_19_000001_add_rejection_fields_to_module_enrollments_table.php`
- Modify: `app/Models/ModuleEnrollment.php`

**Step 1: Write the failing test**
- Extend rejection test to assert persisted fields:
  - rejection_reason_code
  - rejection_reason_note
  - rejected_by_instructor_id
  - rejected_at

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=EnrollmentDecisionNotificationTest`
- Expected: FAIL due to missing DB columns and fillable/casts

**Step 3: Write minimal implementation**
- Add migration columns and index on status + module_id where useful
- Add fillable/casts in `ModuleEnrollment`

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=EnrollmentDecisionNotificationTest`
- Expected: PASS for persistence assertions (notification assertions may still fail)

**Step 5: Commit**
- `git add database/migrations/2026_03_19_000001_add_rejection_fields_to_module_enrollments_table.php app/Models/ModuleEnrollment.php`
- `git commit -m "feat(enrollment): persist rejection reason and audit fields"`

## Task 3: Add learner notification classes for enrollment approval/rejection

**Files:**
- Create: `app/Notifications/Learner/EnrollmentApprovedNotification.php`
- Create: `app/Notifications/Learner/EnrollmentRejectedNotification.php`
- Modify: `app/Http/Controllers/Instructor/EnrollmentController.php`

**Step 1: Write the failing test**
- Assert learner receives in-app notification on approve and reject
- Reject notification must include module title, reason, and instructor name

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=EnrollmentDecisionNotificationTest`
- Expected: FAIL with no notification dispatched

**Step 3: Write minimal implementation**
- Implement notification classes with `toDatabase()` payload
- In `EnrollmentController@approve` and `@reject`, send notifications to learner

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=EnrollmentDecisionNotificationTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Notifications/Learner/EnrollmentApprovedNotification.php app/Notifications/Learner/EnrollmentRejectedNotification.php app/Http/Controllers/Instructor/EnrollmentController.php`
- `git commit -m "feat(enrollment): notify learners on approval/rejection decisions"`

## Task 4: Restrict instructor learners index to enrolled learners on owned modules (view-only)

**Files:**
- Modify: `app/Http/Controllers/Instructor/UserController.php`
- Modify: `resources/views/instructor/users/index.blade.php`
- Modify: `routes/instructor.php`

**Step 1: Write the failing test**
- Assert learners list excludes learners not enrolled in instructor-owned modules
- Assert create/edit routes are unavailable to instructor learners management flow

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=LearnerVisibilityTest`
- Expected: FAIL due to current global user query and resource routes

**Step 3: Write minimal implementation**
- Update `UserController@index` query with `whereHas('moduleEnrollments.module', fn...)`
- Remove `create/store/edit/update` route exposure for `instructor.users` (or hard-forbid actions and hide UI)
- Update table columns to: No, Learner Name, Role(age-group label), Modules Enrolled Count(instructor scope), Last Activity Page, View

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=LearnerVisibilityTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Http/Controllers/Instructor/UserController.php resources/views/instructor/users/index.blade.php routes/instructor.php`
- `git commit -m "feat(instructor): scope learners list and enforce view-only actions"`

## Task 5: Fix instructor search routing to details/information pages

**Files:**
- Modify: `app/Http/Controllers/Instructor/SearchController.php`
- Modify: `resources/views/instructor/dashboard.blade.php`
- Modify: `resources/views/layouts/instructor-app.blade.php` (if search UI relocation is shared here)

**Step 1: Write the failing test**
- Assert module result URL resolves to `instructor.modules.show`
- Assert lesson result URL resolves to `instructor.lessons.show`
- Assert learner result URL resolves to `instructor.users.show`

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=SearchRoutingTest`
- Expected: FAIL because current URLs point to edit/index

**Step 3: Write minimal implementation**
- Replace URL builders in `SearchController`
- Keep global search endpoint but dashboard-only UI entry point

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=SearchRoutingTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Http/Controllers/Instructor/SearchController.php resources/views/instructor/dashboard.blade.php resources/views/layouts/instructor-app.blade.php`
- `git commit -m "fix(instructor-search): route results to detail pages"`

## Task 6: Add rejection reason modal flow in module details enrolled learners table

**Files:**
- Modify: `resources/views/instructor/modules/show.blade.php`
- Modify: `app/Http/Controllers/Instructor/EnrollmentController.php`
- Create: `app/Http/Requests/Instructor/RejectEnrollmentRequest.php`

**Step 1: Write the failing test**
- Assert reject endpoint requires reason code
- Assert optional note is accepted

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=EnrollmentDecisionNotificationTest`
- Expected: FAIL on validation behavior

**Step 3: Write minimal implementation**
- Add form request for reject validation
- Update reject action to use validated payload
- Update module details table with reject modal and reason inputs

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=EnrollmentDecisionNotificationTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/instructor/modules/show.blade.php app/Http/Controllers/Instructor/EnrollmentController.php app/Http/Requests/Instructor/RejectEnrollmentRequest.php`
- `git commit -m "feat(enrollment): add validated rejection reason workflow"`

## Task 7: Standardize active/inactive fields in create/edit flows (modules, lessons, quizzes)

**Files:**
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/LessonController.php`
- Modify: `app/Http/Controllers/Instructor/QuizManagementController.php`
- Modify: `resources/views/instructor/modules/partials/module-modal.blade.php`
- Modify: `resources/views/instructor/lessons/partials/lesson-slideout.blade.php`
- Modify: `resources/views/instructor/quizzes/partials/quiz-modal.blade.php`

**Step 1: Write the failing test**
- Assert create defaults to active
- Assert edit can deactivate and persist

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=InstructorModuleStatusLifecycleTest`
- Expected: FAIL (create this test file first)

**Step 3: Write minimal implementation**
- Add/normalize validation and persistence of active status fields
- Ensure default active behavior in store actions

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=InstructorModuleStatusLifecycleTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Http/Controllers/Instructor/ModuleController.php app/Http/Controllers/Instructor/LessonController.php app/Http/Controllers/Instructor/QuizManagementController.php resources/views/instructor/modules/partials/module-modal.blade.php resources/views/instructor/lessons/partials/lesson-slideout.blade.php resources/views/instructor/quizzes/partials/quiz-modal.blade.php tests/Feature/Instructor/InstructorModuleStatusLifecycleTest.php`
- `git commit -m "feat(instructor): normalize active/inactive lifecycle in modal forms"`

## Task 8: Enforce learner-side behavior for deactivated content (hybrid policy)

**Files:**
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/Learner/LessonController.php`
- Modify: `app/Http/Controllers/QuizController.php`
- Modify: `resources/views/learn/modules/show.blade.php` (or actual learner module detail file)

**Step 1: Write the failing test**
- Assert deactivated module remains visible in enrolled/history list
- Assert lesson/quiz progression endpoints are blocked for deactivated module content

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=LearnerDeactivatedModuleBehaviorTest`
- Expected: FAIL with currently allowed progression

**Step 3: Write minimal implementation**
- Add server-side guards in learner progression actions
- Add deactivated badge/message in learner views

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=LearnerDeactivatedModuleBehaviorTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Http/Controllers/Learner/ModuleController.php app/Http/Controllers/Learner/LessonController.php app/Http/Controllers/QuizController.php resources/views/learn/modules/show.blade.php tests/Feature/Learner/LearnerDeactivatedModuleBehaviorTest.php`
- `git commit -m "feat(learner): apply hybrid access policy for deactivated modules"`

## Task 9: Remove duplicate module search bar and refine modules card metadata layout

**Files:**
- Modify: `resources/views/instructor/modules/index.blade.php`

**Step 1: Write the failing test**
- Add feature/snapshot assertion to ensure single search block and metadata placement (if snapshot tests unavailable, assert marker IDs/classes)

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=InstructorModulesIndexUiTest`
- Expected: FAIL due to duplicate search elements

**Step 3: Write minimal implementation**
- Remove duplicated search bar
- Place published status inline with description metadata row
- Remove book icon and adjust thumbnail treatment

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=InstructorModulesIndexUiTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/instructor/modules/index.blade.php tests/Feature/Instructor/InstructorModulesIndexUiTest.php`
- `git commit -m "feat(instructor-modules): simplify search and metadata presentation"`

## Task 10: Replace lesson/quiz legacy edit pages with modal workflows

**Files:**
- Modify: `resources/views/instructor/lessons/index.blade.php`
- Modify: `resources/views/instructor/quizzes/index.blade.php`
- Modify: `resources/views/instructor/lessons/edit.blade.php` (deprecate/redirect pattern)
- Modify: `resources/views/instructor/quizzes/edit.blade.php` (deprecate/redirect pattern)

**Step 1: Write the failing test**
- Assert edit action launches modal workflow path from listing pages

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=InstructorEditModalWorkflowTest`
- Expected: FAIL due to existing dedicated edit pages

**Step 3: Write minimal implementation**
- Wire modal triggers and payload hydration from index/show contexts
- Keep server routes compatible while routing UI to modal interactions

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=InstructorEditModalWorkflowTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/instructor/lessons/index.blade.php resources/views/instructor/quizzes/index.blade.php resources/views/instructor/lessons/edit.blade.php resources/views/instructor/quizzes/edit.blade.php tests/Feature/Instructor/InstructorEditModalWorkflowTest.php`
- `git commit -m "feat(instructor-ui): move lesson and quiz edits to modal workflows"`

## Task 11: Add table numbering + icon/font usability standards across instructor pages

**Files:**
- Modify: `resources/views/instructor/users/index.blade.php`
- Modify: `resources/views/instructor/lessons/index.blade.php`
- Modify: `resources/views/instructor/quizzes/index.blade.php`
- Modify: `resources/views/instructor/modules/index.blade.php`
- Modify: `resources/views/layouts/instructor-app.blade.php`

**Step 1: Write the failing test**
- Assert numbering column exists for key tables
- Assert action icon classes include standard sizing/contrast tokens

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=InstructorTableStandardsTest`
- Expected: FAIL prior to template updates

**Step 3: Write minimal implementation**
- Implement numbering calculation across pagination
- Apply standardized icon and font utility classes

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=InstructorTableStandardsTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/instructor/users/index.blade.php resources/views/instructor/lessons/index.blade.php resources/views/instructor/quizzes/index.blade.php resources/views/instructor/modules/index.blade.php resources/views/layouts/instructor-app.blade.php tests/Feature/Instructor/InstructorTableStandardsTest.php`
- `git commit -m "feat(instructor-ui): standardize table numbering and icon readability"`

## Task 12: Implement per-page deletion confirmation modals throughout instructor area

**Files:**
- Modify: `resources/views/instructor/modules/index.blade.php`
- Modify: `resources/views/instructor/lessons/index.blade.php`
- Modify: `resources/views/instructor/quizzes/index.blade.php`
- Modify: `resources/views/instructor/image-library/index.blade.php`
- Modify: `resources/views/instructor/users/index.blade.php`

**Step 1: Write the failing test**
- Assert delete actions require modal confirmation controls in rendered page

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=InstructorDeleteConfirmationTest`
- Expected: FAIL on pages still using direct delete

**Step 3: Write minimal implementation**
- Add page-specific confirm modals with cancel/confirm path
- Ensure destructive action does not fire without explicit confirm

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=InstructorDeleteConfirmationTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/instructor/modules/index.blade.php resources/views/instructor/lessons/index.blade.php resources/views/instructor/quizzes/index.blade.php resources/views/instructor/image-library/index.blade.php resources/views/instructor/users/index.blade.php tests/Feature/Instructor/InstructorDeleteConfirmationTest.php`
- `git commit -m "feat(instructor-ui): add page-level delete confirmation modals"`

## Task 13: Redesign instructor image library to theme-aligned visual gallery

**Files:**
- Modify: `resources/views/instructor/image-library/index.blade.php`
- Modify: `app/Http/Controllers/Instructor/ImageLibraryController.php` (if metadata endpoint shaping is needed)

**Step 1: Write the failing test**
- Assert gallery renders metadata panel markers and action controls

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=InstructorImageLibraryThemeTest`
- Expected: FAIL prior to layout update

**Step 3: Write minimal implementation**
- Implement gallery grid + metadata drawer structure
- Keep upload/delete functionality unchanged

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=InstructorImageLibraryThemeTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/instructor/image-library/index.blade.php app/Http/Controllers/Instructor/ImageLibraryController.php tests/Feature/Instructor/InstructorImageLibraryThemeTest.php`
- `git commit -m "feat(instructor-ui): redesign image library with themed visual gallery"`

## Task 14: Enhance instructor assessment logs with approved metrics

**Files:**
- Create: `app/Http/Controllers/Instructor/AssessmentLogController.php` (if not existing)
- Create: `app/Services/InstructorAssessmentInsightsService.php`
- Create: `resources/views/instructor/assessments/index.blade.php`
- Modify: `routes/instructor.php`
- Create: `tests/Feature/Instructor/AssessmentInsightsTest.php`

**Step 1: Write the failing test**
- Assert response includes:
  - per-module score distribution
  - attempt count per learner
  - at-risk learner flag based on configurable thresholds

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AssessmentInsightsTest`
- Expected: FAIL due to missing route/controller/service

**Step 3: Write minimal implementation**
- Add route and controller endpoint
- Implement service methods for three required metrics
- Render instructor assessment view with drill-down links

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AssessmentInsightsTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Http/Controllers/Instructor/AssessmentLogController.php app/Services/InstructorAssessmentInsightsService.php resources/views/instructor/assessments/index.blade.php routes/instructor.php tests/Feature/Instructor/AssessmentInsightsTest.php`
- `git commit -m "feat(instructor): add assessment insights dashboard"`

## Task 15: Expand instructor notification center behavior and sidebar polish

**Files:**
- Modify: `resources/views/layouts/instructor-app.blade.php`
- Modify: `app/Http/Controllers/Instructor/DashboardController.php`
- Modify: `resources/views/instructor/dashboard.blade.php`
- Create: `tests/Feature/Instructor/InstructorNotificationCenterTest.php`

**Step 1: Write the failing test**
- Assert notification badge and list include batched "new quiz taking" summaries
- Assert enrollment decision notifications remain visible for relevant roles

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=InstructorNotificationCenterTest`
- Expected: FAIL prior to notification-center rendering updates

**Step 3: Write minimal implementation**
- Add notification list rendering and unread count in instructor layout
- Add batch summary grouping for new quiz-taking events
- Apply instructor sidebar icon/spacing improvements

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=InstructorNotificationCenterTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/layouts/instructor-app.blade.php app/Http/Controllers/Instructor/DashboardController.php resources/views/instructor/dashboard.blade.php tests/Feature/Instructor/InstructorNotificationCenterTest.php`
- `git commit -m "feat(instructor-ui): enhance notification center and sidebar clarity"`

## Task 16: Final regression run and QA checklist execution

**Files:**
- Modify: `docs/QUICK_TESTING_GUIDE.md` (append instructor refinement test checklist)

**Step 1: Run targeted suite**
- Run: `php artisan test tests/Feature/Instructor`
- Expected: PASS

**Step 2: Run full suite**
- Run: `php artisan test`
- Expected: PASS

**Step 3: Execute manual QA checklist**
- Verify:
  - learners view-only table and scoped visibility
  - search routes to details pages
  - rejection reason + learner notifications
  - active/inactive hybrid learner behavior
  - modal edits and delete confirmations
  - assessment metrics and notification center

**Step 4: Update docs**
- Add concise QA steps and known edge-case checks in `docs/QUICK_TESTING_GUIDE.md`

**Step 5: Commit**
- `git add docs/QUICK_TESTING_GUIDE.md`
- `git commit -m "docs(testing): add instructor panel refinement QA checklist"`
