# Subscription Lifecycle Stabilization and Renewal UX Design

## 1. Purpose and Goals
Stabilize the learner and admin subscription experience end-to-end so billing state, entitlement enforcement, renewal behavior, payment transparency, and date display are reliable and consistent.

Primary outcomes:
- Fix expiration handling so premium access and entitlements are revoked immediately when a subscription expires.
- Ensure start and end timestamps are stored and displayed accurately in admin and learner surfaces.
- Implement renewal flow for both expired and expiring subscriptions.
- Improve learner payment success UX with direct receipt visibility.
- Remove redundant subscription page sections and align Free Plan card UX with paid plans.

## 2. Non-Negotiable Constraints
- Keep Laravel server-rendered architecture (Blade + Alpine).
- Keep controllers thin; lifecycle and entitlement logic remains in services.
- Keep role boundaries and route ownership unchanged.
- Preserve backwards compatibility while normalized subscriber columns are already in mixed use.
- Use additive and reversible schema updates only.

## 3. Locked Product Decisions
1. Renewal extension anchor: extend from the later of now or current expiry.
2. Renewal CTA threshold: configurable per plan.
3. Renewal persistence: update subscription in place and add renewal history metadata/event context.
4. Renewal eligibility: expired or expiring-soon.
5. Renewal entry: learner always sees checkout summary before payment provider redirect.
6. Expiration enforcement: scheduled command plus runtime reconciliation.
7. Status model: keep expanded lifecycle statuses and map to simplified labels in UI.
8. Date source of truth: normalized timestamps first, legacy fallback second.
9. Historical backfill: combined heuristic strategy.
10. Payment success receipt CTA: always show with fallback to payment history.
11. Free Plan feature copy: explicit line-by-line descriptions.
12. Expiry fallback behavior: immediate free-baseline entitlement behavior.
13. Renewal notice surfaces: subscription page, learner dashboard, learner profile subscription tab.
14. Verification scope: targeted subscription/payment/admin tests.

## 4. Architecture and Service Ownership
### 4.1 Single Lifecycle Authority
`app/Services/SubscriptionService.php` remains the main mutation/query authority for:
- activation
- renewal
- expiration
- runtime lifecycle reconciliation
- effective premium checks

Controllers only orchestrate input/output:
- `app/Http/Controllers/Learner/SubscriptionController.php`
- `app/Http/Controllers/PaymentController.php`
- `app/Http/Controllers/Admin/SubscriberAdminController.php`

### 4.2 Entitlement Enforcement Boundary
`app/Services/EntitlementService.php` and `SubscriptionService` entitlement resolution must rely on an effective-active state (status + time boundary), not status alone.

### 4.3 Runtime + Scheduled Safety Net
- Runtime: request-time reconciliation checks stale active-but-expired records and normalizes state.
- Scheduled: subscription expiry command continues batch enforcement, event dispatch, and reminders.

## 5. Data Model and Storage Strategy
### 5.1 Canonical Fields
Use normalized datetime fields as primary:
- `starts_at`
- `ends_at`
- `next_billing_at`
- `plan_price_id`

Legacy fallbacks stay in place for compatibility:
- `start_date`
- `end_date`

### 5.2 Additive Schema Update
Add per-plan configurable renewal warning threshold field on plans, with a sane default fallback in code when null.

### 5.3 Backfill Strategy
Populate normalized timestamps for historical records using priority order:
1. payment `paid_at` and known billing duration
2. existing `start_date` and `end_date`
3. conservative `created_at` fallback when neither exists

No destructive migration in this rollout.

## 6. Subscription Lifecycle Rules
### 6.1 Effective Active Rule
A subscription is effectively active only when:
- lifecycle status allows premium access (`active` and approved transitional states if retained), and
- current time is strictly before effective end timestamp.

### 6.2 Expiration Rule
Expire when `now >= effective_end_timestamp`.

On expiration:
- set status to `expired`
- clear any premium-only behavioral assumptions
- entitlement checks immediately resolve to free baseline capabilities

### 6.3 Renewal Rule
Renewal extends from:
- `max(now, current_expiry)` as anchor

Then add the plan billing duration to compute new `ends_at` and synchronized legacy/end fields.

## 7. Renewal Experience Design
### 7.1 Renewal Availability
Show renewal CTA when:
- subscription is expired, or
- subscription is within configured plan threshold window.

### 7.2 Renewal Flow
1. Learner clicks Renew Subscription.
2. Learner is routed to checkout summary.
3. Learner confirms billing details.
4. Learner proceeds to PayMongo.
5. On success, subscription is renewed using extension anchor rule.

### 7.3 Renewal UX Placements
- `resources/views/subscriptions/index.blade.php`
- learner dashboard subscription widgets
- learner profile subscription section/partial

## 8. Learner Subscription Page UX Changes
Target: `resources/views/subscriptions/index.blade.php`

### 8.1 Remove Redundant Section
Remove entitlement snapshot block entirely.

### 8.2 Free Plan Visual Parity
Ensure Free Plan card uses same structural language as paid cards:
- consistent spacing
- consistent typography scale
- same visual card rhythm and information hierarchy

Free Plan remains clearly labeled as free/baseline.

### 8.3 Explicit Free Feature Copy
Replace vague labels with explicit capability lines:
- Access to modules
- Access to lessons
- Access to lesson topics
- Ability to take quizzes
- Module certificate viewing
- Chat access with instructors
- Limited quiz attempts
- Limited username changes

## 9. Payment Success and Receipt Visibility
Target: `resources/views/payments/success.blade.php`

Add `View Receipt` action in success CTA group.

Behavior:
- when specific payment record is resolvable: route to receipt page
- when not resolvable: route to payment history

Keep existing dashboard/subscription navigation actions.

## 10. Admin Subscriber Date Accuracy
Targets:
- `app/Http/Controllers/Admin/SubscriberAdminController.php`
- `resources/views/admin/subscriber/index.blade.php`
- `resources/views/admin/subscriber/show.blade.php`

Display precedence:
1. `starts_at` / `ends_at`
2. fallback to legacy `start_date` / `end_date`

Admin should always display time-inclusive format to avoid midnight-only ambiguity.

## 11. Error Handling and Observability
- Keep all lifecycle state mutations in DB transactions.
- Emit structured logs for expiry and renewal transitions (subscription id, user id, old/new status, timestamps).
- Keep user-safe fallback flows for unresolved payment/receipt state.

## 12. Testing Strategy
### 12.1 Unit Tests
- renewal anchor computation
- effective-active resolution
- expiration transition behavior

### 12.2 Feature Tests
- entitlement revocation immediately after expiry
- renewal CTA visibility with plan threshold
- renewal checkout to activation flow
- payment success receipt CTA behavior and fallback
- admin subscriber date rendering precedence

### 12.3 Regression Targets
- existing learner subscription/payment tests
- lifecycle status tests

## 13. Rollout and Risk Control
- Ship in additive mode with compatibility fallbacks.
- Keep legacy date columns readable while transitioning displays and logic.
- Avoid destructive migrations in this phase.
- Verify on targeted test set before completion claims.

## 14. Acceptance Criteria
- Expired subscriptions no longer retain premium entitlement behavior.
- Premium badge and premium-only capabilities are both deactivated post-expiry.
- Renewal works for expired and expiring subscriptions and preserves remaining paid time.
- Renewal warning threshold is configurable per plan.
- Learner success page includes receipt visibility action.
- Free Plan card is visually aligned and explicitly descriptive.
- Admin subscriber dates are accurate and time-based.

## 15. Approval
Design approved by user on 2026-04-06 with Approach 1 (service-centric stabilization) and all lifecycle/UX decisions locked.
