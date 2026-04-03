# Learner Module Monetization and Enrollment Visibility Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver learner-side module pricing visibility, paid checkout via PayMongo, enrollment-limit enforcement, and instructor transparency with complete free-versus-paid flows.

**Architecture:** Reuse the existing payment infrastructure and PayMongo link service, then introduce a dedicated module purchase domain record linked to payments. Keep controller logic thin by moving orchestration into a focused service that handles eligibility, checkout creation, and post-payment enrollment. Preserve admin publication gating and existing enrollment modes while adding paid purchase states.

**Tech Stack:** Laravel 12, PHP 8.2, Eloquent, Blade, Tailwind, PayMongo API, PHPUnit Feature tests.

---

I'm using the writing-plans skill to create the implementation plan.

## Task 1: Add Module Purchase Schema

**Files:**
- Create: `database/migrations/2026_03_31_120000_create_module_purchases_table.php`
- Create: `app/Models/ModulePurchase.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Module.php`
- Modify: `app/Models/Payment.php`
- Test: `tests/Feature/Learner/LearnerModulePurchaseSchemaTest.php`

**Step 1: Write the failing test**

Create schema/relationship tests asserting:
1. `module_purchases` table exists.
2. Required columns and indexes exist.
3. User/module/payment relationships resolve.
4. One completed purchase per learner per module uniqueness behavior is enforced.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerModulePurchaseSchemaTest`
Expected: FAIL due to missing table/model relationships.

**Step 3: Write minimal implementation**

1. Add migration with columns:
   - `user_id`, `module_id`, `payment_id` nullable
   - `amount`, `currency`
   - `status` enum/string
   - `purchased_at` nullable
   - `metadata` json nullable
2. Add indexes and a unique constraint for completed ownership strategy.
3. Create `ModulePurchase` model with casts and relationships.
4. Add relationships in `User`, `Module`, and optional inverse in `Payment`.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerModulePurchaseSchemaTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add database/migrations/2026_03_31_120000_create_module_purchases_table.php app/Models/ModulePurchase.php app/Models/User.php app/Models/Module.php app/Models/Payment.php tests/Feature/Learner/LearnerModulePurchaseSchemaTest.php`
`git commit -m "feat: add module purchase domain schema"`

## Task 2: Build Module Purchase Orchestration Service

**Files:**
- Create: `app/Services/ModulePurchaseService.php`
- Create: `app/Support/ModulePriceFormatter.php`
- Test: `tests/Unit/Services/ModulePurchaseServiceTest.php`

**Step 1: Write the failing test**

Add unit tests for service methods:
1. `canStartPurchase()` handles full capacity, parent approval, and existing purchase.
2. `startCheckout()` prepares pending purchase and payment metadata.
3. `finalizeSuccessfulPayment()` marks purchase complete and triggers enrollment branching.
4. Enrollment creation behavior differs for auto/manual enrollment mode.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ModulePurchaseServiceTest`
Expected: FAIL due to missing service and logic.

**Step 3: Write minimal implementation**

1. Implement service with clear methods:
   - eligibility checks
   - pending purchase creation
   - payment metadata preparation
   - completion finalization and idempotency checks
2. Add formatter helper for consistent PHP pricing display.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ModulePurchaseServiceTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Services/ModulePurchaseService.php app/Support/ModulePriceFormatter.php tests/Unit/Services/ModulePurchaseServiceTest.php`
`git commit -m "feat: add module purchase orchestration service"`

## Task 3: Extend Learner Module Routes and Controller Actions

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Test: `tests/Feature/Learner/LearnerPaidModuleFlowTest.php`

**Step 1: Write the failing test**

Create feature tests for:
1. Start Learning always routes to module overview.
2. Free module enroll action works from overview.
3. Paid module action starts checkout only when eligible.
4. Parent-approval requirement blocks payment-link creation.
5. Full module blocks checkout.
6. Existing purchase bypasses duplicate checkout.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerPaidModuleFlowTest`
Expected: FAIL due to missing paid module route/actions.

**Step 3: Write minimal implementation**

1. Add learner module route for paid checkout start (POST action).
2. Inject `ModulePurchaseService` in controller.
3. Update enroll/action branching:
   - free module uses existing enrollment logic
   - paid module uses purchase service
4. Return clear flash messages for full, pending-parent, already-purchased states.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerPaidModuleFlowTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add routes/web.php app/Http/Controllers/Learner/ModuleController.php tests/Feature/Learner/LearnerPaidModuleFlowTest.php`
`git commit -m "feat: add learner paid module checkout entry flow"`

## Task 4: Wire PayMongo Payment Creation for Module Scope

**Files:**
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `app/Services/PayMongoPaymentLinkService.php`
- Modify: `app/Models/Payment.php`
- Test: `tests/Feature/Learner/LearnerModulePaymentCreationTest.php`

**Step 1: Write the failing test**

Add tests asserting:
1. Module-scope payment record stores module metadata.
2. PayMongo link creation receives module context.
3. Payment pending page can represent module-scope transactions.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerModulePaymentCreationTest`
Expected: FAIL due to missing module payment scope behavior.

**Step 3: Write minimal implementation**

1. Extend payment creation path to support `payment_scope=module_purchase`.
2. Preserve existing subscription behavior unchanged.
3. Ensure metadata includes `module_id`, `module_purchase_id`, learner context.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerModulePaymentCreationTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/PaymentController.php app/Services/PayMongoPaymentLinkService.php app/Models/Payment.php tests/Feature/Learner/LearnerModulePaymentCreationTest.php`
`git commit -m "feat: support module purchase scope in paymongo payment creation"`

## Task 5: Complete Webhook and Fallback Finalization for Module Purchases

**Files:**
- Modify: `app/Http/Controllers/Api/WebhookController.php`
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `app/Services/ModulePurchaseService.php`
- Test: `tests/Feature/Learner/LearnerModulePaymentWebhookTest.php`

**Step 1: Write the failing test**

Add tests for:
1. Webhook success marks module purchase completed.
2. Enrollment is created according to enrollment mode.
3. Duplicate webhook events do not duplicate records.
4. Fallback callback/polling can finalize when webhook is delayed.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerModulePaymentWebhookTest`
Expected: FAIL because webhook finalization currently subscription-centric.

**Step 3: Write minimal implementation**

1. Detect module purchase metadata in webhook handler.
2. Delegate completion to `ModulePurchaseService` with idempotency guard.
3. Keep subscription webhook flow intact.
4. Update pending-status fallback endpoints to resolve module completion states.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerModulePaymentWebhookTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Api/WebhookController.php app/Http/Controllers/PaymentController.php app/Services/ModulePurchaseService.php tests/Feature/Learner/LearnerModulePaymentWebhookTest.php`
`git commit -m "feat: finalize module purchases via webhook and fallback checks"`

## Task 6: Upgrade Learner Module Catalog Card Metadata

**Files:**
- Modify: `resources/views/components/learner/module-card-recommended.blade.php`
- Modify: `resources/views/learner/modules/index.blade.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Test: `tests/Feature/Learner/LearnerModuleCatalogCardMetadataTest.php`

**Step 1: Write the failing test**

Add UI assertions for catalog cards:
1. Instructor avatar and name are displayed.
2. Price displays Free or P amount.
3. Occupancy displays current/limit when limited.
4. Full badge appears at capacity and actions are disabled accordingly.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerModuleCatalogCardMetadataTest`
Expected: FAIL because card metadata is incomplete today.

**Step 3: Write minimal implementation**

1. Load required relations/counts in index query.
2. Render instructor and pricing metadata in card component.
3. Render occupancy and full state labels.
4. Keep existing visual language and spacing conventions.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerModuleCatalogCardMetadataTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add resources/views/components/learner/module-card-recommended.blade.php resources/views/learner/modules/index.blade.php app/Http/Controllers/Learner/ModuleController.php tests/Feature/Learner/LearnerModuleCatalogCardMetadataTest.php`
`git commit -m "feat: enrich learner module cards with instructor price and capacity metadata"`

## Task 7: Add Instructor Information Card and Paid CTA States on Module Overview

**Files:**
- Modify: `resources/views/learner/modules/show.blade.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Learner/LearnerModuleOverviewPaidStateTest.php`

**Step 1: Write the failing test**

Add tests asserting module overview shows:
1. Instructor Information Card (photo, name, summary).
2. View Full Background action.
3. Free modules show Enroll Now.
4. Paid unpaid modules show Price button instead of Enroll Now.
5. Full modules show Enrollment Closed and block purchase action.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerModuleOverviewPaidStateTest`
Expected: FAIL due to missing overview states.

**Step 3: Write minimal implementation**

1. Add instructor card block in overview sidebar/info area.
2. Add paid-state CTA branching and lock conditions.
3. Link View Full Background to instructor profile detail route.
4. Keep curriculum visible while locking progression for unpaid paid modules.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerModuleOverviewPaidStateTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add resources/views/learner/modules/show.blade.php app/Http/Controllers/Learner/ModuleController.php routes/web.php tests/Feature/Learner/LearnerModuleOverviewPaidStateTest.php`
`git commit -m "feat: add instructor info card and paid module overview action states"`

## Task 8: Add Learner-Facing Instructor Background View

**Files:**
- Create: `app/Http/Controllers/Learner/InstructorProfileController.php`
- Create: `resources/views/learner/instructors/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Learner/LearnerInstructorBackgroundViewTest.php`

**Step 1: Write the failing test**

Add tests verifying:
1. Learner can open instructor background page from module overview route.
2. Page shows instructor profile details and photo.
3. Non-learner unauthorized access behavior follows existing middleware policy.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerInstructorBackgroundViewTest`
Expected: FAIL due to missing route/controller/view.

**Step 3: Write minimal implementation**

1. Add learner route for instructor profile background view.
2. Build thin controller action with safe read-only profile data.
3. Add Blade page aligned with learner-side design system.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerInstructorBackgroundViewTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Learner/InstructorProfileController.php resources/views/learner/instructors/show.blade.php routes/web.php tests/Feature/Learner/LearnerInstructorBackgroundViewTest.php`
`git commit -m "feat: add learner instructor background profile page"`

## Task 9: Extend Payment History for Module Purchases

**Files:**
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `resources/views/payments/history.blade.php`
- Test: `tests/Feature/Learner/LearnerPaymentHistoryModuleTransactionsTest.php`

**Step 1: Write the failing test**

Add tests asserting:
1. Payment history includes subscription and module payment rows.
2. Module rows display module title and status.
3. Filters/grouping by transaction type works.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerPaymentHistoryModuleTransactionsTest`
Expected: FAIL because history is currently subscription-centric.

**Step 3: Write minimal implementation**

1. Extend payment history query to load module purchase context.
2. Add type indicator in view.
3. Add optional filter parameters for all/subscription/module.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerPaymentHistoryModuleTransactionsTest`
Expected: PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/PaymentController.php resources/views/payments/history.blade.php tests/Feature/Learner/LearnerPaymentHistoryModuleTransactionsTest.php`
`git commit -m "feat: extend learner payment history with module transactions"`

## Task 10: Add Regression and Flow Integrity Tests

**Files:**
- Create: `tests/Feature/Learner/LearnerModuleMonetizationRegressionTest.php`
- Modify: related factories/seed helpers as needed

**Step 1: Write the failing test**

Add regression tests covering combined scenarios:
1. Free module complete path remains stable.
2. Paid module with parent approval requirement.
3. Paid module full-capacity block.
4. Paid manual-mode enrollment after successful payment.
5. Duplicate purchase requests are idempotent.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerModuleMonetizationRegressionTest`
Expected: FAIL until integrated behavior is complete.

**Step 3: Write minimal implementation adjustments**

Patch any edge-case orchestration gaps discovered by regression tests.

**Step 4: Run focused and broad verification**

Run:
1. `php artisan test --filter=LearnerModuleMonetizationRegressionTest`
2. `php artisan test --filter=LearnerPaidModuleFlowTest`
3. `php artisan test --filter=LearnerModulePaymentWebhookTest`
4. `php artisan test --filter=PaymentController`

Expected: PASS for updated scope.

**Step 5: Commit**

Run:
`git add tests/Feature/Learner/LearnerModuleMonetizationRegressionTest.php`
`git commit -m "test: add learner module monetization regression coverage"`

---

## Verification Checklist

1. Free modules still support direct enrollment and progression.
2. Paid modules require purchase, with overview-first UX.
3. Parent approval gate occurs before checkout.
4. Capacity checks prevent over-selling.
5. Completed purchases are durable ownership records.
6. Manual enrollment mode after payment remains pending until instructor action.
7. Payment history displays both subscription and module transactions.

## Rollout Notes

1. Deploy migration before enabling paid module purchase routes.
2. Verify PayMongo webhook signature and endpoint availability in environment.
3. Monitor logs for module-scope payment metadata during first rollout window.
4. Add support playbook for manual refund handling in admin operations.

---

Plan complete and saved to `docs/plans/2026-03-31-learner-module-monetization-implementation-plan.md`. Two execution options:

1. Subagent-Driven (this session) - dispatch fresh subagent per task, review between tasks, fast iteration.
2. Parallel Session (separate) - open new session with executing-plans, batch execution with checkpoints.

Which approach?