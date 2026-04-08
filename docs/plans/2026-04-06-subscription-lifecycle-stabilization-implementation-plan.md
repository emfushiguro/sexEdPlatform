# Subscription Lifecycle Stabilization and Renewal UX Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Stabilize subscription expiry, renewal, entitlement fallback, payment receipt visibility, and admin timestamp accuracy while preserving the current Laravel service-layer architecture.

**Architecture:** Keep all lifecycle behavior centralized in subscription services and keep controllers thin. Use normalized subscriber datetime columns as canonical with legacy fallbacks for backward compatibility. Implement runtime and scheduled expiry enforcement together to prevent stale premium access windows.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS, PHPUnit.

---

### Task 1: Lock Lifecycle Regression Coverage First

**Files:**
- Modify: `tests/Unit/Services/SubscriptionLifecycleStateTest.php`
- Modify: `tests/Feature/Learner/LearnerSubscriptionEntitlementsActivationTest.php`
- Create: `tests/Feature/Learner/LearnerSubscriptionExpiryEntitlementFallbackTest.php`

**Step 1: Write failing tests for expiry fallback behavior**
Add tests that prove:
- active-but-time-expired subscriptions are treated as non-premium
- entitlement checks return free baseline behavior once expired
- status reconciliation happens before learner subscription rendering where needed

**Step 2: Run focused tests to verify failures**
Run:
`php artisan test --filter=SubscriptionLifecycleStateTest`
`php artisan test --filter=LearnerSubscriptionExpiryEntitlementFallbackTest`
Expected: FAIL on new assertions before implementation.

**Step 3: Commit test-only baseline**
Run:
`git add tests/Unit/Services/SubscriptionLifecycleStateTest.php tests/Feature/Learner/LearnerSubscriptionEntitlementsActivationTest.php tests/Feature/Learner/LearnerSubscriptionExpiryEntitlementFallbackTest.php`
`git commit -m "test: add failing expiry entitlement fallback coverage"`

---

### Task 2: Implement Effective Lifecycle Resolution in Service Layer

**Files:**
- Modify: `app/Services/SubscriptionService.php`
- Modify: `app/Services/EntitlementService.php`
- Modify: `app/Models/User.php`
- Test: `tests/Unit/Services/SubscriptionLifecycleStateTest.php`

**Step 1: Add effective end timestamp resolver in service**
Implement private helper(s) that consistently resolve effective end from `ends_at` then `end_date`.

**Step 2: Add lifecycle reconciliation method**
Implement method to reconcile stale active-but-expired subscriptions at runtime and transition to `expired` safely.

**Step 3: Ensure premium checks use effective lifecycle state**
Update premium and entitlement query logic to require effective active state, not status-only checks.

**Step 4: Keep User premium helper aligned**
Ensure `User::isPremium()` stays consistent with service behavior and normalized-first timestamp logic.

**Step 5: Run lifecycle and entitlement tests**
Run:
`php artisan test --filter=SubscriptionLifecycleStateTest`
`php artisan test --filter=LearnerSubscriptionExpiryEntitlementFallbackTest`
Expected: PASS.

**Step 6: Commit service-layer lifecycle changes**
Run:
`git add app/Services/SubscriptionService.php app/Services/EntitlementService.php app/Models/User.php tests/Unit/Services/SubscriptionLifecycleStateTest.php tests/Feature/Learner/LearnerSubscriptionExpiryEntitlementFallbackTest.php`
`git commit -m "fix: enforce effective lifecycle state for premium and entitlements"`

---

### Task 3: Normalize Scheduled Expiry Command Behavior

**Files:**
- Modify: `app/Console/Commands/ExpireSubscriptions.php`
- Modify: `app/Services/SubscriptionService.php`
- Create: `tests/Feature/Console/ExpireSubscriptionsNormalizedDatesTest.php`

**Step 1: Add failing command test for normalized timestamp expiry**
Cover expiry for records with `ends_at` populated even when `end_date` is null or stale.

**Step 2: Refactor expiry command to normalized-first logic**
Use service-layer expiration logic and normalized-first date checks; keep events and notifications intact.

**Step 3: Run command-related tests**
Run:
`php artisan test --filter=ExpireSubscriptionsNormalizedDatesTest`
Expected: PASS.

**Step 4: Commit command normalization changes**
Run:
`git add app/Console/Commands/ExpireSubscriptions.php app/Services/SubscriptionService.php tests/Feature/Console/ExpireSubscriptionsNormalizedDatesTest.php`
`git commit -m "fix: normalize scheduled expiry checks to ends_at first"`

---

### Task 4: Add Plan-Level Renewal Warning Threshold and Safe Backfill

**Files:**
- Create: `database/migrations/2026_04_06_000001_add_renewal_warning_days_to_subscription_plans.php`
- Create: `database/migrations/2026_04_06_000002_backfill_subscriber_normalized_timestamps.php`
- Modify: `app/Models/SubscriptionPlan.php`
- Create: `tests/Feature/Admin/SubscriptionRenewalWarningConfigTest.php`

**Step 1: Add failing test for plan-level threshold persistence/default behavior**
Verify plan warning threshold can be read with fallback when null.

**Step 2: Add additive migration for per-plan renewal warning days**
Create nullable integer column with conservative defaults handled in code.

**Step 3: Add additive migration for normalized timestamp backfill**
Backfill `starts_at` and `ends_at` using combined heuristic strategy (payment-first, legacy fallback, created_at fallback).

**Step 4: Update model cast/fillable accessors as needed**
Ensure renewal warning days are accessible from plan model.

**Step 5: Run migration and threshold tests**
Run:
`php artisan test --filter=SubscriptionRenewalWarningConfigTest`
Expected: PASS.

**Step 6: Commit schema updates**
Run:
`git add database/migrations/2026_04_06_000001_add_renewal_warning_days_to_subscription_plans.php database/migrations/2026_04_06_000002_backfill_subscriber_normalized_timestamps.php app/Models/SubscriptionPlan.php tests/Feature/Admin/SubscriptionRenewalWarningConfigTest.php`
`git commit -m "feat: add plan-level renewal warning config and safe timestamp backfill"`

---

### Task 5: Implement Renewal Eligibility and Extension Anchor Rules

**Files:**
- Modify: `app/Services/SubscriptionService.php`
- Modify: `app/Http/Controllers/Learner/SubscriptionController.php`
- Create: `tests/Feature/Learner/LearnerSubscriptionRenewalFlowTest.php`

**Step 1: Add failing renewal behavior tests**
Cover:
- expired subscription renewal eligibility
- expiring-soon eligibility by plan threshold
- extension anchor using later of now or current expiry

**Step 2: Implement service-level renewal eligibility helpers**
Add helpers for `isRenewableNow` and warning window checks with plan-level configuration.

**Step 3: Update renewal mutation to preserve remaining paid time**
Ensure renewal calculations use extension anchor rule and sync both normalized and legacy fields.

**Step 4: Keep controller thin**
Controller should delegate logic to service and only manage redirects/flash messages.

**Step 5: Run renewal tests**
Run:
`php artisan test --filter=LearnerSubscriptionRenewalFlowTest`
Expected: PASS.

**Step 6: Commit renewal logic changes**
Run:
`git add app/Services/SubscriptionService.php app/Http/Controllers/Learner/SubscriptionController.php tests/Feature/Learner/LearnerSubscriptionRenewalFlowTest.php`
`git commit -m "feat: add configurable renewal eligibility and extension anchor logic"`

---

### Task 6: Update Learner Subscription Page UX and Renewal Notices

**Files:**
- Modify: `resources/views/subscriptions/index.blade.php`
- Modify: `app/Http/Controllers/Learner/SubscriptionController.php`
- Create: `tests/Feature/Learner/LearnerSubscriptionPageUiParityTest.php`

**Step 1: Add failing UI assertions**
Verify:
- entitlement snapshot block is absent
- free plan explicit feature lines are present
- renewal notice/CTA appears for expired and expiring states

**Step 2: Remove entitlement snapshot section**
Delete current entitlement snapshot panel rendering.

**Step 3: Align Free Plan card with paid plan structure**
Keep same card hierarchy and spacing while retaining clear free/baseline labeling.

**Step 4: Replace free feature copy with explicit descriptions**
Use the approved explicit bullet lines.

**Step 5: Expose renewal notice state from controller payload**
Pass computed renewal notice metadata to blade view in a thin-controller way.

**Step 6: Run subscription page tests**
Run:
`php artisan test --filter=LearnerSubscriptionPageUiParityTest`
Expected: PASS.

**Step 7: Commit learner UX changes**
Run:
`git add resources/views/subscriptions/index.blade.php app/Http/Controllers/Learner/SubscriptionController.php tests/Feature/Learner/LearnerSubscriptionPageUiParityTest.php`
`git commit -m "feat: refine subscription page free plan parity and renewal notice UX"`

---

### Task 7: Add Payment Success Receipt Visibility

**Files:**
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `resources/views/payments/success.blade.php`
- Create: `tests/Feature/Learner/LearnerPaymentSuccessReceiptCtaTest.php`

**Step 1: Add failing receipt CTA tests**
Cover:
- receipt button shown on success page
- route points to specific receipt when payment id exists
- fallback to payment history when specific payment cannot be resolved

**Step 2: Resolve receipt context in controller success method**
Add lightweight context derivation for subscription and module scope success pages.

**Step 3: Render View Receipt action in success blade**
Insert CTA in action row with fallback-safe URL.

**Step 4: Run payment success tests**
Run:
`php artisan test --filter=LearnerPaymentSuccessReceiptCtaTest`
Expected: PASS.

**Step 5: Commit payment success improvements**
Run:
`git add app/Http/Controllers/PaymentController.php resources/views/payments/success.blade.php tests/Feature/Learner/LearnerPaymentSuccessReceiptCtaTest.php`
`git commit -m "feat: add payment success receipt CTA with safe fallback"`

---

### Task 8: Fix Admin Subscriber Date Rendering Precedence

**Files:**
- Modify: `app/Http/Controllers/Admin/SubscriberAdminController.php`
- Modify: `resources/views/admin/subscriber/index.blade.php`
- Modify: `resources/views/admin/subscriber/show.blade.php`
- Create: `tests/Feature/Admin/AdminSubscriberDateAccuracyTest.php`

**Step 1: Add failing admin date tests**
Verify normalized datetime fields are displayed before legacy date fields in index and show pages.

**Step 2: Update admin data shaping in controller**
Compute date presentation using normalized-first fallback strategy.

**Step 3: Update blade display bindings**
Swap start/end source fields to normalized-first output and keep time-inclusive formatting.

**Step 4: Run admin subscriber tests**
Run:
`php artisan test --filter=AdminSubscriberDateAccuracyTest`
Expected: PASS.

**Step 5: Commit admin date fixes**
Run:
`git add app/Http/Controllers/Admin/SubscriberAdminController.php resources/views/admin/subscriber/index.blade.php resources/views/admin/subscriber/show.blade.php tests/Feature/Admin/AdminSubscriberDateAccuracyTest.php`
`git commit -m "fix: normalize admin subscriber start and expiry timestamp display"`

---

### Task 9: Add Scheduler Wiring for Lifecycle Reliability

**Files:**
- Modify: `routes/console.php`
- Create: `tests/Feature/Console/SubscriptionSchedulerRegistrationTest.php`

**Step 1: Add failing scheduler registration test**
Assert expiry and renewal processing commands are scheduled.

**Step 2: Register lifecycle commands in scheduler**
Schedule commands for regular reconciliation windows suitable for billing reliability.

**Step 3: Run scheduler test**
Run:
`php artisan test --filter=SubscriptionSchedulerRegistrationTest`
Expected: PASS.

**Step 4: Commit scheduler wiring**
Run:
`git add routes/console.php tests/Feature/Console/SubscriptionSchedulerRegistrationTest.php`
`git commit -m "chore: schedule subscription lifecycle reconciliation commands"`

---

### Task 10: Run Targeted Verification Suite and Publish Test Report

**Files:**
- Modify/Create: `docs/plans/2026-04-06-subscription-lifecycle-stabilization-test-report.md`

**Step 1: Run targeted test matrix**
Run:
`php artisan test --filter=SubscriptionLifecycleStateTest`
`php artisan test --filter=LearnerSubscriptionExpiryEntitlementFallbackTest`
`php artisan test --filter=LearnerSubscriptionRenewalFlowTest`
`php artisan test --filter=LearnerSubscriptionPageUiParityTest`
`php artisan test --filter=LearnerPaymentSuccessReceiptCtaTest`
`php artisan test --filter=AdminSubscriberDateAccuracyTest`
`php artisan test --filter=ExpireSubscriptionsNormalizedDatesTest`

Expected: PASS for all targeted cases.

**Step 2: Record actual output and residual risks**
Write concise test report including command list, pass/fail outcomes, and any unresolved edge cases.

**Step 3: Commit verification artifacts**
Run:
`git add docs/plans/2026-04-06-subscription-lifecycle-stabilization-test-report.md`
`git commit -m "test: capture subscription lifecycle stabilization verification results"`

---

## Execution Notes
- Keep each task strictly scoped and independent.
- Do not move business rules into controllers.
- Prefer normalized timestamps in logic and UI, but preserve legacy fallbacks.
- Preserve existing route ownership and role constraints.
- Avoid unrelated refactors while executing this plan.

## Completion Criteria
- Expired subscriptions immediately lose premium entitlements.
- Renewal works for expired and expiring subscriptions and preserves remaining paid time.
- Subscription page reflects new UX requirements (no entitlement snapshot, explicit Free Plan details).
- Payment success page provides receipt visibility.
- Admin subscriber dates render accurate timestamp values.
- Targeted tests pass and are documented.
