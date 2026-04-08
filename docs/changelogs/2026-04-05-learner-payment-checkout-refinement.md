# 2026-04-05 - Learner Payment Checkout Refinement

## Summary
- Rolled out summary-first learner checkout for both subscription and module purchases.
- Simplified checkout summary UI into a confirmation-first page with module/instructor context and trust messaging.
- Removed sandbox banner, local payment-method selection, and billing input fields from learner checkout summary.
- Preserved full PayMongo `payment_method_types` payload and defaulted to card-first ordering when no preferred method is provided.
- Migrated learner checkout creation from Payment Links to Checkout Sessions with explicit `line_items`, `success_url`, `cancel_url`, and `payment_method_types`.
- Improved cancellation/failure behavior with platform-hosted cancel/success pages and module-specific retry/return actions.
- Hardened completion idempotency around duplicate webhook events and post-payment queue dispatch.
- Verified payment history and module revenue surfaces remain intact after checkout flow changes.

## Key Technical Changes
- Added learner checkout rollout and simulation environment controls in `config/billing.php`.
- Added summary routes and legacy route redirection behavior in `routes/web.php`.
- Introduced shared orchestration service in `app/Services/Checkout/LearnerCheckoutService.php`.
- Unified checkout validation contracts in:
  - `app/Http/Requests/Checkout/ProcessLearnerCheckoutRequest.php`
  - `app/Http/Requests/ProcessPaymentRequest.php`
  - `app/Http/Requests/ProcessModulePaymentRequest.php`
- Implemented shared summary UI in:
  - `resources/views/payments/checkout-summary.blade.php`
  - `resources/views/payments/partials/checkout-item-module.blade.php`
  - `resources/views/payments/partials/checkout-item-subscription.blade.php`
- Updated callback and completion flow behavior in:
  - `app/Http/Controllers/PaymentController.php`
  - `app/Http/Controllers/Learner/ModuleController.php`
  - `app/Services/ModulePurchaseService.php`
- Improved webhook reconciliation in `app/Http/Controllers/Api/WebhookController.php` by resolving subscription payments using metadata `payment_id` and pending/processing fallback.
- Added idempotent post-payment job dispatch guard in `app/Listeners/HandlePaymentSuccessful.php`.
- Updated PayMongo status-verification fallbacks across subscription index/dashboard/pending checks to support both `paymongo_checkout_session_id` and legacy `paymongo_link_id`.

## Test Coverage Added
- `tests/Feature/Learner/LearnerCheckoutFeatureFlagTest.php`
- `tests/Unit/Services/Checkout/LearnerCheckoutServiceTest.php`
- `tests/Feature/Learner/LearnerCheckoutValidationTest.php`
- `tests/Feature/Learner/LearnerCheckoutRoutingFlowTest.php`
- `tests/Feature/Learner/LearnerCheckoutSummaryViewTest.php`
- `tests/Feature/Learner/LearnerCheckoutPayloadContractTest.php`
- `tests/Feature/Learner/LearnerCheckoutCancelFailureFlowTest.php`
- `tests/Feature/Learner/LearnerCheckoutCompletionIdempotencyTest.php`
- `tests/Feature/Learner/LearnerSubscriptionCheckoutHistoryTest.php`

## Verification Commands Run
- `php artisan test --filter=Payment` -> PASS (16 tests, 47 assertions)
- `php artisan test --filter=Webhook` -> PASS (3 tests, 14 assertions)
- Focused checkout/payment suite -> PASS (14 tests)
  - LearnerCheckoutFeatureFlagTest
  - LearnerCheckoutRoutingFlowTest
  - LearnerCheckoutSummaryViewTest
  - PayMongoPaymentLinkServiceTest
  - LearnerCheckoutCompletionIdempotencyTest
  - LearnerModulePaymentWebhookTest

## Sandbox Caveats
- PayMongo sandbox may still present wallet flows as QR-style steps in hosted checkout.
- Application now explicitly communicates this behavior on checkout summary pages and keeps full method payloads for production parity.
- Simulation success routes remain restricted to non-production environments configured in `billing.payment.simulation_enabled_envs`.
