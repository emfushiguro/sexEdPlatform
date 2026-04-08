# Learner Payment Checkout Refinement Design

## 1. Purpose and Goals
Refine the learner-side payment experience for both paid module purchases and subscriptions so learners always review a clear purchase summary before leaving the platform for PayMongo checkout.

Primary goals:
- Improve payment flow UX with a summary-first checkout pattern.
- Keep development in sandbox mode while clarifying sandbox channel limitations.
- Refactor checkout architecture to reduce duplicated logic and improve reliability.
- Ensure learners clearly understand purchase type, item details, and total before paying.

## 2. Scope
In scope:
- Learner flows for module purchase and subscription purchase.
- Checkout summary pages and orchestration logic.
- PayMongo link payload validation and method configuration transparency.
- Success, cancellation, and failure UX behavior.
- Post-payment side effects (recording, receipt generation, entitlement/access updates).
- Focused tests covering checkout, webhook completion, and retry behavior.

Out of scope:
- Replacing PayMongo provider.
- Rebuilding admin analytics surfaces.
- New payment channels beyond configured PayMongo-supported types.

## 3. Approved Product Decisions
The following decisions were confirmed during brainstorming:
- Rollout: full replacement now for both module and subscription checkout flows.
- Checkout UI strategy: shared summary pattern with dynamic scope-specific sections.
- Payment method behavior: optional preferred method from learner page, final method selection remains in hosted PayMongo checkout.
- Sandbox behavior: explicitly support and explain QR-centric sandbox behavior while still sending multiple method types.
- Cancellation target: return learner to checkout summary page with retry path.
- Failure messaging: friendly explanation plus retry option.
- Success redirect: module purchase -> module overview, subscription purchase -> subscription overview.
- Receipt handling: queued generation after confirmation.
- Idempotency: strict idempotency for callback and completion side effects.
- Simulation policy: allowed in local/testing/staging only, blocked in production.
- Billing details: require name/email/phone for subscription flow for parity with module flow.
- Summary content density: standard detail level (clear, not overloaded).
- Verification depth: focused feature and unit tests.
- Launch safety: environment-based feature flag.

## 4. Current State Findings
Observed from the current codebase:
- Runtime mode currently resolves to sandbox.
- Configured PayMongo methods include gcash, paymaya, grab_pay, card.
- Existing module payment already has a learner purchase form and pending flow.
- Existing subscription payment has a separate payment create/process flow.
- Existing webhook and pending reconciliation logic already support robust confirmation paths.

Implication:
- QR-only behavior in sandbox is likely channel/account/environment behavior in hosted checkout, not only a payload omission issue.
- We still need UX-level clarity and architecture unification to prevent learner confusion.

## 5. Target User Experience
### 5.1 Module Purchase
1. Learner clicks Pay Module.
2. Learner lands on module checkout summary page.
3. Learner reviews module details, instructor, price, billing details, and total.
4. Learner clicks Proceed to Payment.
5. System creates/updates pending payment and redirects to PayMongo checkout URL.
6. On confirmed payment, learner gets module access and is redirected to module overview.
7. On cancel/failure, learner returns to module checkout summary with retry messaging.

### 5.2 Subscription Purchase
1. Learner clicks Subscribe Now.
2. Learner lands on subscription checkout summary page.
3. Learner reviews plan details, duration, billing details, and total.
4. Learner clicks Proceed to Payment.
5. System creates/updates pending subscription payment and redirects to PayMongo checkout URL.
6. On confirmed payment, subscription activates and learner is redirected to subscription overview.
7. On cancel/failure, learner returns to subscription checkout summary with retry messaging.

## 6. UI and Content Specification
Checkout summary pages must align with existing learner dashboard design system:
- Tailwind utility conventions already used in learner views.
- Card-based sections with consistent spacing and typography.
- Existing button styles and hierarchy.

Required sections for both scopes:
- Purchase Type
- Item Details
- Billing Information
- Payment Summary (price, total)
- Terms acceptance
- Proceed to Payment action

Module-specific fields:
- Module name
- Instructor name
- Optional short description
- Module one-time price

Subscription-specific fields:
- Plan name
- Plan duration
- Plan price
- Total amount

Environment notice:
- Sandbox mode banner explaining that hosted checkout method availability and QR screens in sandbox may differ from production.

## 7. Architecture Design (Approach A)
Adopt unified checkout orchestration with a shared learner checkout pattern.

### 7.1 Controller Layer
Controllers remain thin and only:
- Build summary context for GET routes.
- Validate and submit checkout requests for POST routes.
- Delegate payment link creation and pending-record handling to orchestration service.

### 7.2 Service Layer
Create a unified learner checkout orchestration service responsible for:
- Preparing checkout context for module/subscription.
- Validating purchase prerequisites and billing payload.
- Creating or reusing pending payment safely.
- Calling PayMongo link creation with complete method list and preferred ordering.
- Returning consistent redirect information.

Domain-specific completion remains in existing services:
- Module completion/enrollment in ModulePurchaseService.
- Subscription activation in SubscriptionService.

### 7.3 View Layer
Use shared summary layout and reusable partials/components for:
- Summary cards
- Billing form block
- Payment summary block
- Sandbox info block

## 8. PayMongo Integration Rules
1. Keep current mode in sandbox for development.
2. Ensure payment_method_types always contains all allowed configured methods.
3. If learner chooses a preferred method, place it first but do not restrict to one method.
4. Keep final payment-method choice in hosted PayMongo page.
5. Preserve metadata for scope, item identifiers, user, and billing snapshot.

## 9. Post-Payment Behavior
Upon successful payment confirmation:
- Mark payment completed exactly once.
- Persist actual paymongo payment reference where available.
- Queue receipt generation.
- Module scope:
  - Mark module purchase completed.
  - Ensure module enrollment is granted according to enrollment mode.
  - Record module revenue and instructor earnings via existing ledger flow.
- Subscription scope:
  - Activate subscription idempotently.

Redirect targets:
- Module payment success -> module overview page.
- Subscription payment success -> subscription overview page.

## 10. Error, Cancellation, and Retry Behavior
Cancellation:
- Return learner to corresponding checkout summary page.
- Show message: Payment was cancelled. You may try again anytime.

Failure:
- Show clear error notice.
- Keep retry path prominent.

Gateway/API issues:
- Preserve pending state and allow resume/retry.
- Use non-technical learner messaging.
- Keep diagnostic logs for maintainers.

## 11. Idempotency and Safety
- Enforce idempotent completion across webhook and pending-page verification.
- Guard duplicate side effects (access grant, activation, receipts, ledgers).
- Keep strict webhook signature verification.
- Restrict simulation success endpoints to local/testing/staging; block in production.

## 12. Testing Strategy
Focused automated coverage:
- Unit tests:
  - Payment method resolution and ordering behavior.
  - Checkout orchestration branching for module/subscription.
  - Idempotent completion guard behavior.
- Feature tests:
  - Summary pages render correct purchase details.
  - Proceed-to-payment creates expected pending records and redirects.
  - Cancel/failure returns to summary with retry messaging.
  - Webhook and pending polling complete payments correctly per scope.

## 13. Rollout and Monitoring
- Gate full replacement with environment feature flag.
- Enable in local/testing/staging first, then production after verification.
- Add structured logs for checkout creation, link payload summary, callback outcomes, and reconciliation path decisions.

## 14. Acceptance Criteria
Feature is accepted when:
- Both module and subscription flows always pass through summary page before hosted checkout.
- Learners can clearly review purchase details and total prior to payment.
- PayMongo payload includes all configured methods while allowing preferred ordering.
- Cancellation/failure paths return to summary with retry UX.
- Successful payment reliably records transaction, queues receipt, and grants correct access/activation.
- Duplicate callbacks/retries do not create duplicate side effects.
- Simulation endpoints are unavailable in production.

## 15. Risks and Mitigations
Risk: Sandbox behavior still appears QR-heavy even with full method payload.
Mitigation: Explicit sandbox UX messaging and environment-specific expectations.

Risk: Refactor could regress one of the two checkout scopes.
Mitigation: Shared orchestration contracts and scope-specific feature tests.

Risk: Duplicate confirmation events.
Mitigation: Strict idempotency checks around payment completion and side effects.

---

Approval status: Approved by user during brainstorming on 2026-04-05.
