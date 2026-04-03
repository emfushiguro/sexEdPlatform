# Module Monetization Transparency and Revenue Ledger Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement a transparent, auditable monetization system for paid modules with real-time split accounting, admin commission control, admin revenue reporting, and instructor earnings visibility.

**Architecture:** Reuse the existing module purchase payment flow as the transaction trigger, then write immutable split snapshots into dedicated ledger tables. Keep policy management in normalized tables (global plus instructor override), enforce snapshot-at-purchase semantics, and drive all admin/instructor financial pages from ledger reads instead of payment metadata parsing.

**Tech Stack:** Laravel 12, PHP 8.2, Eloquent ORM, Blade, Tailwind, Alpine.js, PHPUnit Feature and Unit tests.

---

I'm using the writing-plans skill to create the implementation plan.

## Task 1: Add Monetization Schema Migrations

**Files:**
- Create: `database/migrations/2026_04_02_120000_create_commission_policies_table.php`
- Create: `database/migrations/2026_04_02_120100_create_module_sale_ledgers_table.php`
- Create: `database/migrations/2026_04_02_120200_create_instructor_earnings_visibility_table.php`
- Create: `database/migrations/2026_04_02_120300_create_commission_policy_audits_table.php`
- Test: `tests/Feature/Admin/ModuleMonetizationSchemaTest.php`

**Step 1: Write the failing test**

Create schema tests asserting:
1. New monetization tables exist.
2. Key columns exist, including snapshot fields and payout status.
3. `module_sale_ledgers.payment_id` is unique.
4. Foreign keys resolve to users, payments, and module purchases correctly.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ModuleMonetizationSchemaTest`  
Expected: FAIL due to missing tables.

**Step 3: Write minimal implementation**

1. Add four migrations with required indexes and constraints.
2. Add enum-like string constraints where practical.
3. Add a unique index for idempotency on `payment_id`.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ModuleMonetizationSchemaTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add database/migrations/2026_04_02_120000_create_commission_policies_table.php database/migrations/2026_04_02_120100_create_module_sale_ledgers_table.php database/migrations/2026_04_02_120200_create_instructor_earnings_visibility_table.php database/migrations/2026_04_02_120300_create_commission_policy_audits_table.php tests/Feature/Admin/ModuleMonetizationSchemaTest.php`  
`git commit -m "feat(monetization): add commission policy and sale ledger schema"`

## Task 2: Add Monetization Models and Relationships

**Files:**
- Create: `app/Models/CommissionPolicy.php`
- Create: `app/Models/ModuleSaleLedger.php`
- Create: `app/Models/InstructorEarningsVisibility.php`
- Create: `app/Models/CommissionPolicyAudit.php`
- Modify: `app/Models/Payment.php`
- Modify: `app/Models/ModulePurchase.php`
- Modify: `app/Models/Module.php`
- Modify: `app/Models/User.php`
- Test: `tests/Unit/Models/ModuleMonetizationRelationshipsTest.php`

**Step 1: Write the failing test**

Add relationship tests for:
1. Payment -> moduleSaleLedger.
2. ModulePurchase -> moduleSaleLedger.
3. User(instructor) -> moduleSaleLedgers and instructorOverridePolicies.
4. Module -> moduleSaleLedgers.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ModuleMonetizationRelationshipsTest`  
Expected: FAIL due to missing models/relationships.

**Step 3: Write minimal implementation**

1. Add model classes with casts, fillable fields, and scopes.
2. Wire Eloquent relations in existing models.
3. Add helper methods for active policy and payout status checks.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ModuleMonetizationRelationshipsTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Models/CommissionPolicy.php app/Models/ModuleSaleLedger.php app/Models/InstructorEarningsVisibility.php app/Models/CommissionPolicyAudit.php app/Models/Payment.php app/Models/ModulePurchase.php app/Models/Module.php app/Models/User.php tests/Unit/Models/ModuleMonetizationRelationshipsTest.php`  
`git commit -m "feat(monetization): add ledger models and domain relationships"`

## Task 3: Implement Policy Resolution and Split Calculator Services

**Files:**
- Create: `app/Services/Monetization/CommissionPolicyResolver.php`
- Create: `app/Services/Monetization/RevenueSplitCalculator.php`
- Test: `tests/Unit/Services/CommissionPolicyResolverTest.php`
- Test: `tests/Unit/Services/RevenueSplitCalculatorTest.php`

**Step 1: Write the failing tests**

Add tests for:
1. Override precedence (instructor policy over global).
2. Effective date window filtering.
3. Half-up rounding behavior.
4. Gross and net basis math.

**Step 2: Run tests to verify they fail**

Run:
1. `php artisan test --filter=CommissionPolicyResolverTest`
2. `php artisan test --filter=RevenueSplitCalculatorTest`  
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Implement active policy lookup by scope and time.
2. Implement deterministic split calculator returning snapshot payload.
3. Add explicit exceptions for missing active policy.

**Step 4: Run tests to verify they pass**

Run:
1. `php artisan test --filter=CommissionPolicyResolverTest`
2. `php artisan test --filter=RevenueSplitCalculatorTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Services/Monetization/CommissionPolicyResolver.php app/Services/Monetization/RevenueSplitCalculator.php tests/Unit/Services/CommissionPolicyResolverTest.php tests/Unit/Services/RevenueSplitCalculatorTest.php`  
`git commit -m "feat(monetization): add commission resolution and split calculator services"`

## Task 4: Add Ledger Write Service and Hook Payment Completion

**Files:**
- Create: `app/Services/Monetization/ModuleSaleLedgerService.php`
- Modify: `app/Services/ModulePurchaseService.php`
- Modify: `app/Http/Controllers/PaymentController.php`
- Test: `tests/Feature/Learner/ModuleSaleLedgerCreationTest.php`
- Test: `tests/Feature/Learner/ModuleSaleLedgerIdempotencyTest.php`

**Step 1: Write the failing tests**

Add integration tests asserting:
1. Completed module payment writes exactly one ledger row.
2. Duplicate completion events do not duplicate ledger rows.
3. Ledger snapshot fields contain commission percent and amounts.

**Step 2: Run tests to verify they fail**

Run:
1. `php artisan test --filter=ModuleSaleLedgerCreationTest`
2. `php artisan test --filter=ModuleSaleLedgerIdempotencyTest`  
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Add ledger service method `createForCompletedModulePayment(Payment $payment)`.
2. Enforce `payment_scope=module_purchase` guard.
3. Wrap creation with transaction and unique-key safety handling.
4. Call service from module completion path in `ModulePurchaseService`.

**Step 4: Run tests to verify they pass**

Run:
1. `php artisan test --filter=ModuleSaleLedgerCreationTest`
2. `php artisan test --filter=ModuleSaleLedgerIdempotencyTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Services/Monetization/ModuleSaleLedgerService.php app/Services/ModulePurchaseService.php app/Http/Controllers/PaymentController.php tests/Feature/Learner/ModuleSaleLedgerCreationTest.php tests/Feature/Learner/ModuleSaleLedgerIdempotencyTest.php`  
`git commit -m "feat(monetization): persist real-time split ledger on module payment completion"`

## Task 5: Build Admin Commission Settings Management

**Files:**
- Modify: `routes/admin.php`
- Create: `app/Http/Controllers/Admin/CommissionSettingsController.php`
- Create: `app/Http/Requests/Admin/StoreCommissionPolicyRequest.php`
- Create: `app/Http/Requests/Admin/UpdateCommissionPolicyRequest.php`
- Create: `resources/views/admin/monetization/commission-settings.blade.php`
- Test: `tests/Feature/Admin/AdminCommissionSettingsTest.php`

**Step 1: Write the failing test**

Add feature tests for:
1. Admin can view commission settings page.
2. Admin can create global policy.
3. Admin can create instructor override.
4. Invalid overlapping effective windows are rejected.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminCommissionSettingsTest`  
Expected: FAIL due to missing route/controller/view.

**Step 3: Write minimal implementation**

1. Add admin routes under a monetization prefix.
2. Add controller actions index/store/update.
3. Add request validation with bounds and date overlap rules.
4. Render UI consistent with existing admin card/table design.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminCommissionSettingsTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add routes/admin.php app/Http/Controllers/Admin/CommissionSettingsController.php app/Http/Requests/Admin/StoreCommissionPolicyRequest.php app/Http/Requests/Admin/UpdateCommissionPolicyRequest.php resources/views/admin/monetization/commission-settings.blade.php tests/Feature/Admin/AdminCommissionSettingsTest.php`  
`git commit -m "feat(admin): add commission settings with global and instructor scopes"`

## Task 6: Add Commission Policy Audit Logging

**Files:**
- Modify: `app/Http/Controllers/Admin/CommissionSettingsController.php`
- Modify: `app/Services/AdminActivityLogService.php`
- Test: `tests/Feature/Admin/AdminCommissionPolicyAuditLogTest.php`

**Step 1: Write the failing test**

Add tests verifying:
1. Policy create/update writes audit row with before/after payload.
2. Actor id and timestamp are stored.
3. Non-admin users cannot write policy changes.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminCommissionPolicyAuditLogTest`  
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Add explicit audit writes on create/update/deactivate actions.
2. Reuse admin activity service where possible.
3. Ensure request metadata capture is sanitized.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminCommissionPolicyAuditLogTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Admin/CommissionSettingsController.php app/Services/AdminActivityLogService.php tests/Feature/Admin/AdminCommissionPolicyAuditLogTest.php`  
`git commit -m "feat(admin): enforce audit logging for commission policy mutations"`

## Task 7: Build Admin Module Revenue Dashboard

**Files:**
- Modify: `routes/admin.php`
- Create: `app/Http/Controllers/Admin/ModuleRevenueController.php`
- Create: `resources/views/admin/monetization/module-revenue.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/AdminModuleRevenueDashboardTest.php`

**Step 1: Write the failing test**

Add tests for:
1. Admin can access module revenue page.
2. KPI cards are present and computed from ledger rows.
3. Transaction table and instructor rollup rows render.
4. Filters by date/instructor/module/payout status work.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminModuleRevenueDashboardTest`  
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Build controller query composition for KPIs and tables.
2. Add filter handling with safe defaults.
3. Render admin-styled cards, table, and filter controls.
4. Add sidebar navigation entry in existing admin layout.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminModuleRevenueDashboardTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add routes/admin.php app/Http/Controllers/Admin/ModuleRevenueController.php resources/views/admin/monetization/module-revenue.blade.php resources/views/layouts/admin.blade.php tests/Feature/Admin/AdminModuleRevenueDashboardTest.php`  
`git commit -m "feat(admin): add module revenue analytics dashboard from sale ledger"`

## Task 8: Build Instructor Earnings Dashboard and Detail Page

**Files:**
- Modify: `routes/instructor.php`
- Create: `app/Http/Controllers/Instructor/ModuleEarningsController.php`
- Create: `resources/views/instructor/earnings/index.blade.php`
- Create: `resources/views/instructor/earnings/show.blade.php`
- Modify: `resources/views/layouts/instructor-app.blade.php`
- Test: `tests/Feature/Instructor/InstructorModuleEarningsTest.php`

**Step 1: Write the failing test**

Add tests asserting:
1. Instructor can access earnings list and detail page.
2. Summary cards show totals from instructor-scoped ledger rows.
3. Table includes required columns and View action.
4. Other instructors' rows are inaccessible.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorModuleEarningsTest`  
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Add instructor routes for index/show/delete-visibility actions.
2. Build controller queries scoped by `instructor_id = auth()->id()`.
3. Build list and detail Blade views with existing instructor panel language.
4. Add nav item in instructor layout.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorModuleEarningsTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add routes/instructor.php app/Http/Controllers/Instructor/ModuleEarningsController.php resources/views/instructor/earnings/index.blade.php resources/views/instructor/earnings/show.blade.php resources/views/layouts/instructor-app.blade.php tests/Feature/Instructor/InstructorModuleEarningsTest.php`  
`git commit -m "feat(instructor): add module earnings dashboard and transaction detail view"`

## Task 9: Add Instructor Soft Delete Visibility Workflow

**Files:**
- Modify: `app/Http/Controllers/Instructor/ModuleEarningsController.php`
- Modify: `resources/views/instructor/earnings/index.blade.php`
- Test: `tests/Feature/Instructor/InstructorEarningsSoftDeleteTest.php`

**Step 1: Write the failing test**

Add tests for:
1. Delete action stores visibility deletion record.
2. Deleted rows are hidden from instructor list.
3. Admin module revenue page still counts hidden rows.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorEarningsSoftDeleteTest`  
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Add delete endpoint with confirmation and optional reason capture.
2. Persist visibility row instead of deleting ledger record.
3. Update list query to exclude soft-deleted visibility rows by default.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorEarningsSoftDeleteTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Instructor/ModuleEarningsController.php resources/views/instructor/earnings/index.blade.php tests/Feature/Instructor/InstructorEarningsSoftDeleteTest.php`  
`git commit -m "feat(instructor): support soft-delete visibility for earnings rows"`

## Task 10: Add Transparency Messaging in Module Pricing UI and Earnings Page

**Files:**
- Modify: `resources/views/instructor/modules/partials/module-modal.blade.php`
- Modify: `resources/views/instructor/modules/create.blade.php`
- Modify: `resources/views/instructor/modules/edit.blade.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/ModuleEarningsController.php`
- Test: `tests/Feature/Instructor/InstructorCommissionTransparencyNoticeTest.php`

**Step 1: Write the failing test**

Add tests asserting:
1. Module pricing UI shows commission notice text.
2. Notice reflects effective policy percent for current instructor.
3. Earnings page includes formula explanation and no-refund policy note.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorCommissionTransparencyNoticeTest`  
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Inject effective commission payload into module create/edit contexts.
2. Render commission notice and estimated net earnings helper text.
3. Render explanatory block on instructor earnings page.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorCommissionTransparencyNoticeTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add resources/views/instructor/modules/partials/module-modal.blade.php resources/views/instructor/modules/create.blade.php resources/views/instructor/modules/edit.blade.php app/Http/Controllers/Instructor/ModuleController.php app/Http/Controllers/Instructor/ModuleEarningsController.php tests/Feature/Instructor/InstructorCommissionTransparencyNoticeTest.php`  
`git commit -m "feat(instructor): add commission transparency notices in pricing and earnings views"`

## Task 11: Add Payout Status Transitions and Admin Controls

**Files:**
- Modify: `app/Http/Controllers/Admin/ModuleRevenueController.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/admin/monetization/module-revenue.blade.php`
- Test: `tests/Feature/Admin/AdminPayoutStatusTransitionTest.php`

**Step 1: Write the failing test**

Add tests verifying:
1. Admin can transition payout status (`pending` -> `payable` -> `paid`).
2. Invalid transitions are blocked.
3. Transition events are auditable.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminPayoutStatusTransitionTest`  
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Add guarded transition endpoints and policy checks.
2. Validate allowed state machine transitions.
3. Record audit metadata for each state change.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminPayoutStatusTransitionTest`  
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Admin/ModuleRevenueController.php routes/admin.php resources/views/admin/monetization/module-revenue.blade.php tests/Feature/Admin/AdminPayoutStatusTransitionTest.php`  
`git commit -m "feat(admin): add payout status transition workflow for module sale ledger"`

## Task 12: End-to-End Regression and Verification Sweep

**Files:**
- Create: `tests/Feature/Learner/LearnerModulePurchaseMonetizationRegressionTest.php`
- Modify: `tests/Feature/Learner/LearnerModulePaymentWebhookTest.php`
- Modify: `tests/Feature/Learner/LearnerPaymentHistoryModuleTransactionsTest.php`

**Step 1: Write the failing tests**

Add regression coverage for:
1. Existing learner module purchase flow remains functional.
2. Ledger row appears without breaking enrollment unlock.
3. Existing payment history still renders module transactions.

**Step 2: Run tests to verify they fail first**

Run:
1. `php artisan test --filter=LearnerModulePurchaseMonetizationRegressionTest`
2. `php artisan test --filter=LearnerModulePaymentWebhookTest`
3. `php artisan test --filter=LearnerPaymentHistoryModuleTransactionsTest`  
Expected: initial FAIL where changes are pending.

**Step 3: Write minimal implementation adjustments**

Patch any regressions discovered by tests without introducing unrelated refactors.

**Step 4: Run focused and broad verification**

Run:
1. `php artisan test --filter=ModuleSaleLedger`
2. `php artisan test --filter=AdminModuleRevenueDashboardTest`
3. `php artisan test --filter=InstructorModuleEarningsTest`
4. `php artisan test --filter=LearnerModulePaymentWebhookTest`

Expected: PASS.

**Step 5: Commit**

Run:
`git add tests/Feature/Learner/LearnerModulePurchaseMonetizationRegressionTest.php tests/Feature/Learner/LearnerModulePaymentWebhookTest.php tests/Feature/Learner/LearnerPaymentHistoryModuleTransactionsTest.php`  
`git commit -m "test(monetization): add cross-flow regression coverage for ledger integration"`

---

## Verification Checklist

1. Completed module purchases create exactly one ledger record.
2. Instructor override policy is honored over global policy.
3. Historical ledger snapshot values do not change after policy edits.
4. Admin commission policy changes are auditable.
5. Admin revenue page totals and tables reconcile with ledger data.
6. Instructor earnings page shows required columns and summary cards.
7. Instructor soft delete hides rows only from instructor view.
8. No-refund policy messaging appears in instructor-facing monetization areas.
9. Existing learner payment + enrollment flows remain stable.
10. Reporting displays a clear note that analytics start from release date (no backfill).

## Rollout Notes

1. Seed one active global policy before enabling ledger writes.
2. Release order:
   - schema + models
   - services + ledger writes
   - admin pages
   - instructor pages
   - transparency messages
3. Post-release monitoring:
   - compare `module_purchases` completed count vs `module_sale_ledgers` count
   - verify no duplicate ledger rows per payment id
   - verify policy audit rows are being written on every admin change
