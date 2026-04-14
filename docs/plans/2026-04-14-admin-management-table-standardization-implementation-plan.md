# Admin Management Table Standardization Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Standardize all in-scope admin management tables to the Payment Management baseline, add module revenue transaction and instructor roll-up detail pages, and enforce finalized paid transaction behavior for module revenue.

**Architecture:** Hybrid refactor. Build a small shared table standard layer (filters/actions/pagination shell), then adapt each page to that contract while preserving page-specific business logic. For module revenue, apply additive route/controller/view updates and a safe payout-status backfill migration.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, PHPUnit.

---

I am using the writing-plans skill to create this implementation plan.

## Task 1: Additive Module Revenue Finalization Migration

**Files:**
- Create: database/migrations/2026_04_14_000100_backfill_module_sale_ledger_paid_status.php
- Test: tests/Feature/Admin/AdminModuleRevenueFinalizedStateMigrationTest.php

**Step 1: Write the failing migration test**
- Assert seeded module_sale_ledgers with pending/payable become paid after migration.

**Step 2: Run the test and verify fail**
- Run: php artisan test --filter=AdminModuleRevenueFinalizedStateMigrationTest
- Expected: FAIL before migration exists.

**Step 3: Implement migration**
- Update payout_status to paid for non-paid rows in up().
- Reversible down() strategy should not drop data; keep no-op with clear comment or snapshot logic if project supports it.

**Step 4: Re-run test and verify pass**
- Run: php artisan test --filter=AdminModuleRevenueFinalizedStateMigrationTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: backfill module revenue ledger rows to paid

## Task 2: Set New Module Revenue Rows to Paid By Default

**Files:**
- Modify: app/Services/Monetization/ModuleSaleLedgerService.php
- Test: tests/Feature/Admin/AdminModuleRevenueDashboardTest.php

**Step 1: Write failing test**
- Add coverage asserting newly created ledger rows use payout_status paid when payment is completed module purchase.

**Step 2: Run test and verify fail**
- Run: php artisan test --filter=AdminModuleRevenueDashboardTest
- Expected: FAIL showing payout_status mismatch.

**Step 3: Implement minimal change**
- Change ledger creation default from pending to paid.

**Step 4: Re-run test and verify pass**
- Run: php artisan test --filter=AdminModuleRevenueDashboardTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: default module sale ledger payout status to paid

## Task 3: Add Module Revenue Transaction Details Route

**Files:**
- Modify: routes/admin.php
- Test: tests/Feature/Admin/AdminModuleRevenueTransactionDetailsPageTest.php

**Step 1: Write failing route test**
- Assert authenticated admin can access admin.monetization.module-revenue.transactions.show.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminModuleRevenueTransactionDetailsPageTest
- Expected: FAIL with route not found.

**Step 3: Add route**
- Add additive GET route under monetization group for transaction details.

**Step 4: Re-run and verify pass**
- Run: php artisan test --filter=AdminModuleRevenueTransactionDetailsPageTest
- Expected: PASS route resolution.

**Step 5: Commit**
- Commit message: feat: add module revenue transaction details route

## Task 4: Add Module Revenue Transaction Details Controller Action

**Files:**
- Modify: app/Http/Controllers/Admin/ModuleRevenueController.php
- Test: tests/Feature/Admin/AdminModuleRevenueTransactionDetailsPageTest.php

**Step 1: Extend failing test**
- Assert page renders transaction breakdown fields and related entities.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminModuleRevenueTransactionDetailsPageTest
- Expected: FAIL due to missing controller action/view.

**Step 3: Implement action**
- Add show transaction method with authorized eager loading.
- Keep controller thin and reuse existing query patterns.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminModuleRevenueTransactionDetailsPageTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: implement module revenue transaction details action

## Task 5: Create Module Revenue Transaction Details Blade

**Files:**
- Create: resources/views/admin/monetization/module-revenue-transaction-show.blade.php
- Test: tests/Feature/Admin/AdminModuleRevenueTransactionDetailsPageTest.php

**Step 1: Add failing assertions**
- Assert table-style metadata blocks and summary cards follow admin visual contract.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminModuleRevenueTransactionDetailsPageTest
- Expected: FAIL due to missing markup.

**Step 3: Implement Blade**
- Include transaction references, learner/instructor/module details, split breakdown, status badges.
- Follow Payment-style container, spacing, and action icon language.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminModuleRevenueTransactionDetailsPageTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: add module revenue transaction details page

## Task 6: Add Instructor Roll-Up Dedicated View Route And Action

**Files:**
- Modify: routes/admin.php
- Modify: app/Http/Controllers/Admin/ModuleRevenueController.php
- Test: tests/Feature/Admin/AdminModuleRevenueInstructorRollupViewTest.php

**Step 1: Write failing test**
- Assert roll-up row View links to dedicated route and page loads instructor financial context.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminModuleRevenueInstructorRollupViewTest
- Expected: FAIL (missing route/action).

**Step 3: Implement route and action**
- Add additive route for instructor roll-up detail.
- Add controller action with instructor-scoped transactions and stats.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminModuleRevenueInstructorRollupViewTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: add instructor roll-up dedicated view route and action

## Task 7: Create Instructor Roll-Up Dedicated Blade

**Files:**
- Create: resources/views/admin/monetization/module-revenue-instructor-show.blade.php
- Test: tests/Feature/Admin/AdminModuleRevenueInstructorRollupViewTest.php

**Step 1: Add failing assertions**
- Assert instructor summary cards plus standardized transactions table and pagination block.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminModuleRevenueInstructorRollupViewTest
- Expected: FAIL.

**Step 3: Implement Blade**
- Reuse Payment table contract: No. first, Actions last, icon action consistency, real-time filter controls.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminModuleRevenueInstructorRollupViewTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: add instructor roll-up detail page

## Task 8: Standardize Shared Admin Table Partials

**Files:**
- Modify: resources/views/admin/partials/table-filter-bar.blade.php
- Modify: resources/views/admin/partials/row-actions.blade.php
- Create: resources/views/admin/partials/table-pagination-footer.blade.php
- Test: tests/Feature/Admin/AdminTableUxTest.php

**Step 1: Add failing test coverage**
- Assert standardized filter bar/action/pagination signatures across target pages.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminTableUxTest
- Expected: FAIL due to old partial structure mismatches.

**Step 3: Implement partial updates**
- Keep backward-compatible includes.
- Normalize markup classes to Payment baseline tokens.
- Enforce Payment palette lock: brand + gray structural palette, emerald/amber/rose semantic states, and Payment-matching action icon button colors.

**Step 4: Re-run tests**
- Run: php artisan test --filter=AdminTableUxTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: refactor: standardize shared admin table partials

## Task 9: Standardize Users Management Table Shell

**Files:**
- Modify: resources/views/admin/users/index.blade.php
- Modify: resources/views/admin/users/partials/users-table.blade.php
- Modify: resources/views/admin/users/show.blade.php
- Test: tests/Feature/Admin/AdminUsersUiAlignmentTest.php

**Step 1: Write/update failing test**
- Assert Payment-style table shell, actions order, filters, and pagination placement.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminUsersUiAlignmentTest
- Expected: FAIL on UI alignment assertions.

**Step 3: Implement changes**
- Keep existing create wizard and role governance behavior.
- Align table structure and visual contract.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminUsersUiAlignmentTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: align users management to payment table standard

## Task 10: Standardize Instructor Applications Table Shell

**Files:**
- Modify: resources/views/admin/instructor-applications/index.blade.php
- Modify: resources/views/admin/instructor-applications/show.blade.php
- Test: tests/Feature/Admin/AdminInstructorApplicationsUiTest.php

**Step 1: Add failing assertions**
- Assert standardized columns, action icon ordering, filter block parity, pagination parity.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminInstructorApplicationsUiTest
- Expected: FAIL.

**Step 3: Implement changes**
- Preserve approval/rejection logic.
- Align shell to Payment standard.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminInstructorApplicationsUiTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: standardize instructor applications table UX

## Task 11: Standardize Content Review Queue Table Shell

**Files:**
- Modify: resources/views/admin/content-reviews/index.blade.php
- Modify: resources/views/admin/content-reviews/show.blade.php
- Test: tests/Feature/Admin/AdminContentReviewUiTest.php

**Step 1: Add failing assertions**
- Assert payment-standard table visuals and filter behavior while preserving moderation actions.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminContentReviewUiTest
- Expected: FAIL.

**Step 3: Implement changes**
- Keep Start Review and moderation links.
- Align shell, spacing, and action/icon consistency.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminContentReviewUiTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: align content review queue with admin table standard

## Task 12: Standardize Subscribers Management Table Shell

**Files:**
- Modify: resources/views/admin/subscriber/index.blade.php
- Modify: resources/views/admin/subscriber/show.blade.php
- Test: tests/Feature/Admin/AdminSubscriberDateAccuracyTest.php
- Create: tests/Feature/Admin/AdminSubscriberManagementUiStandardizationTest.php

**Step 1: Write failing UI standardization test**
- Assert Payment-style shell, action ordering, status badges, pagination placement.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminSubscriberManagementUiStandardizationTest
- Expected: FAIL.

**Step 3: Implement changes**
- Keep subscriber business actions unchanged.
- Normalize filter/table/action visuals and behavior.

**Step 4: Re-run tests**
- Run: php artisan test --filter=AdminSubscriberManagementUiStandardizationTest
- Run: php artisan test --filter=AdminSubscriberDateAccuracyTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: standardize subscriber management table UX

## Task 13: Standardize Plans Management List And Detail Surfaces

**Files:**
- Modify: resources/views/admin/subscription-plans/index.blade.php
- Modify: resources/views/admin/subscription-plans/show.blade.php
- Test: tests/Feature/Admin/PlanManagementFlowTest.php

**Step 1: Add failing assertions**
- Assert list/table-related regions and action icons align to Payment contract where applicable.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=PlanManagementFlowTest
- Expected: FAIL.

**Step 3: Implement changes**
- Keep wizard behavior and existing plan workflow logic.
- Align visual hierarchy and action consistency.

**Step 4: Re-run tests**
- Run: php artisan test --filter=PlanManagementFlowTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: standardize plans management list and detail UX

## Task 14: Standardize Commission Settings Table UX

**Files:**
- Modify: resources/views/admin/monetization/commission-settings.blade.php
- Test: tests/Feature/Admin/AdminCommissionSettingsTableUxStandardizationTest.php

**Step 1: Write failing test**
- Assert Payment-style table/filter/action/pagination contract.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminCommissionSettingsTableUxStandardizationTest
- Expected: FAIL.

**Step 3: Implement changes**
- Keep commission policy wizard behavior and authorization checks.
- Add standardized filter and pagination shells if applicable.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminCommissionSettingsTableUxStandardizationTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: standardize commission settings table UX

## Task 15: Standardize Enrollments Pages Used In Admin Context

**Files:**
- Modify: resources/views/instructor/enrollments/index.blade.php
- Modify: resources/views/instructor/enrollments/show.blade.php
- Test: tests/Feature/Admin/AdminSharedEnrollmentManagementTest.php

**Step 1: Add failing assertions**
- Assert page-level parity with admin table contract when accessed from admin shared routes.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminSharedEnrollmentManagementTest
- Expected: FAIL.

**Step 3: Implement changes**
- Preserve enrollment approval/rejection/archive/delete behavior.
- Align table/filter/action/pagination shell to Payment baseline.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminSharedEnrollmentManagementTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: feat: align shared enrollments table UX with admin standard

## Task 16: Refactor Module Revenue Index Actions To Finalized Model

**Files:**
- Modify: resources/views/admin/monetization/module-revenue.blade.php
- Modify: app/Http/Controllers/Admin/ModuleRevenueController.php
- Test: tests/Feature/Admin/AdminModuleRevenueDashboardTest.php
- Modify: tests/Feature/Admin/AdminPayoutStatusTransitionTest.php

**Step 1: Write failing test updates**
- Assert dashboard no longer shows Mark as Payable/Mark as Paid buttons.
- Assert View transaction details and archive/delete actions are shown.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminModuleRevenueDashboardTest
- Expected: FAIL.

**Step 3: Implement minimal behavior changes**
- Remove transition controls from active UI.
- Keep compatibility route/action where needed but no longer primary workflow.

**Step 4: Re-run tests**
- Run: php artisan test --filter=AdminModuleRevenueDashboardTest
- Run: php artisan test --filter=AdminPayoutStatusTransitionTest
- Expected: PASS after test updates reflect finalized model.

**Step 5: Commit**
- Commit message: refactor: align module revenue dashboard to finalized paid workflow

## Task 17: Standardize Payment Details, User Details, Plan Details, Subscriber Details Visual Parity

**Files:**
- Modify: resources/views/admin/payments/show.blade.php
- Modify: resources/views/admin/users/show.blade.php
- Modify: resources/views/admin/subscription-plans/show.blade.php
- Modify: resources/views/admin/subscriber/show.blade.php
- Test: tests/Feature/Admin/AdminSharedContentViewLayoutTest.php

**Step 1: Add failing assertions**
- Assert consistent detail-page card hierarchy, spacing, and action area style.

**Step 2: Run and verify fail**
- Run: php artisan test --filter=AdminSharedContentViewLayoutTest
- Expected: FAIL.

**Step 3: Implement changes**
- Align detail pages to shared admin detail shell standards.

**Step 4: Re-run test**
- Run: php artisan test --filter=AdminSharedContentViewLayoutTest
- Expected: PASS.

**Step 5: Commit**
- Commit message: style: align admin management detail pages with table standard

## Task 18: Verification Sweep And Final Safety Checks

**Files:**
- Create: docs/changelogs/2026-04-14-admin-management-table-standardization.md

**Step 1: Run targeted automated suite**
- Run:
  - php artisan test --filter=AdminTableUxTest
  - php artisan test --filter=AdminUsersUiAlignmentTest
  - php artisan test --filter=AdminInstructorApplicationsUiTest
  - php artisan test --filter=AdminContentReviewUiTest
  - php artisan test --filter=AdminSubscriberManagementUiStandardizationTest
  - php artisan test --filter=PlanManagementFlowTest
  - php artisan test --filter=AdminCommissionSettingsTableUxStandardizationTest
  - php artisan test --filter=AdminSharedEnrollmentManagementTest
  - php artisan test --filter=AdminModuleRevenueDashboardTest
  - php artisan test --filter=AdminModuleRevenueTransactionDetailsPageTest
  - php artisan test --filter=AdminModuleRevenueInstructorRollupViewTest

**Step 2: Run frontend build**
- Run: npm run build
- Expected: PASS.

**Step 3: Execute manual QA checklist**
- Verify all in-scope pages for strict payment-parity shells, icons, actions, and pagination behavior.
- Verify no non-baseline table-shell palette drift (for example sky/violet/indigo shell accents) unless explicitly approved semantic usage.

**Step 4: Write changelog**
- Document implemented pages, routes, tests, and residual risks.

**Step 5: Commit**
- Commit message: docs: record admin table standardization rollout and verification

---

## Manual QA Checklist

1. No. column is first and Actions column is last on every in-scope table
2. View icon appears first in action group where applicable
3. Archive/Delete visual treatment is consistent across pages
4. Filters update results in real time
5. Pagination layout and readability match payment baseline
6. Empty states use consistent spacing and tone
7. Module Revenue has no payout transition action buttons
8. Module Revenue row View opens transaction detail page
9. Instructor roll-up View opens dedicated instructor page
10. Detail pages (user/subscriber/plan/payment) follow consistent card hierarchy
11. Table shells, stat cards, filter bars, and action icons use Payment Management palette families

---

## Risks And Mitigations

1. Risk: Real-time filtering on large datasets can increase front-end payload size
- Mitigation: Use per-page payload caps and preserve server filtering fallback where necessary

2. Risk: Existing tests may assert old payout transitions
- Mitigation: Update tests with explicit finalized-state expectations

3. Risk: Shared partial updates can affect unrelated admin tables
- Mitigation: Add targeted UI tests and validate impacted pages manually

---

Plan complete and saved to docs/plans/2026-04-14-admin-management-table-standardization-implementation-plan.md.

Two execution options:

1. Subagent-Driven (this session) - dispatch a fresh subagent per task with review between tasks
2. Parallel Session (separate) - open a new session with executing-plans for batch execution

Which approach?
