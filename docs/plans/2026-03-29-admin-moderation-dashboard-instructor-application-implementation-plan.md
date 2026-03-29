# Admin Moderation, Dashboard, and Instructor Application Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement moderation-focused admin navigation, a command-center dashboard aligned with existing admin UI, and a structured instructor-application rejection reason system with clear learner notifications.

**Architecture:** Build incrementally on the existing admin routes, controllers, and Blade views rather than introducing a new module boundary. Keep controllers thin and move aggregation/composition logic into services and enum/value helpers. Maintain backward compatibility by preserving legacy rejection_reason while introducing structured rejection_reason_code and rejection_reason_note fields.

**Tech Stack:** Laravel 12 (PHP), Blade, Alpine.js, Eloquent, Notifications (mail + database), PHPUnit feature tests.

---

## Task 1: Add Moderation Navigation Test Coverage

**Files:**
- Modify: tests/Feature/Admin/AdminDashboardMetricsTest.php
- Modify: resources/views/layouts/admin.blade.php
- Modify: app/Providers/AppServiceProvider.php

**Step 1: Write failing sidebar moderation tests**

Add tests asserting dashboard response includes:
- Instructor Applications link label
- Module Published Review link label
- Optional badge container markers for pending counts

Example assertion pattern:

```php
$this->actingAs($admin)
    ->get(route('admin.dashboard'))
    ->assertOk()
    ->assertSee('Instructor Applications', false)
    ->assertSee('Module Published Review', false);
```

**Step 2: Run test to verify it fails**

Run:

```bash
php artisan test --filter=AdminDashboardMetricsTest
```

Expected: FAIL because moderation links are not yet rendered in sidebar.

**Step 3: Implement minimal sidebar moderation links**

1. Add Moderation section in admin sidebar.
2. Add links to:
   - admin.instructor-applications.index
   - admin.content-reviews.index (labeled Module Published Review)
3. Add active-state classes for both route groups.
4. Add pending counts in AppServiceProvider composer payload for sidebar badges.

**Step 4: Run test to verify it passes**

Run:

```bash
php artisan test --filter=AdminDashboardMetricsTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add tests/Feature/Admin/AdminDashboardMetricsTest.php resources/views/layouts/admin.blade.php app/Providers/AppServiceProvider.php
git commit -m "feat: add admin moderation sidebar shortcuts"
```

## Task 2: Add Command-Center Dashboard Feature Tests

**Files:**
- Create: tests/Feature/Admin/AdminDashboardCommandCenterTest.php
- Modify: app/Services/AdminDashboardService.php
- Modify: app/Http/Controllers/Admin/DashboardController.php
- Modify: resources/views/admin/dashboard.blade.php

**Step 1: Write failing dashboard command-center tests**

Create tests for:
1. 8 snapshot metrics rendered with expected labels.
2. Quick action links to moderation queues.
3. Recent activity section visible.

Example skeleton:

```php
public function test_dashboard_renders_command_center_sections(): void
{
    $this->withoutVite();

    $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Pending Instructor Applications', false)
        ->assertSee('Pending Module Reviews', false)
        ->assertSee('Recent System Activity', false);
}
```

**Step 2: Run test to verify it fails**

Run:

```bash
php artisan test --filter=AdminDashboardCommandCenterTest
```

Expected: FAIL due missing labels/sections.

**Step 3: Implement minimal dashboard service + view changes**

1. Add command-center metric payload in AdminDashboardService.
2. Keep DashboardController as orchestration only.
3. Update dashboard Blade to:
   - render 8-card snapshot grid
   - render quick action band
   - render lightweight recent activity list
4. Keep visual classes aligned with existing admin cards/tables.

**Step 4: Run test to verify it passes**

Run:

```bash
php artisan test --filter=AdminDashboardCommandCenterTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add tests/Feature/Admin/AdminDashboardCommandCenterTest.php app/Services/AdminDashboardService.php app/Http/Controllers/Admin/DashboardController.php resources/views/admin/dashboard.blade.php
git commit -m "feat: redesign admin dashboard as moderation command center"
```

## Task 3: Add Instructor Application UI Table Coverage

**Files:**
- Create: tests/Feature/Admin/AdminInstructorApplicationsUiTest.php
- Modify: app/Http/Controllers/Admin/InstructorApplicationController.php
- Modify: resources/views/admin/instructor-applications/index.blade.php
- Modify: resources/views/admin/instructor-applications/show.blade.php

**Step 1: Write failing UI assertions for application columns and filters**

Test should assert page includes:
- Username
- Location
- Educational Background
- Professional Background
- Date Applied
- Status
- data-testid marker for filter bar

**Step 2: Run test to verify it fails**

Run:

```bash
php artisan test --filter=AdminInstructorApplicationsUiTest
```

Expected: FAIL because current index table does not include all required fields.

**Step 3: Implement minimal controller + view updates**

1. Eager-load learner profile city data needed for location rendering.
2. Add unified filter/search controls matching existing admin table pattern.
3. Add required table columns and status chips.
4. Keep view logic lightweight (formatting only).

**Step 4: Run test to verify it passes**

Run:

```bash
php artisan test --filter=AdminInstructorApplicationsUiTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add tests/Feature/Admin/AdminInstructorApplicationsUiTest.php app/Http/Controllers/Admin/InstructorApplicationController.php resources/views/admin/instructor-applications/index.blade.php resources/views/admin/instructor-applications/show.blade.php
git commit -m "feat: align instructor application admin ui with table and card patterns"
```

## Task 4: Add Structured Rejection Reason Data Model

**Files:**
- Create: database/migrations/2026_03_29_000001_add_structured_rejection_fields_to_instructor_applications_table.php
- Modify: app/Models/InstructorApplication.php
- Create: app/Enums/InstructorApplicationRejectionReason.php
- Modify: app/Http/Requests/RejectInstructorApplicationRequest.php

**Step 1: Write failing schema + validation tests**

1. Add test to assert reject endpoint accepts rejection_reason_code.
2. Add test asserting Other requires rejection_reason_note.
3. Add schema assertion test for new columns.

Suggested test file:
- tests/Feature/Admin/InstructorApplicationRejectionReasonTest.php

**Step 2: Run tests to verify failure**

Run:

```bash
php artisan test --filter=InstructorApplicationRejectionReasonTest
```

Expected: FAIL because columns and validation rules do not exist yet.

**Step 3: Implement migration, enum, and request rules**

1. Add nullable string rejection_reason_code.
2. Add nullable text rejection_reason_note.
3. Add enum for allowed reason codes and display labels.
4. Update request rules:
   - rejection_reason_code required, in enum values
   - rejection_reason_note nullable|string|max:1000
   - rejection_reason_note required_if code=other

**Step 4: Run tests to verify pass**

Run:

```bash
php artisan test --filter=InstructorApplicationRejectionReasonTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add database/migrations/2026_03_29_000001_add_structured_rejection_fields_to_instructor_applications_table.php app/Models/InstructorApplication.php app/Enums/InstructorApplicationRejectionReason.php app/Http/Requests/RejectInstructorApplicationRequest.php tests/Feature/Admin/InstructorApplicationRejectionReasonTest.php
git commit -m "feat: add structured instructor application rejection reason fields"
```

## Task 5: Refactor Reject Flow Service and Controller Contract

**Files:**
- Modify: app/Http/Controllers/Admin/InstructorApplicationController.php
- Modify: app/Services/InstructorApplicationService.php
- Modify: tests/Feature/InstructorApplicationApprovalTest.php

**Step 1: Write failing feature test for structured reject payload**

Update existing reject test to post:
- rejection_reason_code
- rejection_reason_note (optional)

Assert database contains:
- status=rejected
- rejection_reason_code
- rejection_reason_note
- composed rejection_reason

**Step 2: Run test to verify failure**

Run:

```bash
php artisan test --filter=InstructorApplicationApprovalTest
```

Expected: FAIL because current flow only accepts rejection_reason text.

**Step 3: Implement minimal flow changes**

1. Controller passes validated code and note to service.
2. Service composes readable rejection_reason string from enum label + note.
3. Persist structured + composed fields in one update call.

**Step 4: Run test to verify pass**

Run:

```bash
php artisan test --filter=InstructorApplicationApprovalTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/InstructorApplicationController.php app/Services/InstructorApplicationService.php tests/Feature/InstructorApplicationApprovalTest.php
git commit -m "feat: support structured rejection reasons in instructor application workflow"
```

## Task 6: Update Reject Modal and Detail Rendering

**Files:**
- Modify: resources/views/admin/instructor-applications/_reject-modal.blade.php
- Modify: resources/views/admin/instructor-applications/show.blade.php

**Step 1: Write failing UI test for reject form fields**

Add assertions for:
- rejection_reason_code select/radio input
- rejection_reason_note input
- conditional helper copy for Other reason

Place in:
- tests/Feature/Admin/AdminInstructorApplicationsUiTest.php

**Step 2: Run test to verify failure**

Run:

```bash
php artisan test --filter=AdminInstructorApplicationsUiTest
```

Expected: FAIL until reject modal fields are updated.

**Step 3: Implement modal and detail UI updates**

1. Replace free-text-only rejection input with:
   - preset reason selector
   - optional note textarea
2. Update detail page reason panel to show structured label + note cleanly.
3. Preserve readability and existing visual tokens.

**Step 4: Run test to verify pass**

Run:

```bash
php artisan test --filter=AdminInstructorApplicationsUiTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/admin/instructor-applications/_reject-modal.blade.php resources/views/admin/instructor-applications/show.blade.php tests/Feature/Admin/AdminInstructorApplicationsUiTest.php
git commit -m "feat: add preset rejection reasons to admin reject modal"
```

## Task 7: Update Notification Payload and Email Messaging

**Files:**
- Modify: app/Notifications/InstructorApplicationStatusUpdate.php
- Modify: resources/views/emails/instructor-application-status.blade.php
- Create: tests/Feature/Notifications/InstructorApplicationStatusNotificationTest.php

**Step 1: Write failing notification test**

Cover rejection payload includes:
- reason_code
- reason_label
- reason_note
- readable_reason

Also assert email view receives formatted reason content.

**Step 2: Run test to verify failure**

Run:

```bash
php artisan test --filter=InstructorApplicationStatusNotificationTest
```

Expected: FAIL due missing structured fields.

**Step 3: Implement notification and email updates**

1. Extend toArray payload with structured reason fields.
2. Keep backward-compatible message/title behavior.
3. Update rejection email copy to clearly explain reason category + note.

**Step 4: Run test to verify pass**

Run:

```bash
php artisan test --filter=InstructorApplicationStatusNotificationTest
```

Expected: PASS.

**Step 5: Commit**

```bash
git add app/Notifications/InstructorApplicationStatusUpdate.php resources/views/emails/instructor-application-status.blade.php tests/Feature/Notifications/InstructorApplicationStatusNotificationTest.php
git commit -m "feat: include structured rejection rationale in instructor application notifications"
```

## Task 8: Full Regression and Final Verification

**Files:**
- Modify (if needed): tests/Feature/Admin/AdminDashboardMetricsTest.php
- Modify (if needed): tests/Feature/Admin/AdminTableUxTest.php
- Modify (if needed): docs/changelogs (if your team tracks admin UI updates there)

**Step 1: Run focused admin and instructor application suites**

Run:

```bash
php artisan test --filter=AdminDashboardMetricsTest
php artisan test --filter=AdminDashboardCommandCenterTest
php artisan test --filter=AdminInstructorApplicationsUiTest
php artisan test --filter=InstructorApplicationApprovalTest
php artisan test --filter=InstructorApplicationRejectionReasonTest
php artisan test --filter=InstructorApplicationStatusNotificationTest
```

Expected: All PASS.

**Step 2: Run broader regression slice**

Run:

```bash
php artisan test --testsuite=Feature --filter=Admin
```

Expected: PASS with no regressions in existing admin governance tests.

**Step 3: Fix any failures minimally**

Apply only targeted fixes. Avoid unrelated refactors.

**Step 4: Re-run failing test(s) until green**

Run only impacted tests first, then re-run the suite command above.

**Step 5: Commit stabilization changes**

```bash
git add -A
git commit -m "test: stabilize admin moderation and instructor application flows"
```

## Task 9: Final Documentation Sync

**Files:**
- Modify: docs/plans/2026-03-29-admin-moderation-dashboard-instructor-application-design.md
- Modify: docs/plans/2026-03-29-admin-moderation-dashboard-instructor-application-implementation-plan.md

**Step 1: Update design doc implementation status notes**

Add short section: implemented files, constraints discovered, and follow-ups.

**Step 2: Validate no plan drift**

Confirm implementation plan still matches actual merged behavior.

**Step 3: Commit docs updates**

```bash
git add docs/plans/2026-03-29-admin-moderation-dashboard-instructor-application-design.md docs/plans/2026-03-29-admin-moderation-dashboard-instructor-application-implementation-plan.md
git commit -m "docs: sync implementation notes for admin moderation upgrade"
```
