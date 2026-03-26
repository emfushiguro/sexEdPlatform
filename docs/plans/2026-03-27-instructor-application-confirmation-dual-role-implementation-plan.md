# Instructor Application Confirmation and Dual-Role Upgrade Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement admin approval plus learner confirmation before role grant, while preserving simultaneous Learner and Instructor roles.

**Architecture:** Extend `instructor_applications` into a strict lifecycle state machine and keep workflow orchestration in `InstructorApplicationService`. Admin approval moves applications to waiting-for-confirmation, and learner acceptance performs the role upgrade transaction that keeps Learner role and adds Instructor role.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Spatie Laravel Permission, PHPUnit.

---

## Task 1: Lock Behavior with Failing Workflow Tests

**Files:**
- Create: `tests/Feature/InstructorApplicationConfirmationFlowTest.php`
- Modify: `tests/Feature/InstructorApplicationApprovalTest.php`

**Step 1: Write the failing test**

Add tests for:
- admin approval changes status to `approved_waiting_confirmation`
- admin approval does not immediately grant instructor role
- learner acceptance grants instructor role and keeps learner role
- learner decline sets cooldown and blocks immediate reapply

Example skeleton:

```php
public function test_admin_approval_requires_learner_confirmation_before_role_grant(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->assignRole('admin');

    $learner = User::factory()->create(['role' => 'learner']);
    $learner->assignRole('learner');

    $application = InstructorApplication::factory()->create([
        'user_id' => $learner->id,
        'status' => 'pending_review',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.instructor-applications.approve', $application))
        ->assertRedirect();

    $this->assertDatabaseHas('instructor_applications', [
        'id' => $application->id,
        'status' => 'approved_waiting_confirmation',
    ]);

    $this->assertFalse($learner->fresh()->hasRole('instructor'));
}
```

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
FAIL due to missing statuses/routes/decision logic.

**Step 3: Write minimal implementation placeholder**

Create placeholder test file and imports only (no production changes yet).

**Step 4: Run test to verify failing baseline remains clear**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
FAIL with explicit assertion/route/status errors.

**Step 5: Commit**

Run:
`git add tests/Feature/InstructorApplicationConfirmationFlowTest.php tests/Feature/InstructorApplicationApprovalTest.php`
`git commit -m "test: add failing confirmation workflow coverage"`

---

## Task 2: Add Lifecycle Schema and Data Migration

**Files:**
- Create: `database/migrations/2026_03_27_100000_expand_instructor_application_lifecycle_states.php`
- Modify: `app/Models/InstructorApplication.php`

**Step 1: Write the failing test**

Add migration-sensitive assertions in `tests/Feature/InstructorApplicationConfirmationFlowTest.php` for new fields:
- `reviewed_by`
- `reviewed_at`
- `learner_decision_at`
- `reapply_available_at`

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
FAIL with unknown column or invalid status values.

**Step 3: Write minimal implementation**

Implement migration to:
- add lifecycle columns
- map legacy statuses (`pending` -> `pending_review`, `rejected` -> `rejected`, `approved` conditional)
- add required indexes

Update model:
- add new fillable fields
- add casts
- add scopes for lifecycle states
- add helper methods for transition guards

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
Schema-related failures resolved; remaining workflow failures continue (acceptable).

**Step 5: Commit**

Run:
`git add database/migrations/2026_03_27_100000_expand_instructor_application_lifecycle_states.php app/Models/InstructorApplication.php tests/Feature/InstructorApplicationConfirmationFlowTest.php`
`git commit -m "feat: add instructor application lifecycle schema"`

---

## Task 3: Refactor Service for Two-Phase Review and Confirmation

**Files:**
- Modify: `app/Services/InstructorApplicationService.php`
- Modify: `tests/Unit/InstructorApplicationServiceTest.php`

**Step 1: Write the failing test**

Add/adjust unit tests for:
- `approveForConfirmation` only transitions to `approved_waiting_confirmation`
- `acceptInstructorRole` assigns instructor and keeps learner
- `declineInstructorRole` sets `role_declined` and cooldown
- invalid transition throws domain exception

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorApplicationServiceTest`

Expected:
FAIL because new methods/guards do not exist yet.

**Step 3: Write minimal implementation**

Service changes:
- replace current `approve` role-grant behavior with `approveForConfirmation`
- add `acceptInstructorRole`
- add `declineInstructorRole`
- keep mail-safe notification pattern
- preserve transaction boundaries and audit writes (`role_transitions`)

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorApplicationServiceTest`

Expected:
PASS.

**Step 5: Commit**

Run:
`git add app/Services/InstructorApplicationService.php tests/Unit/InstructorApplicationServiceTest.php`
`git commit -m "refactor: implement two-phase instructor role upgrade service"`

---

## Task 4: Add Learner Confirmation Endpoints and Request Validation

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Learner/InstructorApplicationController.php`
- Create: `app/Http/Requests/ConfirmInstructorRoleRequest.php`
- Create: `app/Http/Requests/DeclineInstructorRoleRequest.php`
- Create: `resources/views/learner/instructor-application/decision.blade.php`

**Step 1: Write the failing test**

Add feature tests for:
- owner can view decision page and accept/decline
- non-owner cannot act on application
- only `approved_waiting_confirmation` can be decided

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
FAIL with route not found or 403/422 mismatches.

**Step 3: Write minimal implementation**

Add routes under learner instructor prefix:
- `GET decision/{application}`
- `POST decision/{application}/accept`
- `POST decision/{application}/decline`

Controller methods call service only; requests handle validation/authorization.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
PASS for endpoint authorization and transition routing.

**Step 5: Commit**

Run:
`git add routes/web.php app/Http/Controllers/Learner/InstructorApplicationController.php app/Http/Requests/ConfirmInstructorRoleRequest.php app/Http/Requests/DeclineInstructorRoleRequest.php resources/views/learner/instructor-application/decision.blade.php tests/Feature/InstructorApplicationConfirmationFlowTest.php`
`git commit -m "feat: add learner instructor role confirmation endpoints"`

---

## Task 5: Update Admin Review Controller and Notification Payloads

**Files:**
- Modify: `app/Http/Controllers/Admin/InstructorApplicationController.php`
- Modify: `app/Notifications/InstructorApplicationStatusUpdate.php`
- Modify: `resources/views/emails/instructor-application-status.blade.php`
- Optional modify: `app/Notifications/InstructorApplicationApproved.php`

**Step 1: Write the failing test**

Add tests asserting:
- approval notification includes accept/decline URLs
- notification message says confirmation is required before access
- database notification type and status payload are correct

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorApplicationApprovalTest`

Expected:
FAIL due to outdated notification payload and wording.

**Step 3: Write minimal implementation**

Controller approve path should call service review method (no direct role grant).
Notification payload should include:
- `application_id`
- `status`
- `accept_url`
- `decline_url`

Email copy must clearly indicate role is pending learner confirmation.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorApplicationApprovalTest`

Expected:
PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Admin/InstructorApplicationController.php app/Notifications/InstructorApplicationStatusUpdate.php resources/views/emails/instructor-application-status.blade.php tests/Feature/InstructorApplicationApprovalTest.php`
`git commit -m "feat: update approval notifications for learner confirmation"`

---

## Task 6: Enforce Multi-Role Semantics in User Checks and Submission Guards

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Http/Requests/SubmitInstructorApplicationRequest.php`
- Modify: `app/Http/Controllers/Learner/DashboardController.php`
- Modify: `app/Http/Controllers/Learner/InstructorApplicationController.php`
- Modify: `app/Http/Middleware/EnsureProfileCompleted.php`

**Step 1: Write the failing test**

Add tests for:
- dual-role user still treated as learner for learner-side access checks
- dual-role user cannot reapply as instructor
- profile completion middleware still behaves correctly for learner+instructor users

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
FAIL due to `isLearner()` and role guards relying on single role column.

**Step 3: Write minimal implementation**

Update role helpers to prioritize Spatie checks:
- `isLearner()` -> `hasRole('learner')`
- `isInstructor()` -> `hasRole('instructor')`

Update submission guard to block already-instructor users.
Update dashboard/controller conditions to align with dual-role semantics.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
PASS.

**Step 5: Commit**

Run:
`git add app/Models/User.php app/Http/Requests/SubmitInstructorApplicationRequest.php app/Http/Controllers/Learner/DashboardController.php app/Http/Controllers/Learner/InstructorApplicationController.php app/Http/Middleware/EnsureProfileCompleted.php tests/Feature/InstructorApplicationConfirmationFlowTest.php`
`git commit -m "fix: align learner and instructor checks with multi-role support"`

---

## Task 7: Add Cooldown Configuration and Messaging

**Files:**
- Create: `config/instructor_applications.php`
- Modify: `app/Services/InstructorApplicationService.php`
- Modify: `resources/views/learner/instructor-application/form.blade.php`
- Modify: `app/Http/Controllers/Learner/InstructorApplicationController.php`

**Step 1: Write the failing test**

Add tests for:
- decline sets `reapply_available_at` based on config value
- submission before cooldown expires fails with validation/error message

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
FAIL because cooldown config and message logic are missing.

**Step 3: Write minimal implementation**

Create config with:
- `decline_cooldown_days` default `14`

Use config in service cooldown calculation.
Show remaining cooldown feedback in learner apply flow.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorApplicationConfirmationFlowTest`

Expected:
PASS.

**Step 5: Commit**

Run:
`git add config/instructor_applications.php app/Services/InstructorApplicationService.php resources/views/learner/instructor-application/form.blade.php app/Http/Controllers/Learner/InstructorApplicationController.php tests/Feature/InstructorApplicationConfirmationFlowTest.php`
`git commit -m "feat: add decline cooldown configuration for instructor reapplication"`

---

## Task 8: Full Regression Verification and Docs Sync

**Files:**
- Modify: `tests/Feature/InstructorApplicationSubmissionTest.php`
- Modify: `tests/Feature/InstructorApplicationApprovalTest.php`
- Modify: `docs/changelogs/2026-03-20-instructor-ui-modernization.md` (append note if this branch uses same changelog stream)

**Step 1: Write the failing test**

Add final regression checks:
- approved users can access both learner and instructor dashboards
- admin reject still works with reason
- existing submission validation remains intact

**Step 2: Run targeted suites**

Run:
`php artisan test --filter=InstructorApplication`

Expected:
Any remaining failures should now be explicit and fixable.

**Step 3: Write minimal implementation**

Patch only failing areas, avoid unrelated refactors.

**Step 4: Run full verification**

Run:
`php artisan test`

Expected:
PASS with no instructor-application regressions.

**Step 5: Commit**

Run:
`git add tests/Feature/InstructorApplicationSubmissionTest.php tests/Feature/InstructorApplicationApprovalTest.php tests/Feature/InstructorApplicationConfirmationFlowTest.php docs/changelogs/2026-03-20-instructor-ui-modernization.md`
`git commit -m "test: verify instructor application confirmation dual-role workflow end to end"`

---

## Notes for Execution

- Keep controllers thin; place transition and guard logic in service layer.
- Use Form Requests for validation/authorization of accept/decline actions.
- Preserve notification fail-soft behavior (database notification should not fail on mail transport errors).
- Do not introduce API routes; keep server-rendered Blade flow.
- Prefer Spatie role checks as source of truth for new authorization conditions.

## Verification Commands Summary

- `php artisan test --filter=InstructorApplicationConfirmationFlowTest`
- `php artisan test --filter=InstructorApplicationServiceTest`
- `php artisan test --filter=InstructorApplicationApprovalTest`
- `php artisan test --filter=InstructorApplicationSubmissionTest`
- `php artisan test --filter=InstructorApplication`
- `php artisan test`
