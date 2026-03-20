# Learner Subscription Experience Redesign - Implementation Plan

Date: 2026-03-20  
Depends on: docs/plans/2026-03-20-learner-subscription-experience-redesign-design.md

## Objective

Implement a learner subscription page redesign that is fully admin-plan-driven, visually aligned with current learner UI patterns, and optimized for conversion with transparent comparison.

## Delivery Strategy

- Keep backend payment/subscription lifecycle intact.
- Refactor data preparation in learner subscription controller.
- Replace old learner subscription rendering with hybrid cards + comparison + summary modal.
- Validate with focused feature tests and regression checks.

## Task Breakdown

### Task 1 - Prepare normalized learner subscription view model
Files:
- app/Http/Controllers/Learner/SubscriptionController.php

Actions:
- Add a plan normalization step for active admin plans.
- Produce per-plan fields: eligibility, reason, current-plan marker, price cycles, flattened feature labels.
- Keep existing pending-payment verification and summary retrieval behavior intact.

Verification:
- Controller returns required payload keys for index view.
- No changes to existing payment activation behavior.

### Task 2 - Redesign learner subscription page structure
Files:
- resources/views/subscriptions/index.blade.php

Actions:
- Introduce new page sections:
  - status/context strip
  - card-first plans grid
  - expandable comparison table
  - summary modal trigger points
  - mobile sticky CTA bar
- Preserve existing flash messaging semantics.

Verification:
- Page renders with and without active subscription.
- Current plan and ineligible states appear correctly.

### Task 3 - Card CTA state and selection behavior
Files:
- resources/views/subscriptions/index.blade.php
- resources/js/app.js (if small helper is needed)

Actions:
- Implement CTA state matrix:
  - eligible selectable
  - ineligible disabled + reason
  - current plan disabled + Current badge
- Ensure selected plan + cycle are tracked for modal handoff.

Verification:
- Disabled cards cannot submit.
- Eligible cards can open summary modal.

### Task 4 - Comparison table integration (landing-inspired pattern)
Files:
- resources/views/subscriptions/index.blade.php
- resources/css/app.css (scoped styles if needed)

Actions:
- Add expandable comparison section under cards.
- Include all visible plans plus free baseline row.
- Keep semantic table markup and mobile overflow handling.

Verification:
- Compare section collapses/expands correctly.
- Table remains readable on mobile.

### Task 5 - Summary modal and payment handoff
Files:
- resources/views/subscriptions/index.blade.php
- app/Http/Controllers/Learner/SubscriptionController.php (only if request payload needs alignment)

Actions:
- Build summary modal with selected plan/cycle/amount.
- On Continue, submit to existing subscription subscribe/upgrade pathway.
- Keep downstream payment flow unchanged.

Verification:
- Continue action reaches expected payment create/process route.
- Guardrails still prevent invalid subscribe paths.

### Task 6 - Visual polish aligned to current learner theme
Files:
- resources/views/subscriptions/index.blade.php
- resources/css/app.css (minimal, page-scoped)

Actions:
- Apply consistent spacing, hierarchy, border radius, shadows, and accent usage.
- Add subtle motion for reveal/hover/expand transitions.
- Ensure visual parity with current learner design language.

Verification:
- Desktop and mobile screenshots show coherent UI style.
- No style leakage to unrelated pages.

### Task 7 - Analytics instrumentation
Files:
- resources/views/subscriptions/index.blade.php
- resources/js/app.js (or existing analytics helper location)

Actions:
- Emit events for:
  - plans viewed
  - plan selected
  - comparison expanded
  - continue clicked
  - checkout started
- Include lightweight metadata (plan_id, cycle, viewport).

Verification:
- Events observable in existing analytics pipeline/log hooks.

### Task 8 - Tests and regression checks
Files:
- tests/Feature/Learner (new or existing relevant file)

Actions:
- Add feature coverage for:
  - active plan visibility
  - ineligible plan disabled rendering
  - current plan state
  - checkout initiation pathway
- Run targeted tests and one broader learner subscription regression pass.

Verification:
- New tests pass.
- Existing relevant subscription tests remain green.

## Suggested Execution Order

1. Task 1
2. Task 2
3. Task 3
4. Task 4
5. Task 5
6. Task 6
7. Task 7
8. Task 8

## Definition of Done

- New learner subscription UI is live on the subscription index page.
- Admin-created active plans are fully represented.
- Eligibility and current-plan UX states match approved rules.
- Modal-first continue flow reaches payment successfully.
- Mobile sticky CTA works and remains non-intrusive.
- Tests for key states are added and passing.
- No regression in existing subscription lifecycle routes.

## Rollout Notes

- Keep old upgrade page route available during initial rollout as fallback.
- If needed, gate new UI with a temporary feature flag for staged verification.
