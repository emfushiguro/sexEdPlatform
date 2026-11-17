# Guardian Gift Regression Fixes Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans to implement these approved bug fixes in the current checkout. Do not create a worktree or commit unrelated changes.

**Goal:** Fix the four defects reproduced in the guardian gift verification report.

**Architecture:** Keep the existing PayMongo checkout and verified gift fulfillment. Reconcile authenticated expired checkout responses in the gift service, preserve regular subscription controls until gift fulfillment, and recheck plan availability before reserving checkout. Use responsive CSS for the My Children header.

**Tech Stack:** Laravel, MySQL, Blade, Tailwind, PHPUnit, Puppeteer.

**Spec:** The user's approved request to fix the verification findings; existing guardian gifting and dynamic plan requirements.

## Global Constraints

- Preserve purchaser, beneficiary, historical amounts, purchased terms, and existing records.
- Never activate from redirects or release a potentially payable session based on a failed browser callback.
- No migrations, new payment integration, refund functionality, worktree, commit, or push.
- Run database tests only after verifying testing/mysql/cc_db_test on isolated port 33309.

## Review Focus

- A failed attempt inside an active session must keep the original payable checkout.
- An expired response with a mismatched session or mode must not release another checkout.
- An expired gift renewal must preserve existing paid access and permit another purchase.
- A deactivated or unavailable plan must be rejected before a new gateway request; an already issued quote retains its history.
- Unpaid gifts must not suppress regular controls; completed gifts remain prepaid and non-refundable.

### Task 1: Preserve the failing cases

**Files:** `tests/Feature/Parent/SubscriptionGifting/GuardianSubscriptionGiftTest.php`.

- [x] Add the three reproduced payment/renewal/catalog cases to the existing suite, reusing its family fixtures.
- [x] Add checks for mismatched expired sessions, failed attempts in active sessions, renewal access preservation, and stale plan availability.
- [x] Run the new tests on isolated MySQL and confirm the three known failures before implementation.

### Task 2: Correct gift lifecycle and checkout validation

**Files:** `app/Services/GuardianSubscriptionGiftService.php`, `app/Services/SubscriptionService.php`, `app/Http/Controllers/Learner/SubscriptionController.php`, `resources/views/payments/pending.blade.php`; the existing gift tests and `tests/Support/GiftConcurrencyScenario.php`.

**Interfaces:** Preserve `verifyAndCompletePendingPayment(Payment): bool`, `reserveCheckout(Payment, User): array`, and `hasGiftPayments(Subscription): bool`. Add `isGiftManaged(Subscription): bool` for display and management decisions.

- [x] Reconcile a verified expired unpaid session under the existing dependent/payment locks; exclude that closed session from unresolved purchases.
- [x] Stop pending-page polling and show retry guidance when gift payment becomes terminal.
- [x] Recheck current active/archive/availability/price eligibility before creating a session, preserving the frozen purchase quote. Release a locally unstarted invalid purchase without touching existing access.
- [x] Treat regular subscriptions as gift-managed only once gift fulfillment has applied.
- [x] Rerun the new tests and the full gift/concurrency suites.

### Task 3: Correct mobile overflow

**File:** `resources/views/parent/children/index.blade.php`.

- [x] Allow the My Children heading and action group to stack and wrap on narrow screens.
- [x] Verify the entire page and cards at 390px and desktop widths with the existing browser preview.

### Task 4: Verify and review

- [x] Run the related subscription, payment verification, admin payment, parent, lifecycle and entitlement tests.
- [x] Build production assets and run `git diff --check`.
- [x] Obtain an independent read-only code review and resolve any blocking findings.
- [x] Report fresh results separately from the five previously recorded baseline failures. Leave unrelated module-payment behavior unchanged.

## Review Decisions

- Preserve broad safety checks for unresolved and applied gifts. Use the separate management predicate for the regular subscription interface, cancellation, and renewal entry points.
- Ignore only certified closed history: cancelled and unapplied with an expired session, or cancelled and unavailable without any provider session. This permits regular renewal after an unpaid gift closes.
- Serialize regular renewal preparation and cancellation on the dependent/subscription. Reconcile expiry from a freshly locked subscription for every entry point, including scheduled expiry.
- Extend the existing independent-process concurrency fixture rather than introducing another test framework. Wait for worker readiness and inspect only current transaction waits, excluding historical deadlock output.
- Keep all changes uncommitted in the user's existing checkout.

## Verification Results

- Gift and concurrency suites: 89 tests, 585 assertions, no failures or errors.
- Related regression run: 158 tests, 894 assertions, five failures and one risky-test warning. Failed test identities exactly match the pre-fix baseline: three module-payment cases and two learner UI assertions expecting the previous renewal button label.
- Production build, PHP syntax checks, and `git diff --check` passed. Browser preview at 390px reports no card or page overflow; desktop actions remain on one row.
- Final independent review found no additional introduced blockers. PayMongo API responses were mocked for automated verification; no real payment was made.
