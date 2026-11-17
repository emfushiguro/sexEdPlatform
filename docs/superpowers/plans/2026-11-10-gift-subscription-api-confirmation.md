# Gift Subscription API Confirmation Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans in the existing checkout. No worktree, commit or push.

**Goal:** Confirm gift payments through the existing PayMongo API lookup used by regular subscriptions, including sandbox use without webhooks.

**Architecture:** Reuse PayMongoPaymentLinkService::retrieveCheckoutSession(). Share gift validation and atomic term application between authenticated session retrieval and signed webhook handling. Connect the existing return, pending, polling and purchaser-history paths to this verifier.

**Tech Stack:** Laravel, existing PayMongo service, PHPUnit.

**Spec:** docs/superpowers/specs/2026-11-09-guardian-subscription-gifting-design.md (2026-11-10 update supersedes webhook-only confirmation).

## Constraints and review focus

- Guardian remains purchaser; only the dependent receives access.
- Compare stored session, payment, subscription, purchaser, amount, currency and test/live mode with PayMongo's response.
- Checkout status may remain active even with a paid payment: use the returned paid payment object.
- Unpaid, failed, mismatched and unavailable API responses preserve pending access.
- API and webhook repeats share the existing applied_at marker and cannot extend twice.
- Preserve frozen price/duration and existing gateway, signatures, checkout and database schema.

## Implementation

- [x] Add failing route tests for API confirmation on return, pending, polling and history, plus mismatch/failure, authorization and repeat/extension cases.
- [x] Add GuardianSubscriptionGiftService::verifyAndCompletePendingPayment(Payment $payment): bool and share verified-session fulfillment with completeFromWebhook().
- [x] Route gift confirmation through PaymentController::verifyAndActivateIfPaid() at the existing entry points.
- [x] Run focused gift, ordinary subscription success/renewal and payment tests on the isolated test database; lint and check the diff.
- [x] Fresh review: fix payer confirmation after relationship removal and history retry of missing follow-ups. Recovery tests were RED, then GREEN (5 tests, 25 assertions). Reviewer rechecked both fixes without further important findings.

## Verification results

- Core regression: 73 tests, 443 assertions passed on disposable MySQL port 33309, database cc_db_test. Includes gift flows, regular success verification, history synchronization, renewals, subscription checkout/history, payment creation and gateway service tests.
- PHP lint and git diff --check passed. No frontend source changed, so no asset rebuild was needed.
- A broader payment/module run had 74 tests, 441 assertions and five failures. All five already occur in the saved unchanged-HEAD baseline for 521feabe:
  - LearnerCheckoutPayloadContractTest::test_module_checkout_without_local_method_selection_still_sends_full_method_list
  - LearnerCheckoutPayloadContractTest::test_module_checkout_sends_full_method_list_with_preferred_first
  - LearnerCheckoutRoutingFlowTest::test_module_payment_entry_redirects_to_module_summary_page
  - LearnerCheckoutCompletionIdempotencyTest::test_duplicate_webhook_event_does_not_duplicate_module_completion_side_effects
  - LearnerModulePaymentWebhookTest::test_paymongo_webhook_completes_module_purchase_and_enrollment
- Read-only lookup of an existing sandbox checkout confirmed its returned payment was paid despite the enclosing checkout status remaining active. No development payment was manually activated during verification.
