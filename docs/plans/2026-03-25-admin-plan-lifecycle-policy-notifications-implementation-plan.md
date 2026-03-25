# Admin Plan Lifecycle, Policy Cleanup, and Notification Unification Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement a modal-wizard-based admin plans UX with archive/restore lifecycle, enforce no-refund and subscriber-operation policy removals, unify admin Toastify feedback, and add admin navbar notifications for operational events.

**Architecture:** Keep controllers thin and shift transition/policy behavior into focused service and request-validation layers. Build one shared wizard flow for plan create/edit to remove duplicate behavior. Enforce policy removals in routes and backend handlers (not only UI), then align admin views and tests.

**Tech Stack:** Laravel 12, Blade + Alpine.js, Eloquent, Form Requests, Laravel Notifications (database channel), PHPUnit feature tests.

---

## Task 1: Add Plan Archive State and Query Scopes

**Files:**
- Modify: `database/migrations/2026_02_17_000001_create_subscription_plans_table.php` (if migration already shipped, add a new migration instead)
- Create: `database/migrations/2026_03_25_000001_add_archived_at_to_subscription_plans_table.php` (preferred for existing environments)
- Modify: `app/Models/SubscriptionPlan.php`
- Test: `tests/Feature/Admin/SubscriptionSchemaMigrationTest.php`

**Step 1: Write the failing test**
- Add assertions that `subscription_plans` includes `archived_at` and that model queries can distinguish active/inactive/archived records.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=SubscriptionSchemaMigrationTest`
- Expected: FAIL because `archived_at` and archive scopes are missing.

**Step 3: Write minimal implementation**
- Add migration to append nullable `archived_at`.
- Add casts/scopes in `SubscriptionPlan`:
  - `scopeArchived()`
  - `scopeNotArchived()`
  - `isArchived()` helper.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=SubscriptionSchemaMigrationTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add database/migrations/2026_03_25_000001_add_archived_at_to_subscription_plans_table.php app/Models/SubscriptionPlan.php tests/Feature/Admin/SubscriptionSchemaMigrationTest.php
git commit -m "feat(plans): add archive lifecycle schema and scopes"
```

## Task 2: Implement Plan Lifecycle Service (activate/deactivate/archive/restore + impact snapshot)

**Files:**
- Create: `app/Services/Admin/PlanLifecycleService.php`
- Modify: `app/Http/Controllers/Admin/SubscriptionPlanAdminController.php`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**
- Add tests asserting:
  - deactivate keeps existing subscriptions intact.
  - archive marks plan archived and hides it from default list.
  - restore brings plan back as inactive.
  - impact snapshot returns subscriber counts for confirmation modal.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: FAIL because lifecycle service and archive transitions do not exist.

**Step 3: Write minimal implementation**
- Implement transition methods in `PlanLifecycleService`.
- Update controller actions to call service methods.
- Ensure deactivation only blocks new purchases and does not cancel active subscribers.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add app/Services/Admin/PlanLifecycleService.php app/Http/Controllers/Admin/SubscriptionPlanAdminController.php tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(admin-plans): add lifecycle service with archive and impact snapshot"
```

## Task 3: Replace Fullscreen Plan Form with Shared 3-Step Wizard Modal

**Files:**
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Modify: `resources/views/admin/subscription-plans/edit.blade.php` (or remove dependence if edit now modal-first)
- Modify: `resources/js/app.js`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**
- Assert page includes wizard step markers for create/edit in modal context:
  - step 1 identity,
  - step 2 billing config,
  - step 3 entitlement cards.
- Assert old single-form fullscreen markers are removed.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: FAIL due to missing wizard structure and markers.

**Step 3: Write minimal implementation**
- Build shared Alpine wizard state in plans index.
- Support create and edit modes from same modal shell.
- Keep sidebar lock behavior while modal is open.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/admin/subscription-plans/index.blade.php resources/views/admin/subscription-plans/edit.blade.php resources/js/app.js tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(admin-plans): implement shared create-edit wizard modal"
```

## Task 4: Add Billing Preview and Custom Period Validation

**Files:**
- Modify: `app/Http/Requests/Admin/StoreSubscriptionPlanRequest.php`
- Modify: `app/Http/Requests/Admin/UpdateSubscriptionPlanRequest.php`
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**
- Add tests for billing mode validation:
  - monthly and annual valid without custom dates.
  - custom requires valid start/end date and end >= start.
- Add assertion that billing preview block is present in wizard.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: FAIL due to incomplete validation and missing preview marker.

**Step 3: Write minimal implementation**
- Tighten request rules for billing mode and custom period fields.
- Render live billing preview panel in step 2.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Requests/Admin/StoreSubscriptionPlanRequest.php app/Http/Requests/Admin/UpdateSubscriptionPlanRequest.php resources/views/admin/subscription-plans/index.blade.php tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(admin-plans): add billing preview and custom period validation"
```

## Task 5: Replace Trial/Delete Table Actions with Lifecycle Actions and Archive Page

**Files:**
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Create: `resources/views/admin/subscription-plans/archived.blade.php`
- Modify: `routes/admin.php`
- Modify: `app/Http/Controllers/Admin/SubscriptionPlanAdminController.php`
- Test: `tests/Feature/Admin/AdminTableUxTest.php`

**Step 1: Write the failing test**
- Assert plans table does not show trial column.
- Assert delete action is absent and archive action is present.
- Assert archived plans page route/view exists and restore action is available.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: FAIL due to old table/actions and missing archived page.

**Step 3: Write minimal implementation**
- Remove trial column render.
- Replace delete with archive confirmation action.
- Add archived listing route/controller/view and restore action.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/admin/subscription-plans/index.blade.php resources/views/admin/subscription-plans/archived.blade.php routes/admin.php app/Http/Controllers/Admin/SubscriptionPlanAdminController.php tests/Feature/Admin/AdminTableUxTest.php
git commit -m "feat(admin-plans): replace delete with archive and add archived plans page"
```

## Task 6: Add Impact Confirmation Modals for Activate/Deactivate and Archive

**Files:**
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Modify: `app/Http/Controllers/Admin/SubscriptionPlanAdminController.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**
- Add assertions for modal impact data endpoints and rendered warning text.
- Assert message includes entitlement-until-renewal/expiry note.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: FAIL because modals and impact fetch contract are missing.

**Step 3: Write minimal implementation**
- Add impact payload route/controller method.
- Render activate/deactivate/archive confirmation modals with subscriber counts.
- Guard against stale state on submit.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/admin/subscription-plans/index.blade.php app/Http/Controllers/Admin/SubscriptionPlanAdminController.php routes/admin.php tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(admin-plans): add impact-aware lifecycle confirmation modals"
```

## Task 7: Remove Refund and Refund-Adjacent Admin Operations

**Files:**
- Modify: `routes/admin.php`
- Modify: `app/Http/Controllers/Admin/PaymentAdminController.php`
- Modify: `resources/views/admin/payments/index.blade.php`
- Modify: `resources/views/admin/payments/show.blade.php`
- Modify: `resources/views/admin/payments/subscription-details.blade.php`
- Test: `tests/Feature/Admin/AdminTableUxTest.php`

**Step 1: Write the failing test**
- Assert refund route/action is unavailable.
- Assert process-refund and internal-note buttons are not rendered in payment index/show/detail pages.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: FAIL because refund/internal-note controls still exist.

**Step 3: Write minimal implementation**
- Remove refund/internal-note routes.
- Remove `processRefund` and `addInternalNote` action access from active admin flow.
- Remove all related buttons/forms from payment views.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add routes/admin.php app/Http/Controllers/Admin/PaymentAdminController.php resources/views/admin/payments/index.blade.php resources/views/admin/payments/show.blade.php resources/views/admin/payments/subscription-details.blade.php tests/Feature/Admin/AdminTableUxTest.php
git commit -m "feat(admin-payments): remove refund and internal-note operations"
```

## Task 8: Remove Grace/Retry/Schedule-Cancel/Reactivation Subscriber Actions

**Files:**
- Modify: `routes/admin.php`
- Modify: `app/Http/Controllers/Admin/SubscriberAdminController.php`
- Modify: `resources/views/admin/subscriber/index.blade.php`
- Modify: `resources/views/admin/subscriber/show.blade.php`
- Modify: `resources/views/admin/subscriber/partials/subscriptions-tab.blade.php`
- Test: `tests/Feature/Admin/AdminTableUxTest.php`

**Step 1: Write the failing test**
- Assert removed subscriber operations are not present in UI.
- Assert removed routes return 404 or are absent.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: FAIL while legacy actions/routes still exist.

**Step 3: Write minimal implementation**
- Remove subscriber routes for extend-grace, schedule-cancel, reactivate.
- Remove corresponding forms/buttons in subscriber views.
- Keep active/cancel operations only (or approved retained subset).

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add routes/admin.php app/Http/Controllers/Admin/SubscriberAdminController.php resources/views/admin/subscriber/index.blade.php resources/views/admin/subscriber/show.blade.php resources/views/admin/subscriber/partials/subscriptions-tab.blade.php tests/Feature/Admin/AdminTableUxTest.php
git commit -m "feat(admin-subscribers): remove grace and reactivation legacy operations"
```

## Task 9: Migrate Admin Legacy Popups to Toastify

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `resources/js/toast.js` (if shared helper extension is needed)
- Modify: touched admin views still using old popup markup/scripts
- Test: `tests/Feature/Admin/AdminTableUxTest.php`

**Step 1: Write the failing test**
- Add assertions for Toastify flash block in admin layout.
- Assert legacy popup block/markup markers are absent in touched views.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: FAIL because admin still uses old popup patterns.

**Step 3: Write minimal implementation**
- Add the same toast feedback pattern used by learner/instructor layouts into admin layout.
- Remove legacy popup scripts/containers in touched admin templates.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/layouts/admin.blade.php resources/js/toast.js tests/Feature/Admin/AdminTableUxTest.php
git commit -m "feat(admin-ui): unify flash feedback with toastify"
```

## Task 10: Add Admin Navbar Notification Center and Routes

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`
- Create: `app/Http/Controllers/Admin/NotificationController.php`
- Modify: `routes/admin.php`
- Create: `app/Notifications/Admin/NewSubscriberNotification.php`
- Create: `app/Notifications/Admin/PaymentStatusNotification.php`
- Create: `app/Notifications/Admin/PlanLifecycleNotification.php`
- Test: `tests/Feature/Admin/AdminActivityLogTest.php`

**Step 1: Write the failing test**
- Add tests that admin header shows unread badge and notification list hooks.
- Add tests for mark-read and mark-all-read routes.
- Add tests for notification creation on key events.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminActivityLogTest`
- Expected: FAIL due to missing admin notification controller/routes and notification classes.

**Step 3: Write minimal implementation**
- Add admin notification dropdown in top-right navbar (instructor-style UX family).
- Add mark-read/mark-all-read actions in admin notification controller.
- Introduce event notification classes for new subscriber, payment status, and plan lifecycle transitions.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminActivityLogTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/layouts/admin.blade.php app/Http/Controllers/Admin/NotificationController.php routes/admin.php app/Notifications/Admin/NewSubscriberNotification.php app/Notifications/Admin/PaymentStatusNotification.php app/Notifications/Admin/PlanLifecycleNotification.php tests/Feature/Admin/AdminActivityLogTest.php
git commit -m "feat(admin): add notification center and operational event notifications"
```

## Task 11: Update Terms and Policy Text

**Files:**
- Modify: `resources/views/legal/terms.blade.php`
- Test: `tests/Feature/Admin/AdminTableUxTest.php` (or add focused policy-content test)

**Step 1: Write the failing test**
- Add assertions that terms page includes:
  - no-refund policy for subscriptions.
  - plan deactivation rule preserving current entitlement until renewal/expiry.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: FAIL because required policy text is missing.

**Step 3: Write minimal implementation**
- Update terms wording with explicit clauses and clear language.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/legal/terms.blade.php tests/Feature/Admin/AdminTableUxTest.php
git commit -m "docs(terms): add no-refund and deactivation entitlement policy clauses"
```

## Task 12: End-to-End Verification and Report

**Files:**
- Modify: `docs/QUICK_TESTING_GUIDE.md`
- Create: `docs/plans/2026-03-25-admin-plan-lifecycle-policy-notifications-test-report.md`

**Step 1: Prepare verification checklist**
- Add pass/fail checklist for all acceptance criteria and policy removals.

**Step 2: Run tests**
- Run:
  - `php artisan test --filter=PlanManagementFlowTest`
  - `php artisan test --filter=AdminTableUxTest`
  - `php artisan test --filter=AdminActivityLogTest`
  - `php artisan test --filter=SubscriptionSchemaMigrationTest`
  - `php artisan test --filter=SubscriptionNormalizationBackfillTest`

**Step 3: Run full suite before final handoff**
- Run: `php artisan test`
- Expected: PASS or documented known failures unrelated to this scope.

**Step 4: Record evidence**
- Save outputs and manual verification notes in test report doc.

**Step 5: Commit**
```bash
git add docs/QUICK_TESTING_GUIDE.md docs/plans/2026-03-25-admin-plan-lifecycle-policy-notifications-test-report.md
git commit -m "test: document admin lifecycle and policy cleanup verification"
```

## Definition of Done

- Plans create/edit use one modal wizard with three approved steps.
- Billing preview appears in step 2 and entitlement checklist cards appear in step 3.
- Trial column is removed from plan table.
- Delete is replaced by archive, and archived plans can be restored from dedicated page.
- Activate/deactivate/archive confirmation modals include subscriber impact data.
- Refund/internal-note payment operations are removed from active admin flows.
- Grace/retry/schedule-cancel/reactivation subscriber operations are removed from active admin flows.
- Admin layout uses Toastify feedback in touched operations.
- Admin navbar includes notification bell with unread count and read management actions.
- Terms include no-refund clause and entitlement-until-renewal/expiry deactivation clause.
- All targeted tests and full suite are executed with results documented.
