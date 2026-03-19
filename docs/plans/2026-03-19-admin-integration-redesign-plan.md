# Admin Integration Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement a normalized subscription domain and admin UX redesign (including admin login redesign) aligned with current platform theme and learner-side subscription parity.

**Architecture:** Keep Laravel server-rendered architecture and thin controllers, move business logic into services, and migrate from single-price JSON-feature plans to normalized plan prices + feature entitlements with full admin activity logging. Reuse/adapt TailAdmin UI component patterns while preserving Concious Connections branding and existing auth/payment flows.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, PHPUnit, Spatie roles/permissions, existing SubscriptionService and SubscriptionDunningService.

---

## Task 1: Add Normalized Subscription Schema

**Files:**
- Create: `database/migrations/2026_03_19_100001_create_plan_prices_table.php`
- Create: `database/migrations/2026_03_19_100002_create_feature_catalog_table.php`
- Create: `database/migrations/2026_03_19_100003_create_plan_feature_entitlements_table.php`
- Create: `database/migrations/2026_03_19_100004_add_normalized_columns_to_subscribers_table.php`
- Create: `database/migrations/2026_03_19_100005_create_admin_activity_logs_table.php`
- Create: `tests/Feature/Admin/SubscriptionSchemaMigrationTest.php`

**Step 1: Write the failing test**
- Add `SubscriptionSchemaMigrationTest` that asserts new tables/columns exist after migration.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=SubscriptionSchemaMigrationTest`
- Expected: FAIL (missing tables/columns)

**Step 3: Write minimal implementation**
- Create migrations for `plan_prices`, `feature_catalog`, `plan_feature_entitlements`, `admin_activity_logs`, and normalized subscriber columns.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=SubscriptionSchemaMigrationTest`
- Expected: PASS

**Step 5: Commit**
- `git add database/migrations tests/Feature/Admin/SubscriptionSchemaMigrationTest.php`
- `git commit -m "feat(subscription): add normalized schema for pricing and entitlements"`

---

## Task 2: Add Models, Relationships, and Casting

**Files:**
- Create: `app/Models/PlanPrice.php`
- Create: `app/Models/FeatureCatalog.php`
- Create: `app/Models/PlanFeatureEntitlement.php`
- Create: `app/Models/AdminActivityLog.php`
- Modify: `app/Models/SubscriptionPlan.php`
- Modify: `app/Models/Subscription.php`
- Create: `tests/Unit/Models/SubscriptionDomainRelationsTest.php`

**Step 1: Write the failing test**
- Add tests for model relationships, key casts, and accessors needed by admin/learner screens.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=SubscriptionDomainRelationsTest`
- Expected: FAIL (class/relation missing)

**Step 3: Write minimal implementation**
- Add new models and wire Eloquent relationships between plan, price, entitlements, and subscriptions.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=SubscriptionDomainRelationsTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Models tests/Unit/Models/SubscriptionDomainRelationsTest.php`
- `git commit -m "feat(subscription): add normalized domain models and relations"`

---

## Task 3: Build Entitlement Resolution Service

**Files:**
- Create: `app/Services/EntitlementService.php`
- Create: `app/Support/SubscriptionFeatureKeys.php`
- Create: `tests/Unit/Services/EntitlementServiceTest.php`

**Step 1: Write the failing test**
- Add tests for boolean, quota, unlimited, and missing-feature fallback behavior.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=EntitlementServiceTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Implement service methods:
  - `canAccessFeature(User $user, string $featureKey): bool`
  - `getFeatureQuota(User $user, string $featureKey): ?int`
  - `getSubscriptionSummary(User $user): array`
- Add canonical key `unlimited_shields`.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=EntitlementServiceTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Services/EntitlementService.php app/Support/SubscriptionFeatureKeys.php tests/Unit/Services/EntitlementServiceTest.php`
- `git commit -m "feat(subscription): add entitlement resolution service"`

---

## Task 4: Implement Lifecycle State Machine and Dunning Updates

**Files:**
- Modify: `app/Enums/SubscriptionStatus.php`
- Modify: `app/Services/SubscriptionService.php`
- Modify: `app/Services/SubscriptionDunningService.php`
- Create: `tests/Unit/Services/SubscriptionLifecycleStateTest.php`

**Step 1: Write the failing test**
- Add tests for transitions:
  - active -> scheduled_cancel
  - active -> grace_period
  - grace_period -> expired
  - grace_period -> active (recovery)

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=SubscriptionLifecycleStateTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Add transition methods and guard rules in services.
- Ensure retry/reminder hooks remain idempotent.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=SubscriptionLifecycleStateTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Enums/SubscriptionStatus.php app/Services/SubscriptionService.php app/Services/SubscriptionDunningService.php tests/Unit/Services/SubscriptionLifecycleStateTest.php`
- `git commit -m "feat(subscription): implement lifecycle and dunning transitions"`

---

## Task 5: Add Admin Activity Logging Service

**Files:**
- Create: `app/Services/AdminActivityLogService.php`
- Modify: `app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php`
- Modify: `app/Http/Controllers/Admin/SubscriberAdminController.php`
- Modify: `app/Http/Controllers/Admin/PaymentAdminController.php`
- Modify: `app/Http/Controllers/Admin/UserAdminController.php`
- Create: `tests/Feature/Admin/AdminActivityLogTest.php`

**Step 1: Write the failing test**
- Add feature test that performs one admin mutation and asserts a log record is created.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminActivityLogTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Implement centralized logger and call it in admin mutation endpoints.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminActivityLogTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Services/AdminActivityLogService.php app/Http/Controllers/Admin tests/Feature/Admin/AdminActivityLogTest.php`
- `git commit -m "feat(admin): add full activity logging for critical mutations"`

---

## Task 6: Refactor Plan Management to Multi-Price + Entitlements

**Files:**
- Create: `app/Http/Requests/Admin/StorePlanRequest.php`
- Create: `app/Http/Requests/Admin/UpdatePlanRequest.php`
- Modify: `app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php`
- Modify: `resources/views/admin/subscriber/index.blade.php`
- Create: `resources/views/admin/subscriber/plan-create.blade.php`
- Create: `resources/views/admin/subscriber/plan-edit.blade.php`
- Create: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**
- Add feature test for creating a plan with two durations and normalized feature entitlements.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Use form requests for validation.
- Persist plan, prices, entitlements in a transaction.
- Keep backward-compatible reads for existing screens during transition.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Http/Requests/Admin app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php resources/views/admin/subscriber tests/Feature/Admin/PlanManagementFlowTest.php`
- `git commit -m "feat(admin): support multi-duration plan pricing and entitlements"`

---

## Task 7: Implement Hybrid Command Center Dashboard

**Files:**
- Create: `app/Services/AdminDashboardService.php`
- Modify: `routes/admin.php`
- Create: `app/Http/Controllers/Admin/DashboardController.php`
- Modify: `resources/views/admin/dashboard.blade.php`
- Create: `tests/Feature/Admin/AdminDashboardMetricsTest.php`

**Step 1: Write the failing test**
- Add test to assert dashboard response includes risk, leakage, and growth metric blocks.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminDashboardMetricsTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Add service-driven metric aggregation.
- Update route to controller action and render metric cards with CTA links.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminDashboardMetricsTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Services/AdminDashboardService.php app/Http/Controllers/Admin/DashboardController.php routes/admin.php resources/views/admin/dashboard.blade.php tests/Feature/Admin/AdminDashboardMetricsTest.php`
- `git commit -m "feat(admin): implement hybrid command center dashboard"`

---

## Task 8: Standardize Admin Tables and Actions

**Files:**
- Modify: `resources/views/admin/users/index.blade.php`
- Modify: `resources/views/admin/subscriber/index.blade.php`
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Modify: `resources/views/admin/payments/index.blade.php`
- Create: `resources/views/admin/partials/table-filter-bar.blade.php`
- Create: `resources/views/admin/partials/row-actions.blade.php`
- Create: `tests/Feature/Admin/AdminTableUxTest.php`

**Step 1: Write the failing test**
- Add test assertions for expected key columns and action controls on each table page.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Add reusable filter/action partials.
- Ensure columns/actions match approved design spec.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/admin tests/Feature/Admin/AdminTableUxTest.php`
- `git commit -m "feat(admin-ui): standardize management tables and actions"`

---

## Task 9: Align Learner Subscription UX with New Domain

**Files:**
- Modify: `app/Http/Controllers/Learner/SubscriptionController.php`
- Modify: `resources/views/subscriptions/index.blade.php`
- Modify: `resources/views/subscriptions/upgrade.blade.php`
- Modify: `resources/views/learn/dashboard.blade.php`
- Create: `tests/Feature/Learner/LearnerSubscriptionParityTest.php`

**Step 1: Write the failing test**
- Add test asserting learner subscription page shows multi-duration pricing and status labels from normalized subscription states.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=LearnerSubscriptionParityTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Render prices from `plan_prices`.
- Surface status chips and self-service actions using centralized subscription summary.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=LearnerSubscriptionParityTest`
- Expected: PASS

**Step 5: Commit**
- `git add app/Http/Controllers/Learner/SubscriptionController.php resources/views/subscriptions resources/views/learn/dashboard.blade.php tests/Feature/Learner/LearnerSubscriptionParityTest.php`
- `git commit -m "feat(learner): align subscription UX with normalized domain"`

---

## Task 10: Redesign Admin Login UI to Match Learner Theme

**Files:**
- Modify: `resources/views/auth/admin-login.blade.php`
- Reference: `resources/views/auth/learner-login.blade.php`
- Optional shared partials: `resources/views/components/auth/` (create only if needed)
- Create: `tests/Feature/Auth/AdminLoginPageUiTest.php`

**Step 1: Write the failing test**
- Add feature test for admin login rendering expected role heading, brand assets, and submit route.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminLoginPageUiTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Redesign admin login to use role-specific variant of learner-level polish.
- Reuse/adapt TailAdmin layout primitives where appropriate, while keeping Concious Connections visual identity.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminLoginPageUiTest`
- Expected: PASS

**Step 5: Commit**
- `git add resources/views/auth/admin-login.blade.php tests/Feature/Auth/AdminLoginPageUiTest.php`
- `git commit -m "feat(auth): redesign admin login UI to match platform theme"`

---

## Task 11: Compatibility Backfill and Final Verification

**Files:**
- Create: `database/seeders/SubscriptionDomainBackfillSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `tests/Feature/Admin/SubscriptionBackfillCompatibilityTest.php`

**Step 1: Write the failing test**
- Add test that seeds legacy-style plan data and asserts compatibility mapping populates normalized tables.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=SubscriptionBackfillCompatibilityTest`
- Expected: FAIL

**Step 3: Write minimal implementation**
- Add backfill seeder and compatibility mapping logic.

**Step 4: Run focused tests + full suite**
- Run: `php artisan test --filter=SubscriptionBackfillCompatibilityTest`
- Expected: PASS
- Run: `php artisan test`
- Expected: PASS

**Step 5: Commit**
- `git add database/seeders tests/Feature/Admin/SubscriptionBackfillCompatibilityTest.php`
- `git commit -m "chore(subscription): add backfill compatibility and verification"`

---

## Implementation Notes

- Keep controllers thin; move domain logic to services.
- Use form requests for admin mutation validation.
- Use explicit transactions for plan+price+entitlement writes.
- Maintain `unlimited_shields` as canonical feature key.
- Do not add Family Premium in this implementation.

## Skill References

- `@!skills/executing-plans/SKILL.md`
- `@!skills/test-driven-development/SKILL.md`
- `@!skills/verification-before-completion/SKILL.md`
- `@!skills/systematic-debugging/SKILL.md`

## Handoff

Plan complete and saved to `docs/plans/2026-03-19-admin-integration-redesign-plan.md`.

Two execution options:

1. Subagent-Driven (this session) - I dispatch a fresh subagent per task, review between tasks, and iterate quickly.
2. Parallel Session (separate) - Open a new session with executing-plans and run the plan in a dedicated execution thread.
