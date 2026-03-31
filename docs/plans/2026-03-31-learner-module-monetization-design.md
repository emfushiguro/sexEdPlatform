# Learner Module Monetization and Enrollment Visibility Design

**Date:** 2026-03-31
**Status:** Approved
**Approach:** Approach A (Unified payment backbone with dedicated module purchase records)

## 1. Objective

Deliver a learner-side experience that fully supports instructor-configured module pricing and enrollment limits, surfaces instructor identity clearly, and enforces paid access via existing PayMongo integration.

This design covers:
1. Learner module catalog cards
2. Module overview page updates
3. Free vs paid learner flow
4. Payment orchestration and access unlocking
5. Payment history extension

## 2. Locked Decisions

1. Paid modules use one-time payment per module per learner.
2. Premium subscription does not bypass module payment.
3. Learners see only approved and published modules.
4. Free module flow: Start Learning to module overview, then Enroll Now.
5. Paid module flow: Start Learning to module overview, then Price button to checkout.
6. Parent approval is required before payment for parent-managed learners.
7. If module is full, block payment link creation.
8. Existing paid learners keep access even if module later becomes full.
9. Webhook is payment source of truth; callback and polling are fallback.
10. No automated refunds in this phase.
11. Payment history page is extended to include module payments.
12. Module overview adds an Instructor Information Card.
13. Instructor card includes profile photo, profile summary, and View Full Background action.
14. Price formatting uses PHP display with P prefix, no decimals for whole amounts.
15. Catalog uses Full label; module overview uses Enrollment Closed label.
16. Duplicate purchase attempts should short-circuit to existing ownership state.
17. Test coverage must include free/paid branching, full-capacity blocking, and webhook unlock.

## 3. Scope

### In Scope
1. Learner module catalog metadata updates (instructor, price, enrollment occupancy).
2. Module overview metadata and instructor information card.
3. Paid checkout start from module overview.
4. Module purchase recording and payment linkage.
5. Enrollment creation after payment with auto/manual mode handling.
6. Parent approval gate for paid modules.
7. Unified payment history listing for subscription and module transactions.

### Out of Scope
1. Automated refund workflows.
2. Instructor public profile redesign beyond linking to full background view.
3. Multi-currency support beyond PHP.
4. Waitlist and overbooking behavior.

## 4. Current-State Baseline

1. Module model already has pricing and capacity fields (`access_type`, `price_amount`, `price_currency`, `enrollment_limit`).
2. Learner module catalog and module overview pages already exist.
3. Learner enrollment flow already supports auto/manual enrollment and parent approval for free modules.
4. PayMongo integration and pending-payment polling patterns already exist for subscriptions.
5. Payment history page already exists for subscriptions.

Gap:
1. No module-specific purchase ledger.
2. No paid-module checkout path from learner module overview.
3. Catalog cards do not yet show complete instructor, pricing, and occupancy data.
4. Module overview lacks instructor card and paid purchase action state.

## 5. Architecture

### 5.1 Core Pattern

Use one payment platform flow and add module purchase domain records:
1. Payments table remains the canonical transaction record.
2. New module purchase records represent ownership and unlock rights.
3. Enrollment remains the learning-access state.

### 5.2 Responsibility Boundaries

1. Controllers stay thin.
2. Service layer handles purchase eligibility, checkout orchestration, and enrollment post-payment.
3. Views render state only (not business logic).

### 5.3 Idempotency and Reliability

1. Webhook processing must be idempotent.
2. Duplicate webhook or callback events must not create duplicate purchases/enrollments.
3. Existing ownership should bypass new checkout attempts.

## 6. Data Design

## 6.1 New Entity: Module Purchase

Recommended fields:
1. `user_id`
2. `module_id`
3. `payment_id` (nullable during pending flow, required on completion)
4. `amount`
5. `currency`
6. `status` (`pending`, `completed`, `failed`, `cancelled`)
7. `purchased_at`
8. `metadata` (optional payload for PayMongo references)

Constraints:
1. One completed purchase per learner per module.
2. Index on (`user_id`, `module_id`).

### 6.2 Payment Metadata Extension

Store module context in `payment_details`:
1. `payment_scope = module_purchase`
2. `module_id`
3. `module_purchase_id` (if created before checkout)
4. PayMongo link/payment references

This enables payment history filtering without breaking subscription payment logic.

## 7. Learner Visibility and Card Contract

Catalog query contract:
1. Learner sees only approved and published modules (same governance gate used elsewhere).
2. Include instructor identity (`created_by` user and instructor profile).
3. Include approved enrollment count per module.
4. Include learner purchase/enrollment status.

Card metadata contract:
1. Instructor avatar + instructor name.
2. Price display:
   - Free modules show Free.
   - Paid modules show Pxxx (PHP formatted).
3. Enrollment occupancy:
   - `current / limit Enrolled` when limited.
   - `current Enrolled` when unlimited.
4. Full state:
   - Show Full badge on card when occupancy reached.
   - Disable direct enroll/purchase CTA when full.

## 8. Module Overview Contract

### 8.1 Instructor Information Card

Render:
1. Instructor photo from instructor profile (fallback avatar).
2. Instructor display name.
3. Short instructor summary (if available).
4. View Full Background button.

Button target:
1. Route to full instructor background profile page (read-only learner-facing view).

### 8.2 Module Metadata Panel

Render:
1. Price (Free or P amount).
2. Access type (free/paid).
3. Enrollment mode (open/manual approval).
4. Enrollment occupancy and limit.
5. Enrollment state label (Open or Enrollment Closed).

### 8.3 Paid Module Action State

If paid and not purchased:
1. Show Price button as primary CTA.
2. Keep curriculum visible but lock learning actions.

If paid and purchased:
1. Show enrollment/access CTA based on enrollment mode and status.

## 9. End-to-End Flows

### 9.1 Free Module

1. Learner clicks Start Learning on catalog card.
2. Learner is redirected to module overview.
3. Learner clicks Enroll Now.
4. System checks age and parent-approval requirement.
5. If parent approval required, create pending-parent enrollment.
6. If open enrollment, create approved enrollment and grant access.
7. If manual enrollment mode, create pending enrollment.

### 9.2 Paid Module

1. Learner clicks Start Learning on catalog card.
2. Learner is redirected to module overview.
3. If already purchased, skip checkout path.
4. If not purchased, learner clicks Price button.
5. System checks parent approval requirement first.
6. If parent approval missing, create/retain pending-parent state and stop.
7. If parent approval satisfied, system checks module capacity.
8. If full, block checkout and show Enrollment Closed.
9. If not full, create payment link and redirect to PayMongo checkout.
10. On webhook success, mark payment completed, mark purchase completed, then create enrollment:
    - auto mode -> approved enrollment
    - manual mode -> pending enrollment
11. Redirect learner back to module overview with unlocked state.

## 10. Capacity and Enrollment Rules

1. Capacity checks are executed before creating checkout links.
2. Capacity checks use approved enrollments count.
3. Full modules reject new purchase starts.
4. Previously completed purchases remain valid and do not get revoked when capacity later changes.

## 11. Parent Approval Rule for Paid Modules

1. Parent approval must happen before payment initiation.
2. Child learner without approval cannot generate checkout link.
3. After approval, learner can continue purchase flow from overview.

## 12. Payment Integration Strategy

1. Reuse existing PayMongo payment link service.
2. Introduce module-purchase scope in payment records.
3. Webhook performs final confirmation and unlock actions.
4. Callback and polling are fallback only for UX resilience.
5. All completion handlers must be safe under retries and duplicate notifications.

## 13. Payment History Extension

1. Extend existing learner payment history listing to include module payments.
2. Add type grouping or filter:
   - all
   - subscription
   - module
3. Module rows show module title and transaction status.
4. Keep existing subscription rendering unchanged.

## 14. Error and UX States

1. Full module: show Full on cards and Enrollment Closed in overview.
2. Payment pending: keep learner in pending state with status checks.
3. Payment failed/cancelled: show retry action on overview.
4. Manual enrollment after purchase: show Payment Confirmed, Enrollment Pending message.
5. Already purchased: route to overview with no duplicate charge.

## 15. Security and Compliance

1. Enforce learner ownership checks on purchase/payment routes.
2. Verify PayMongo webhook signature.
3. Prevent direct route abuse with policy checks and guarded queries.
4. Keep published/approved moderation gate intact for learner visibility.

## 16. Testing Strategy

Required test groups:
1. Catalog visibility gate (approved + published only).
2. Catalog metadata rendering (instructor, price, occupancy, full badge).
3. Free flow path.
4. Paid flow path.
5. Parent approval before payment.
6. Capacity block before checkout.
7. Purchase idempotency and duplicate attempts.
8. Webhook completion unlock logic.
9. Manual enrollment mode after payment.
10. Payment history merged view.
11. Module overview instructor card and background button.

## 17. Delivery Sequence

1. Add module purchase schema and model.
2. Add purchase orchestration service.
3. Add/extend learner module routes and controller actions.
4. Add paid purchase CTA and instructor card in module overview.
5. Upgrade catalog cards with new metadata and states.
6. Wire webhook completion for module purchase scope.
7. Extend payment history page for module transactions.
8. Add feature tests and regression checks.
