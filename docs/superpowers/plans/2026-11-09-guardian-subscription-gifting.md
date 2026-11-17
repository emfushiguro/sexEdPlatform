# Guardian Subscription Gifting Implementation Plan

> Confirmation policy revised by user direction on 2026-11-10: see `2026-11-10-gift-subscription-api-confirmation.md`. Its authenticated API confirmation replaces the webhook-only restriction in this original implementation record.

> Execute using superpowers:executing-plans, superpowers:test-driven-development and a final superpowers:requesting-code-review. Each task uses RED/GREEN verification.

**Goal:** Guardians purchase prepaid learner subscription access for verified dependents through existing checkout.

**Architecture:** A small gift integration service prepares an existing subscription/payment pair and applies a signed payment once. Existing metadata represents gift context; narrowly extend existing checkout authorization and lifecycle dispatch.

**Tech Stack:** Existing PHP/Laravel, MySQL/MariaDB, Blade, Tailwind, Alpine and PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-09-guardian-subscription-gifting-design.md`

## Global Constraints

- Current checkout; no worktree, commit or push.
- No schema changes anticipated and no development data reset/reseed.
- Preserve gateway calls, signature middleware and ordinary billing behavior.
- Gift payments are final and non-refundable; add no gift refund flow and guard legacy refund endpoints for gift-funded access.

## Review Focus

- Any existing activation/renewal/admin/simulation path must not bypass signed gift fulfillment.
- Binding an exact payment must not disclose or spend another guardian's pending payment.
- Failed checkout/cleanup must not cancel independently paid access or create automatic retries.
- A duplicate or failed webhook must not consume a term or poison future retries.
- Ordinary learner reconciliation/refund endpoints must not expose guardian payment information.

### Task 0: Record baseline and isolate tests

- [x] Record HEAD/status and inspect relevant schema on an isolated test connection.
- [x] Confirm effective testing environment and connected `cc_db_test` before database tests.
- [x] Run existing focused billing tests to establish baseline; preserve development database.

### Task 1: Guardian authorization and purchase preparation

**Create:** `app/Services/GuardianSubscriptionGiftService.php`, `app/Http/Requests/Parent/StoreSubscriptionGiftRequest.php`, `app/Http/Controllers/Parent/SubscriptionGiftController.php`.
**Modify:** `app/Policies/ParentChildPolicy.php`, `routes/web.php`, `app/Services/SubscriptionService.php`.
**Test:** `tests/Feature/Parent/SubscriptionGifting/GuardianSubscriptionGiftTest.php`.

- [x] RED: approved guardian produces dependent subscription/guardian payment; unverified/unrelated/self purchases are forbidden; same request produces one payment.
- [x] GREEN: `prepare(User $guardian, User $dependent, SubscriptionPlan $plan, string $requestKey): Payment` freezes quote and serializes preparation on beneficiary. Authorization uses registered Laravel policy.
- [x] Preserve existing creation behavior for non-gifts; don't bulk-cancel gift or unrelated pending attempts.

### Task 2: Existing checkout integration

**Modify:** `PaymentController.php`, existing checkout item partial and request context; reuse `PayMongoPaymentLinkService` unchanged.
**Test:** same feature test, focused checkout tests.

- [x] RED: guardian can use existing checkout for exact gift payment; dependent/other guardian cannot view/pay/read receipt; duplicate checkout creates at most one session; frozen amount survives catalogue edits.
- [x] GREEN: purchaser authorization, exact payment binding, durable in-progress/unknown provider reservation, existing checkout template and gateway call. Gift callback/poll/history paths read state, never reconcile via frontend/API fallback.

### Task 3: Signed once-only fulfillment

**Modify:** `Api/WebhookController.php`, `PaymentObserver.php`, `SubscriptionService.php`, narrowly scoped CSRF exception if necessary.
**Test:** signed webhook, mismatched session/amount/mode, pending/failure, duplicate event IDs and retry-after-rollback.

- [x] RED: only valid signed checkout event gives correct dependent access once; pending/failed/redirect/admin/simulation do not grant.
- [x] GREEN: `completeFromWebhook(array $event): void` validates persisted identifiers and quote, then commits payment/application marker/dates together. Dispatch existing financial events and first welcome job after commit, with retryable per-action tracking.
- [x] Same-plan extension uses frozen duration and current locked expiry; different active plan cannot be overwritten.

### Task 4: Lifecycle and privacy safeguards

**Modify:** learner subscription controller/view, subscription service expiry/reconciliation, dunning service, pending cleanup command.
**Test:** renewal/expiry, revoked link, dependent financial privacy, failed gift payment does not alter existing access, competing renewals, ordinary subscriptions.

- [x] RED: gifted access is read-only for dependent financial actions; no automatic gift retries/grace; stale expiry/cleanup cannot erase new paid access.
- [x] GREEN: gift-only branches, locked date rereads and preserve historical payment ownership. Refuse legacy refund of mixed/gift-funded access with non-refundable policy guidance.

### Task 5: UI and communications

**Create:** `resources/views/parent/subscriptions/gift.blade.php`.
**Modify:** parent children index/show, payment checkout item, gift result/receipt copy and links.
**Test:** rendered eligibility/action/plan selection, guardian success destination, dependent status without billing details, mail recipients.

- [x] RED: Gift Subscription action and usable confirmation; payer receipt identifies dependent and routes to payer payment history.
- [x] GREEN: reuse existing layout/styles/checkout; no separate dashboard or frontend framework.

### Task 6: Regression, review and handoff

- [x] Run focused gift suite and existing learner subscription/payment, instructor, module-webhook and connector regression tests on isolated database.
- [x] Run full PHPUnit suite if isolation/baseline permits; report all failures rather than masking them.
- [x] Run PHP lint, formatting/diff checks and Blade rendering checks.
- [x] Fresh read-only code reviewer examines full uncommitted diff against spec; fix important findings with RED/GREEN tests.
- [x] Report implemented behavior, test evidence and final/non-refundable gift policy. Leave changes uncommitted.

## Execution and verification

Implemented in the existing `main` checkout at `521feabe`, without a worktree, commit, push, schema changes or new dependencies. The existing gateway service and webhook signature middleware remain unchanged. Checkout, assignment, lifecycle and administrative completion files received scoped gift guards.

Gift payments are final and non-refundable. No refund workflow was added; legacy dependent refund actions reject gift-funded subscriptions. Guardians remain the purchasers, dependents receive the access, and manual same-plan purchases extend the existing expiry.

| Check | Result |
| --- | --- |
| Initial unchanged billing baseline | 4 tests, 28 assertions passed |
| Final gift and focused billing verification | 36 tests, 211 assertions passed |
| Full Unit/Feature run | 911 tests, 3,922 assertions; 40 failures, 5 errors, 2 risky tests |
| Unchanged HEAD comparison for failing classes | 109 tests, 341 assertions; the identical 40 failures, 5 errors and 2 risky tests reproduced |
| PHP lint / whitespace | All 28 changed/new PHP files passed lint; `git diff --check` passed |

The full run includes learner billing, instructor subscriptions, module checkout/webhooks and connectors. Every failing test identity and failure/error type matched the unchanged source snapshot; no unrelated repairs were made. Existing failures cover admin/module authoring, registration and application expectations, module checkout/enrollment, renewal-copy expectations and monitoring copy. The final active-plan edge-case guard was verified in the final focused run after the full run.

Fresh code review findings were reproduced and fixed: competing self/gift purchases, legacy/admin gift completion, retrying failed follow-up enqueue, and immutable printable receipt details. Two independent-process database scenarios verify once-only confirmation and competing gift/self preparation. All database runs used a disposable MariaDB instance on `127.0.0.1:33309` with a new ignored data directory; development data and `.env` were preserved.

Provider calls were mocked in automated tests. Live PayMongo checkout/delivery and manual browser visual QA were not performed. Unknown provider-session creation remains pending for support reconciliation and cannot automatically create a second charge.
