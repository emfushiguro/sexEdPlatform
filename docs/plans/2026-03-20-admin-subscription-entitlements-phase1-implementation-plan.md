# Admin Subscription Entitlements Phase 1 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver a reliable end-to-end admin subscription flow where fullscreen plan creation, payment success processing, and learner entitlement enforcement work for unlimited shields and certificate PDF download access.

**Architecture:** Keep existing subscription/payment domain structure and apply a thin vertical slice across admin UI, plan persistence, payment-success grant orchestration, and learner-side enforcement. Use idempotent post-payment grant handling with explicit grant status surfaced in admin payments. Preserve current free-tier defaults when entitlement flags are not enabled.

**Tech Stack:** Laravel, Blade, Eloquent, Form Requests, Events/Listeners, Queued Jobs, PHPUnit feature/unit tests.

---

## Task 1: Baseline Entitlement Keys and Runtime Contract

**Files:**
- Modify: `app/Support/SubscriptionFeatureKeys.php`
- Modify: `tests/Unit/Services/EntitlementServiceTest.php`

**Step 1: Write the failing test**
- Add a test in `tests/Unit/Services/EntitlementServiceTest.php` for a learner who has:
  - `unlimited_shields=true`
  - `certificate_pdf_download_access=true`
- Assert both keys are recognized and readable from plan entitlements.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=EntitlementServiceTest`
- Expected: FAIL due to missing key constant and/or missing resolution path.

**Step 3: Write minimal implementation**
- Add `CERTIFICATE_PDF_DOWNLOAD_ACCESS` constant to `app/Support/SubscriptionFeatureKeys.php`.
- Ensure entitlement resolution reads the new key the same way as unlimited shields.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=EntitlementServiceTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add app/Support/SubscriptionFeatureKeys.php tests/Unit/Services/EntitlementServiceTest.php
git commit -m "feat(entitlements): add certificate pdf access key support"
```

## Task 2: Fullscreen Admin Create Modal with Sidebar Lock

**Files:**
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Modify: `resources/js/app.js`
- Modify: `resources/css/app.css`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**
- Add assertions in `tests/Feature/Admin/PlanManagementFlowTest.php` that index page contains:
  - fullscreen modal container marker
  - create-plan trigger marker
  - sidebar lock marker/class toggle hook

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: FAIL because markers/hooks do not exist yet.

**Step 3: Write minimal implementation**
- Refactor create flow in `resources/views/admin/subscription-plans/index.blade.php` into fullscreen overlay modal.
- Add modal open/close state hooks that auto-close and lock sidebar during open session.
- Keep existing submit endpoint unchanged.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/admin/subscription-plans/index.blade.php resources/js/app.js resources/css/app.css tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(admin): use fullscreen plan modal and sidebar lock"
```

## Task 3: Persist Boolean Entitlements in Admin Plan Save

**Files:**
- Modify: `app/Http/Controllers/Admin/SubscriptionPlanAdminController.php`
- Create: `app/Http/Requests/Admin/StoreSubscriptionPlanRequest.php`
- Create: `app/Http/Requests/Admin/UpdateSubscriptionPlanRequest.php`
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**
- Add test that POSTs create-plan payload with boolean entitlements:
  - `unlimited_shields`
  - `certificate_pdf_download_access`
- Assert stored plan has both entitlement flags persisted in feature payload.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: FAIL due to missing request normalization/validation.

**Step 3: Write minimal implementation**
- Add Form Requests for create/update plan validation.
- Normalize booleans from modal payload.
- Persist entitlement flags in `features` consistently.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=PlanManagementFlowTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Admin/SubscriptionPlanAdminController.php app/Http/Requests/Admin/StoreSubscriptionPlanRequest.php app/Http/Requests/Admin/UpdateSubscriptionPlanRequest.php resources/views/admin/subscription-plans/index.blade.php tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(admin): validate and persist phase1 entitlement booleans"
```

## Task 4: Payment-Success Entitlement Grant with Idempotency and Status

**Files:**
- Create: `app/Services/EntitlementGrantService.php`
- Modify: `app/Observers/PaymentObserver.php`
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `app/Models/Payment.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_entitlement_grant_columns_to_payments_table.php`
- Test: `tests/Feature/Admin/AdminActivityLogTest.php`
- Test: `tests/Feature/Learner/LearnerSubscriptionParityTest.php`

**Step 1: Write the failing test**
- Add integration test for successful payment callback that asserts:
  - payment marked successful
  - entitlement grant runs once (idempotent)
  - grant status transitions to granted

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=LearnerSubscriptionParityTest`
- Expected: FAIL due to missing grant-status/idempotency handling.

**Step 3: Write minimal implementation**
- Add payment columns for grant tracking (e.g. `entitlement_grant_status`, `entitlement_grant_attempts`, `entitlement_granted_at`, `entitlement_grant_error`).
- Implement `EntitlementGrantService` to apply plan entitlements to learner effective state.
- Trigger service from payment success handling paths with idempotency guard keyed by successful payment transaction.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=LearnerSubscriptionParityTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add app/Services/EntitlementGrantService.php app/Observers/PaymentObserver.php app/Http/Controllers/PaymentController.php app/Models/Payment.php database/migrations/*.php tests/Feature/Learner/LearnerSubscriptionParityTest.php tests/Feature/Admin/AdminActivityLogTest.php
git commit -m "feat(payments): grant entitlements on payment success with idempotent status tracking"
```

## Task 5: Learner Enforcement for Shields and Certificate Download/Print

**Files:**
- Modify: `app/Http/Controllers/Learner/QuizController.php`
- Modify: `app/Http/Controllers/Learner/CertificateController.php`
- Modify: `resources/views/learner/certificates/show.blade.php`
- Test: `tests/Feature/Learner/LearnerSubscriptionParityTest.php`

**Step 1: Write the failing test**
- Add learner tests asserting:
  - with unlimited_shields=true: quiz flow bypasses shield gate.
  - with certificate_pdf_download_access=false: certificate download/print denied.
  - with certificate_pdf_download_access=true: certificate download allowed.

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=LearnerSubscriptionParityTest`
- Expected: FAIL before enforcement update.

**Step 3: Write minimal implementation**
- Gate shield consumption/check paths in `QuizController` by entitlement flag.
- Gate certificate download/print endpoint behavior in `CertificateController` by entitlement flag.
- Update learner certificate UI to reflect disabled/enabled state.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=LearnerSubscriptionParityTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/QuizController.php app/Http/Controllers/Learner/CertificateController.php resources/views/learner/certificates/show.blade.php tests/Feature/Learner/LearnerSubscriptionParityTest.php
git commit -m "feat(learner): enforce phase1 shield and certificate entitlements"
```

## Task 6: Admin Payments Visibility and Failure Recovery Signals

**Files:**
- Modify: `app/Http/Controllers/Admin/PaymentAdminController.php`
- Modify: `resources/views/admin/payments/subscription-details.blade.php`
- Test: `tests/Feature/Admin/AdminTableUxTest.php`

**Step 1: Write the failing test**
- Add assertions that admin payment view includes:
  - learner
  - plan
  - payment status
  - entitlement grant status

**Step 2: Run test to verify it fails**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: FAIL due to missing grant-state rendering.

**Step 3: Write minimal implementation**
- Load and render entitlement grant status metadata in admin payment detail/listing context.
- Show explicit failed/retrying states for support visibility.

**Step 4: Run test to verify it passes**
- Run: `php artisan test --filter=AdminTableUxTest`
- Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Admin/PaymentAdminController.php resources/views/admin/payments/subscription-details.blade.php tests/Feature/Admin/AdminTableUxTest.php
git commit -m "feat(admin-payments): expose entitlement grant lifecycle state"
```

## Task 7: End-to-End Verification (Local then Staging)

**Files:**
- Modify: `docs/QUICK_TESTING_GUIDE.md`
- Create: `docs/plans/2026-03-20-admin-subscription-entitlements-phase1-test-report.md`

**Step 1: Write the failing verification checklist**
- Add test report template with all acceptance checks unchecked.

**Step 2: Run test suites and manual flow verification**
- Run:
  - `php artisan test --filter=PlanManagementFlowTest`
  - `php artisan test --filter=LearnerSubscriptionParityTest`
  - `php artisan test --filter=EntitlementServiceTest`
  - `php artisan test --filter=AdminTableUxTest`
- Manually verify full flow in local and then staging.

**Step 3: Record outcomes**
- Mark each checklist step pass/fail in report doc with evidence notes.

**Step 4: Commit**
```bash
git add docs/QUICK_TESTING_GUIDE.md docs/plans/2026-03-20-admin-subscription-entitlements-phase1-test-report.md
git commit -m "test: document phase1 entitlement flow verification"
```

## Definition of Done

- Fullscreen admin create modal replaces overlap behavior and controls sidebar state correctly.
- Admin can configure and persist learner entitlement booleans for phase 1.
- Learner subscription payment success immediately grants entitlements with idempotent safety.
- Admin payments page visibly tracks entitlement grant lifecycle.
- Unlimited shields and certificate PDF download/print entitlement behavior is enforced server-side.
- Local and staging verification passed and documented.
