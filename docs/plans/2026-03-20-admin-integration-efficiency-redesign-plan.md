# Admin Integration Efficiency Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Streamline admin operations by removing non-functional sidebar entries, aligning admin login branding with learner auth UI, and replacing plan creation page flow with a modal-based, learner-focused setup.

**Architecture:** Use a balanced refactor that preserves existing subscription domain models while simplifying admin input UX. Keep controllers thin by validating through Form Requests and mapping payloads in service/controller transaction blocks. Implement modal-first creation in existing plans management UI and enforce strict server-side validation for pricing, dates, audience, and entitlements.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, PHPUnit

---

## Task 1: Remove Non-Implemented Sidebar Items

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/AdminDashboardMetricsTest.php`

**Step 1: Write the failing test**

Add a test that renders an admin page using the sidebar and asserts missing labels:
- Calendar
- Seminars
- Messages
- Organizations
- Communication

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_admin_sidebar_hides_unimplemented_navigation_items`
Expected: FAIL because items still render.

**Step 3: Write minimal implementation**

Update sidebar markup to remove those labels/links while preserving existing implemented sections.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_admin_sidebar_hides_unimplemented_navigation_items`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/layouts/admin.blade.php tests/Feature/Admin/AdminDashboardMetricsTest.php
git commit -m "feat(admin): hide unimplemented sidebar navigation items"
```

---

## Task 2: Redesign Admin Login to Match Learner Theme

**Files:**
- Modify: `resources/views/auth/admin-login.blade.php`
- Review reference: `resources/views/auth/learner-login.blade.php`
- Test: `tests/Feature/Auth/AdminLoginPageUiTest.php`

**Step 1: Write the failing test**

Add/adjust assertions for learner-theme parity signals in admin login (layout structure, branded split treatment, role-specific copy retained).

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminLoginPageUiTest`
Expected: FAIL on new UI assertions.

**Step 3: Write minimal implementation**

Refactor admin login Blade to follow learner-auth visual system while keeping admin-specific security text and route actions unchanged.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminLoginPageUiTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/auth/admin-login.blade.php tests/Feature/Auth/AdminLoginPageUiTest.php
git commit -m "feat(auth): align admin login ui with learner branding"
```

---

## Task 3: Move Plan Creation to Modal on Plans Screen

**Files:**
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Modify: `routes/admin.php`
- Modify: `app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php`
- Modify or de-prioritize usage: `resources/views/admin/subscriber/plan-create.blade.php`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**

Add a feature test asserting:
- plans index renders create modal trigger and modal form
- primary create flow posts from index modal
- no redirect dependency on dedicated create page

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PlanManagementFlowTest`
Expected: FAIL because current flow still relies on separate create page.

**Step 3: Write minimal implementation**

Embed modal form in plans index page, wire submit action to existing store endpoint, and keep backward compatibility where safe.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PlanManagementFlowTest`
Expected: PASS for modal-first assertions.

**Step 5: Commit**

```bash
git add resources/views/admin/subscription-plans/index.blade.php routes/admin.php app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php resources/views/admin/subscriber/plan-create.blade.php tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(admin): implement modal-first subscription plan creation"
```

---

## Task 4: Implement Billing Mode, Date Logic, and Audience Validation

**Files:**
- Modify: `app/Http/Requests/Admin/StorePlanRequest.php`
- Modify: `app/Http/Requests/Admin/UpdatePlanRequest.php`
- Modify: `app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php`
- Modify: `app/Models/SubscriptionPlan.php`
- Create migration (if needed): `database/migrations/YYYY_MM_DD_HHMMSS_add_plan_audience_and_availability_fields.php`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing tests**

Add validation and persistence tests for:
- billing mode in monthly/annual/custom
- monthly/annual auto date preview data (admin context)
- custom period requires start/end and valid order
- audience present, learner-only accepted for now
- decimal pricing non-negative and max 2 precision

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PlanManagementFlowTest`
Expected: FAIL on new rules/persistence assertions.

**Step 3: Write minimal implementation**

Implement request rules and controller normalization:
- monthly/annual: auto-compute admin preview range and keep learner subscription runtime behavior purchase-based
- custom: enforce required date range
- persist audience with learner default and future-safe enum values
- convert decimal price to existing storage shape safely

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PlanManagementFlowTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Requests/Admin/StorePlanRequest.php app/Http/Requests/Admin/UpdatePlanRequest.php app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php app/Models/SubscriptionPlan.php database/migrations tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(subscriptions): add billing mode, custom dates, and learner audience validation"
```

---

## Task 5: Implement Learner-Focused Entitlements UX and Mapping

**Files:**
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Modify: `app/Http/Requests/Admin/StorePlanRequest.php`
- Modify: `app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php`
- Modify: `app/Models/FeatureCatalog.php`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**

Add assertions for categorized entitlement payload handling:
- Account and Profile
- Learning Access
- Quiz and Practice

Include checks for unlimited toggle and numeric limit behavior.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PlanManagementFlowTest`
Expected: FAIL because existing technical entitlement rows do not match new grouped UI payload.

**Step 3: Write minimal implementation**

Implement category-based checkbox UI and map to feature catalog + plan entitlement records for:
- Unlimited Username Changes
- Profile customization perks (future)
- Early access to profile features (future)
- Certificate PDF download
- Premium module access
- Lesson attachment downloads
- Advanced topic bundles
- Unlimited Shields / Unlimited Quiz Retaking
- Monthly streak savers

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PlanManagementFlowTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/admin/subscription-plans/index.blade.php app/Http/Requests/Admin/StorePlanRequest.php app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php app/Models/FeatureCatalog.php tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(subscriptions): simplify learner entitlement selection and mapping"
```

---

## Task 6: Regression Verification and Final Validation

**Files:**
- Modify as needed from prior tasks
- Test sweep: `tests/Feature/Admin/PlanManagementFlowTest.php`, `tests/Feature/Auth/AdminLoginPageUiTest.php`, `tests/Feature/Admin/AdminDashboardMetricsTest.php`

**Step 1: Run focused regression tests**

Run:
- `php artisan test --filter=PlanManagementFlowTest`
- `php artisan test --filter=AdminLoginPageUiTest`
- `php artisan test --filter=test_admin_sidebar_hides_unimplemented_navigation_items`

Expected: PASS.

**Step 2: Run full test suite**

Run: `php artisan test`
Expected: PASS (or document unrelated pre-existing failures explicitly).

**Step 3: Address failures minimally**

If failures are related to this feature, apply smallest safe fix and re-run affected tests.

**Step 4: Commit final stabilization changes**

```bash
git add .
git commit -m "test(admin): finalize admin integration efficiency redesign verification"
```

---

## Notes for Execution

- Keep controllers thin; place non-trivial mapping logic in service-style methods/classes if it starts growing.
- Preserve backward compatibility where possible with existing subscription records.
- Prefer additive migration changes over destructive schema edits.
- Ensure modal validation errors are rendered inline and preserve entered values.
