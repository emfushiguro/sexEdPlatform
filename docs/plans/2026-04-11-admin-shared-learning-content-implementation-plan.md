# Admin Shared Learning Content Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Integrate learning content management directly into the Admin panel using shared instructor content controllers/views, while preserving ownership attribution and governance rules.

**Architecture:** Add admin-prefixed routes that point to existing instructor content controllers, then introduce a lightweight panel context resolver for route/layout switching. Extend policy/scope behavior so admins can manage all content while instructors remain owner-scoped. Reuse existing Blade screens by making routes and layout dynamic instead of instructor-hardcoded.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS, Spatie Permissions, PHPUnit.

---

## Task 1: Add Panel Context Resolver Primitive

**Files:**
- Create: `app/Support/ContentPanelContext.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/Services/ContentPanelContextTest.php`

**Step 1: Write the failing test**

```php
public function test_resolves_admin_and_instructor_panel_from_route_name(): void
{
    $this->assertSame('admin', ContentPanelContext::fromRouteName('admin.modules.index')->panel());
    $this->assertSame('instructor', ContentPanelContext::fromRouteName('instructor.modules.index')->panel());
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ContentPanelContextTest`
Expected: FAIL with class/method not found.

**Step 3: Write minimal implementation**

Implement `ContentPanelContext` methods:
- `panel()`
- `layout()` returns `layouts.admin` or `layouts.instructor-app`
- `name(string $suffix)` returns `{panel}.{suffix}` for route generation
- `isAdmin()` / `isInstructor()`

Expose helper/container binding in `AppServiceProvider` for controller/view access.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ContentPanelContextTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Support/ContentPanelContext.php app/Providers/AppServiceProvider.php tests/Unit/Services/ContentPanelContextTest.php
git commit -m "feat: add content panel context resolver"
```

## Task 2: Wire Admin Shared Content Routes to Existing Instructor Controllers

**Files:**
- Modify: `routes/admin.php`
- Modify: `routes/instructor.php`
- Test: `tests/Feature/Admin/AdminSharedContentRoutesTest.php`

**Step 1: Write the failing test**

```php
public function test_admin_modules_route_uses_shared_content_controller(): void
{
    $admin = $this->createAdmin();

    $this->actingAs($admin)
        ->get(route('admin.modules.index'))
        ->assertOk();
}
```

Add assertions for:
- `admin.lessons.index`
- `admin.topics.create`
- `admin.quizzes.index`
- `admin.enrollments.index`

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminSharedContentRoutesTest`
Expected: FAIL on missing route or redirect/authorization mismatch.

**Step 3: Write minimal implementation**

In `routes/admin.php`:
- replace/realign `admin.modules.*` authoring routes to point to `Instructor\ModuleController` and related instructor content controllers
- keep admin moderation routes (`admin.content-reviews.*`) unchanged
- add admin content routes for lessons/topics/quizzes/enrollments using same controllers as instructor routes

Ensure middleware is permission-based (no instructor-role-only route assumptions).

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminSharedContentRoutesTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add routes/admin.php routes/instructor.php tests/Feature/Admin/AdminSharedContentRoutesTest.php
git commit -m "feat: map admin content routes to shared controllers"
```

## Task 3: Expand Policy Rules for Admin Override on Shared Content Resources

**Files:**
- Modify: `app/Policies/LessonPolicy.php`
- Modify: `app/Policies/TopicPolicy.php`
- Modify: `app/Policies/QuizPolicy.php`
- Test: `tests/Feature/Admin/AdminSharedContentPolicyTest.php`

**Step 1: Write the failing test**

Create scenarios where:
- admin can update lesson/topic/quiz belonging to instructor module
- instructor cannot update another instructor content

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminSharedContentPolicyTest`
Expected: FAIL because current policies are owner-only.

**Step 3: Write minimal implementation**

Update each policy to allow admin path via permissions (for example `review modules` / `publish modules` or explicit content edit permissions) while preserving owner checks for instructors.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminSharedContentPolicyTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Policies/LessonPolicy.php app/Policies/TopicPolicy.php app/Policies/QuizPolicy.php tests/Feature/Admin/AdminSharedContentPolicyTest.php
git commit -m "feat: allow admin policy override for shared content resources"
```

## Task 4: Add Role-Aware Module Querying for Admin/Instructor

**Files:**
- Modify: `app/Services/Content/ContentAccessService.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Test: `tests/Feature/Admin/AdminModulesIndexSegmentationTest.php`

**Step 1: Write the failing test**

Test admin `admin.modules.index` supports filters:
- `scope=all`
- `scope=platform`
- `scope=instructor`
- `status=archived`

and returns expected module sets.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminModulesIndexSegmentationTest`
Expected: FAIL because current module list is instructor-only in shared controller.

**Step 3: Write minimal implementation**

Add service methods:
- `paginateAdminModules(string $scope, string $status, int $perPage)`
- keep existing `paginateInstructorModules()` unchanged

Update `ModuleController@index` to branch by panel context and call the right query method.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminModulesIndexSegmentationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/Content/ContentAccessService.php app/Http/Controllers/Instructor/ModuleController.php tests/Feature/Admin/AdminModulesIndexSegmentationTest.php
git commit -m "feat: add admin module segmentation in shared module index"
```

## Task 5: Implement Admin Publish/Draft/Archive in Shared Module Controller

**Files:**
- Modify: `app/Http/Requests/Instructor/StoreModuleRequest.php`
- Modify: `app/Http/Requests/Instructor/UpdateModuleRequest.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `app/Services/ContentGovernanceService.php`
- Test: `tests/Feature/Admin/AdminModuleAuthoringWorkflowTest.php`

**Step 1: Write the failing test**

Cover three admin create actions from `admin.modules.store`:
- action=publish -> approved + published
- action=draft -> draft + not published
- action=archive -> soft-deleted

Also assert `content_owner_type=admin` and attribution fields set.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminModuleAuthoringWorkflowTest`
Expected: FAIL due missing action handling in shared controller path.

**Step 3: Write minimal implementation**

- add validated `action` field (`publish|draft|archive`) for admin context
- in shared module store/update, branch by context:
  - instructor uses existing `toInstructorDraftPayload()`
  - admin delegates to governance/content authoring service with admin ownership
- for archive action, soft delete module after save

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminModuleAuthoringWorkflowTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Requests/Instructor/StoreModuleRequest.php app/Http/Requests/Instructor/UpdateModuleRequest.php app/Http/Controllers/Instructor/ModuleController.php app/Services/ContentGovernanceService.php tests/Feature/Admin/AdminModuleAuthoringWorkflowTest.php
git commit -m "feat: support admin publish draft archive in shared module flow"
```

## Task 6: Make Lesson Controller Panel-Aware (Scope + Redirects)

**Files:**
- Modify: `app/Http/Controllers/Instructor/LessonController.php`
- Test: `tests/Feature/Admin/AdminSharedLessonControllerTest.php`

**Step 1: Write the failing test**

Verify admin can:
- list lessons from all modules
- create lesson in instructor-owned module (policy-permitted)
- receive redirects to `admin.lessons.*` routes (not instructor routes)

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminSharedLessonControllerTest`
Expected: FAIL on redirect route names and owner-only query scope.

**Step 3: Write minimal implementation**

- resolve panel context in controller
- convert hardcoded redirects (`instructor.lessons.*`) to context route names
- branch module query scope for admin vs instructor

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminSharedLessonControllerTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Instructor/LessonController.php tests/Feature/Admin/AdminSharedLessonControllerTest.php
git commit -m "feat: make lesson controller panel-aware for admin routes"
```

## Task 7: Make Topic and Quiz Controllers Panel-Aware

**Files:**
- Modify: `app/Http/Controllers/Instructor/TopicController.php`
- Modify: `app/Http/Controllers/Instructor/QuizManagementController.php`
- Test: `tests/Feature/Admin/AdminSharedTopicQuizControllerTest.php`

**Step 1: Write the failing test**

Verify admin actions route correctly and scope correctly:
- topic create/store redirects to `admin.lessons.show`
- quiz create/update redirects to `admin.quizzes.*`
- admin can manage quiz questions on instructor-owned modules

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminSharedTopicQuizControllerTest`
Expected: FAIL due hardcoded instructor route names and scope assumptions.

**Step 3: Write minimal implementation**

Refactor both controllers to use panel-context route generation and role-aware module ownership scope checks.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminSharedTopicQuizControllerTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Instructor/TopicController.php app/Http/Controllers/Instructor/QuizManagementController.php tests/Feature/Admin/AdminSharedTopicQuizControllerTest.php
git commit -m "feat: make topic and quiz controllers panel-aware"
```

## Task 8: Make Enrollment Controller Admin-Aware

**Files:**
- Modify: `app/Http/Controllers/Instructor/EnrollmentController.php`
- Modify: `app/Services/Content/ContentAccessService.php`
- Test: `tests/Feature/Admin/AdminSharedEnrollmentManagementTest.php`

**Step 1: Write the failing test**

Verify:
- admin sees enrollments across all modules
- instructor only sees own module enrollments
- admin can approve/reject/remove enrollments across modules

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminSharedEnrollmentManagementTest`
Expected: FAIL on instructor-only ownership guards.

**Step 3: Write minimal implementation**

Introduce context-aware ownership checks in enrollment controller (`admin` bypass with permission, instructor owner-only) and keep current notification behavior.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminSharedEnrollmentManagementTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Instructor/EnrollmentController.php app/Services/Content/ContentAccessService.php tests/Feature/Admin/AdminSharedEnrollmentManagementTest.php
git commit -m "feat: enable admin enrollment management in shared controller"
```

## Task 9: Reuse Instructor Views with Dynamic Layout and Dynamic Route Names

**Files:**
- Modify: `resources/views/instructor/modules/index.blade.php`
- Modify: `resources/views/instructor/modules/create.blade.php`
- Modify: `resources/views/instructor/modules/edit.blade.php`
- Modify: `resources/views/instructor/modules/show.blade.php`
- Modify: `resources/views/instructor/lessons/index.blade.php`
- Modify: `resources/views/instructor/lessons/create.blade.php`
- Modify: `resources/views/instructor/lessons/edit.blade.php`
- Modify: `resources/views/instructor/lessons/show.blade.php`
- Modify: `resources/views/instructor/topics/create.blade.php`
- Modify: `resources/views/instructor/topics/edit.blade.php`
- Modify: `resources/views/instructor/quizzes/index.blade.php`
- Modify: `resources/views/instructor/quizzes/create.blade.php`
- Modify: `resources/views/instructor/quizzes/show.blade.php`
- Modify: `resources/views/instructor/quizzes/add-question.blade.php`
- Modify: `resources/views/instructor/quizzes/edit-question.blade.php`
- Modify: `resources/views/instructor/enrollments/index.blade.php`
- Modify: `resources/views/instructor/enrollments/show.blade.php`
- Modify: `resources/views/instructor/enrollments/module.blade.php`
- Test: `tests/Feature/Admin/AdminSharedContentViewLayoutTest.php`

**Step 1: Write the failing test**

Assert admin route renders shared module screen under admin layout and contains admin-prefixed navigation links.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminSharedContentViewLayoutTest`
Expected: FAIL because views currently extend instructor layout and reference instructor routes.

**Step 3: Write minimal implementation**

- switch fixed `@extends('layouts.instructor-app')` to context-driven layout variable
- replace hardcoded `route('instructor....')` calls with context route helper/variables injected by controller
- keep UI components/styles unchanged

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminSharedContentViewLayoutTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/instructor/modules/*.blade.php resources/views/instructor/lessons/*.blade.php resources/views/instructor/topics/*.blade.php resources/views/instructor/quizzes/*.blade.php resources/views/instructor/enrollments/*.blade.php tests/Feature/Admin/AdminSharedContentViewLayoutTest.php
git commit -m "feat: make shared content blades panel-aware"
```

## Task 10: Add Admin Sidebar Learning Content Section + Learners Entry

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `app/Http/Controllers/Admin/UserAdminController.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Admin/AdminSidebarLearningContentNavTest.php`

**Step 1: Write the failing test**

Verify admin layout contains links for:
- Modules
- Lessons
- Lesson Topics
- Quizzes
- Enrollments
- Learners

and learners link opens filtered learner listing suitable for content monitoring.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminSidebarLearningContentNavTest`
Expected: FAIL due missing nav section/routes.

**Step 3: Write minimal implementation**

- add new Admin sidebar section
- point Learners entry to either a dedicated learners route or filtered `admin.users.index` behavior
- include filters: all learners / platform-enrolled / instructor-enrolled

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminSidebarLearningContentNavTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/layouts/admin.blade.php app/Http/Controllers/Admin/UserAdminController.php routes/admin.php tests/Feature/Admin/AdminSidebarLearningContentNavTest.php
git commit -m "feat: add admin learning content navigation and learners view"
```

## Task 11: Preserve Instructor Submission/Review Lifecycle Regression

**Files:**
- Modify: `tests/Feature/Instructor/InstructorModuleReviewSubmissionTest.php`
- Create: `tests/Feature/Admin/AdminDoesNotEnterInstructorSubmissionQueueTest.php`

**Step 1: Write the failing test**

Add coverage that admin-created modules do not create `module_review_requests` and never require `submit/resubmit` flow.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminDoesNotEnterInstructorSubmissionQueueTest`
Expected: FAIL before admin flow adjustments are complete.

**Step 3: Write minimal implementation**

Ensure shared module controller/governance path skips instructor review lifecycle for admin-owned modules.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminDoesNotEnterInstructorSubmissionQueueTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add tests/Feature/Instructor/InstructorModuleReviewSubmissionTest.php tests/Feature/Admin/AdminDoesNotEnterInstructorSubmissionQueueTest.php
git commit -m "test: enforce admin authoring bypasses instructor review queue"
```

## Task 12: End-to-End Verification and Documentation Update

**Files:**
- Modify: `docs/changelogs/2026-04-11-admin-shared-learning-content.md`

**Step 1: Run focused regression suites**

Run:
- `php artisan test --filter=AdminSharedContentRoutesTest`
- `php artisan test --filter=AdminSharedContentPolicyTest`
- `php artisan test --filter=AdminModuleAuthoringWorkflowTest`
- `php artisan test --filter=AdminSharedEnrollmentManagementTest`
- `php artisan test --filter=InstructorModuleReviewSubmissionTest`

Expected: PASS.

**Step 2: Run broader module/admin suite**

Run:
- `php artisan test --filter=AdminModule`
- `php artisan test --filter=InstructorModule`

Expected: PASS (or capture exact failures with follow-up fixes).

**Step 3: Update changelog**

Document:
- shared route architecture
- ownership/publishing behavior
- policy/scope updates
- learner/enrollment monitoring behavior
- tests executed

**Step 4: Final verification command**

Run: `php artisan test`
Expected: PASS (or list known unrelated failures explicitly).

**Step 5: Commit**

```bash
git add docs/changelogs/2026-04-11-admin-shared-learning-content.md
git commit -m "docs: add changelog for admin shared learning content rollout"
```
