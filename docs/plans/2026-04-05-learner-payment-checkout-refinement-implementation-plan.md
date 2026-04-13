# Learner Payment Checkout Refinement Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver a summary-first learner checkout flow for both module and subscription purchases, with clear payment transparency, robust PayMongo sandbox/live handling, and reliable post-payment completion behavior.

**Architecture:** Implement a shared learner checkout orchestration layer that powers both module and subscription summary pages while preserving existing domain services for entitlement side effects. Keep controllers thin, standardize metadata/idempotency behavior, and route all payment link creation through the PayMongo service with full payment method support.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, PayMongo Links API, PHPUnit Feature and Unit tests.

---

I'm using the writing-plans skill to create the implementation plan.

## Task 1: Add Checkout Feature Flag and Environment Guardrails

**Files:**
- Modify: `config/billing.php`
- Modify: `config/paymongo.php` (if needed for readable mode/channel messaging support only)
- Modify: `routes/web.php`
- Test: `tests/Feature/Learner/LearnerCheckoutFeatureFlagTest.php`

**Step 1: Write the failing test**

Create `LearnerCheckoutFeatureFlagTest` assertions:
1. New summary-first routes are enabled when checkout refinement flag is true.
2. Legacy direct-entry routes are not used by learner entry points when flag is true.
3. Route-level simulation success is blocked in production-like environment and allowed in local/testing/staging.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerCheckoutFeatureFlagTest`
Expected: FAIL because feature flag wiring and environment gating are incomplete.

**Step 3: Write minimal implementation**

1. Add config flag in `billing.php` for learner checkout refinement rollout.
2. Wire learner route branching in `routes/web.php` to full replacement routes.
3. Adjust simulation route environment policy to local/testing/staging only.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerCheckoutFeatureFlagTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add config/billing.php config/paymongo.php routes/web.php tests/Feature/Learner/LearnerCheckoutFeatureFlagTest.php
git commit -m "feat: add learner checkout rollout flag and simulation env guards"
```

## Task 2: Build Shared Learner Checkout Orchestration Service

**Files:**
- Create: `app/Services/Checkout/LearnerCheckoutService.php`
- Modify: `app/Services/PayMongoPaymentLinkService.php`
- Modify: `app/Services/ModulePurchaseService.php`
- Modify: `app/Services/SubscriptionService.php`
- Test: `tests/Unit/Services/Checkout/LearnerCheckoutServiceTest.php`

**Step 1: Write the failing test**

Create unit tests for:
1. Building module checkout context payload.
2. Building subscription checkout context payload.
3. Creating checkout link with full `payment_method_types` and preferred ordering.
4. Returning deterministic payload (`status`, `checkout_url`, `payment_id`, `scope`).

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerCheckoutServiceTest`
Expected: FAIL because service does not yet exist.

**Step 3: Write minimal implementation**

1. Create `LearnerCheckoutService` that accepts scope (`module_purchase`, `subscription`) and delegates domain-specific creation to existing services.
2. Standardize metadata and billing snapshot fields across both scopes.
3. Keep PayMongo link creation centralized and method-list complete.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerCheckoutServiceTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/Checkout/LearnerCheckoutService.php app/Services/PayMongoPaymentLinkService.php app/Services/ModulePurchaseService.php app/Services/SubscriptionService.php tests/Unit/Services/Checkout/LearnerCheckoutServiceTest.php
git commit -m "feat: add shared learner checkout orchestration service"
```

## Task 3: Create Unified Form Request Contracts for Summary Checkout Submit

**Files:**
- Create: `app/Http/Requests/Checkout/ProcessLearnerCheckoutRequest.php`
- Modify: `app/Http/Requests/ProcessPaymentRequest.php`
- Modify: `app/Http/Requests/ProcessModulePaymentRequest.php`
- Test: `tests/Feature/Learner/LearnerCheckoutValidationTest.php`

**Step 1: Write the failing test**

Test validation rules:
1. Requires terms acceptance.
2. Requires billing name/email/phone for both scopes per approved decision.
3. Accepts only supported preferred payment methods.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerCheckoutValidationTest`
Expected: FAIL due to missing shared request and mismatched rules.

**Step 3: Write minimal implementation**

1. Add shared request contract for checkout submission.
2. Harmonize module/subscription request behavior with shared billing requirements.
3. Keep user-facing validation messages learner-friendly.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerCheckoutValidationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Requests/Checkout/ProcessLearnerCheckoutRequest.php app/Http/Requests/ProcessPaymentRequest.php app/Http/Requests/ProcessModulePaymentRequest.php tests/Feature/Learner/LearnerCheckoutValidationTest.php
git commit -m "feat: unify learner checkout validation contracts"
```

## Task 4: Add Summary-First Routes and Thin Controllers for Module and Subscription

**Files:**
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/Learner/SubscriptionController.php`
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Learner/LearnerCheckoutRoutingFlowTest.php`

**Step 1: Write the failing test**

Test route behavior:
1. Module payment entry redirects to module summary page.
2. Subscription payment entry redirects to subscription summary page.
3. Summary form submit creates pending checkout and redirects to pending/paymongo path.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerCheckoutRoutingFlowTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Keep controller actions orchestration-only.
2. Route legacy direct paths into summary-first flow.
3. Preserve existing access control, parent approval, age filtering, and module capacity checks.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerCheckoutRoutingFlowTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Learner/ModuleController.php app/Http/Controllers/Learner/SubscriptionController.php app/Http/Controllers/PaymentController.php routes/web.php tests/Feature/Learner/LearnerCheckoutRoutingFlowTest.php
git commit -m "feat: refactor learner routes to summary-first checkout flow"
```

## Task 5: Build Shared Checkout Summary Views with Scope-Specific Partials

**Files:**
- Create: `resources/views/payments/checkout-summary.blade.php`
- Create: `resources/views/payments/partials/checkout-item-module.blade.php`
- Create: `resources/views/payments/partials/checkout-item-subscription.blade.php`
- Create: `resources/views/payments/partials/checkout-billing-form.blade.php`
- Create: `resources/views/payments/partials/checkout-sandbox-notice.blade.php`
- Modify: `resources/views/payments/module-create.blade.php` (legacy fallback/redirect view strategy)
- Modify: `resources/views/payments/create.blade.php` (legacy fallback/redirect view strategy)
- Test: `tests/Feature/Learner/LearnerCheckoutSummaryViewTest.php`

**Step 1: Write the failing test**

Test UI contract:
1. Summary page displays purchase type, item details, and total amount.
2. Module summary includes module + instructor details.
3. Subscription summary includes plan + duration details.
4. Sandbox notice appears in sandbox mode.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerCheckoutSummaryViewTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Implement shared summary shell and scope partials.
2. Keep visual style consistent with learner design system.
3. Add clear copy that method availability may vary in PayMongo sandbox hosted checkout.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerCheckoutSummaryViewTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/payments/checkout-summary.blade.php resources/views/payments/partials/checkout-item-module.blade.php resources/views/payments/partials/checkout-item-subscription.blade.php resources/views/payments/partials/checkout-billing-form.blade.php resources/views/payments/partials/checkout-sandbox-notice.blade.php resources/views/payments/module-create.blade.php resources/views/payments/create.blade.php tests/Feature/Learner/LearnerCheckoutSummaryViewTest.php
git commit -m "feat: add shared learner checkout summary views"
```

## Task 6: Refine PayMongo Payload and Hosted Checkout Expectations

**Files:**
- Modify: `app/Services/PayMongoPaymentLinkService.php`
- Modify: `config/paymongo.php`
- Test: `tests/Unit/Services/PayMongoPaymentLinkServiceTest.php`
- Test: `tests/Feature/Learner/LearnerCheckoutPayloadContractTest.php`

**Step 1: Write the failing test**

Add/extend tests to assert:
1. All configured methods are sent in `payment_method_types`.
2. Preferred method is first, but full list remains.
3. Payload remains stable for both module and subscription scopes.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PayMongoPaymentLinkServiceTest`
Run: `php artisan test --filter=LearnerCheckoutPayloadContractTest`
Expected: At least one FAIL for missing contract coverage.

**Step 3: Write minimal implementation**

1. Keep method resolution centralized and deterministic.
2. Avoid reducing method list to a single method.
3. Keep logs non-sensitive while recording mode and method-set decisions.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PayMongoPaymentLinkServiceTest`
Run: `php artisan test --filter=LearnerCheckoutPayloadContractTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/PayMongoPaymentLinkService.php config/paymongo.php tests/Unit/Services/PayMongoPaymentLinkServiceTest.php tests/Feature/Learner/LearnerCheckoutPayloadContractTest.php
git commit -m "fix: enforce full paymongo payment method payload with preferred ordering"
```

## Task 7: Implement Cancel and Failure Return-to-Summary UX

**Files:**
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `resources/views/payments/checkout-summary.blade.php`
- Test: `tests/Feature/Learner/LearnerCheckoutCancelFailureFlowTest.php`

**Step 1: Write the failing test**

Test assertions:
1. Module payment failure returns to module summary route with retry message.
2. Subscription payment failure returns to subscription summary route with retry message.
3. Learner can retry without losing critical checkout context.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerCheckoutCancelFailureFlowTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Redirect fail/cancel callbacks to summary pages (not dead-end pages).
2. Add actionable retry messaging.
3. Preserve UX consistency between module and subscription scopes.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerCheckoutCancelFailureFlowTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Learner/ModuleController.php app/Http/Controllers/PaymentController.php resources/views/payments/checkout-summary.blade.php tests/Feature/Learner/LearnerCheckoutCancelFailureFlowTest.php
git commit -m "feat: return cancelled and failed payments to checkout summary with retry"
```

## Task 8: Harden Success Completion Idempotency and Queue Receipt Consistently

**Files:**
- Modify: `app/Http/Controllers/Api/WebhookController.php`
- Modify: `app/Services/ModulePurchaseService.php`
- Modify: `app/Services/SubscriptionService.php`
- Modify: `app/Observers/PaymentObserver.php` (if required)
- Test: `tests/Feature/Learner/LearnerCheckoutCompletionIdempotencyTest.php`

**Step 1: Write the failing test**

Test scenarios:
1. Duplicate webhook events do not duplicate side effects.
2. Pending-page verification plus webhook does not double-complete.
3. Receipt generation is queued once.
4. Module enrollment/subscription activation occurs exactly once.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerCheckoutCompletionIdempotencyTest`
Expected: FAIL on duplicate/queue assertions.

**Step 3: Write minimal implementation**

1. Add strict completion guards before side effects.
2. Ensure receipt/invoice queue dispatch is idempotent.
3. Preserve existing observer/event architecture while preventing duplicate actions.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerCheckoutCompletionIdempotencyTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Api/WebhookController.php app/Services/ModulePurchaseService.php app/Services/SubscriptionService.php app/Observers/PaymentObserver.php tests/Feature/Learner/LearnerCheckoutCompletionIdempotencyTest.php
git commit -m "fix: enforce idempotent checkout completion and single receipt queueing"
```

## Task 9: Verify Payment History and Revenue Surface Integrity

**Files:**
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `app/Services/Monetization/ModuleSaleLedgerService.php` (only if required)
- Test: `tests/Feature/Learner/LearnerPaymentHistoryModuleTransactionsTest.php`
- Test: `tests/Feature/Learner/LearnerSubscriptionCheckoutHistoryTest.php`
- Test: `tests/Feature/Admin/AdminModuleRevenueDashboardTest.php` (targeted assertions)

**Step 1: Write/extend failing tests**

Add assertions that post-refactor:
1. Learner payment history still renders module and subscription entries correctly.
2. Revenue and instructor earnings tracking remains populated for completed module purchases.
3. No regressions in admin/payment visibility for completed transactions.

**Step 2: Run tests to verify failure (if regressions exist)**

Run: `php artisan test --filter=LearnerPaymentHistoryModuleTransactionsTest`
Run: `php artisan test --filter=LearnerSubscriptionCheckoutHistoryTest`
Run: `php artisan test --filter=AdminModuleRevenueDashboardTest`
Expected: Catch regressions before release.

**Step 3: Write minimal implementation**

1. Adjust data loading or metadata mapping only where needed.
2. Keep schema untouched unless absolutely required.

**Step 4: Run tests to verify pass**

Re-run above commands.
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/PaymentController.php app/Services/Monetization/ModuleSaleLedgerService.php tests/Feature/Learner/LearnerPaymentHistoryModuleTransactionsTest.php tests/Feature/Learner/LearnerSubscriptionCheckoutHistoryTest.php tests/Feature/Admin/AdminModuleRevenueDashboardTest.php
git commit -m "test: verify history and revenue integrity after checkout refactor"
```

## Task 10: End-to-End Verification and Documentation Update

**Files:**
- Modify: `docs/changelogs/` (new dated changelog entry)
- Modify: `docs/QUICK_TESTING_GUIDE.md` (if checkout QA steps need update)

**Step 1: Run focused verification suite**

Run:
- `php artisan test --filter=LearnerCheckoutFeatureFlagTest`
- `php artisan test --filter=LearnerCheckoutRoutingFlowTest`
- `php artisan test --filter=LearnerCheckoutSummaryViewTest`
- `php artisan test --filter=PayMongoPaymentLinkServiceTest`
- `php artisan test --filter=LearnerCheckoutCompletionIdempotencyTest`
- `php artisan test --filter=LearnerModulePaymentWebhookTest`

Expected: PASS.

**Step 2: Run broader payment/chat safety regression subset**

Run:
- `php artisan test --filter=Payment`
- `php artisan test --filter=Webhook`

Expected: PASS or clearly documented residual failures unrelated to this scope.

**Step 3: Update changelog and QA notes**

1. Add dated changelog documenting summary-first flow, sandbox messaging, and idempotency improvements.
2. Add test command list and known sandbox caveats.

**Step 4: Commit**

```bash
git add docs/changelogs docs/QUICK_TESTING_GUIDE.md
git commit -m "docs: record learner checkout refinement rollout and verification"
```

---

## Implementation Notes and Guardrails

- Keep controllers thin; business logic belongs in services.
- Keep role/authorization and learner age/visibility checks intact.
- Preserve route ownership conventions; learner checkout routes remain in `routes/web.php`.
- Do not bypass webhook signature verification for real environments.
- Keep all changes additive and reversible where possible.

## Definition of Done

Done when:
- Both module and subscription payments use summary-first flow.
- Learners can review purchase details and billing data before redirect.
- PayMongo payload sends all configured methods with optional preferred ordering.
- Cancel/fail returns learner to summary with retry path.
- Success is idempotent and side effects occur exactly once.
- Simulation routes are unavailable in production.
- Focused test suite passes and output is captured in final verification notes.
