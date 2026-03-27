# Learner Subscription Experience Redesign - Design Document

Date: 2026-03-20  
Status: Approved  
Scope: Learner-facing subscription page and checkout initiation flow

## 1. Executive Summary

Redesign the learner subscription experience so it is fully driven by admin-created plans, visually aligned with the current learner/TailAdmin-inspired UI language, and optimized for conversion without sacrificing plan transparency.

The new experience uses a hybrid structure:
- premium card-first plan browsing for quick decision making
- expandable full comparison table for detailed review
- modal-first checkout handoff (Continue -> Proceed to payment)

## 2. Goals and Non-Goals

### Goals
- Show all active, published admin plans to learners.
- Keep ineligible plans visible but disabled, with one-line reason.
- Support multi-cycle pricing presentation (monthly/quarterly/yearly when configured).
- Keep current plan visible with a Current badge and disabled subscribe action.
- Preserve existing payment processing routes while improving pre-checkout UX.
- Improve visual quality to match current platform style and existing design direction.
- Deliver a mobile-first stacked experience with sticky bottom CTA.
- Track key funnel analytics events.

### Non-Goals
- No billing engine rewrite.
- No PayMongo backend contract changes.
- No entitlement model redesign.
- No changes to admin plan management workflows.

## 3. Source of Truth and Data Contract

### Data source
- Admin-managed plans remain canonical source.
- Learner views consume active plans ordered by admin-defined order.

### Normalized plan payload for rendering
Each visible plan should be prepared for UI as:
- id, slug, name, description
- is_free, is_current_plan, is_eligible
- ineligible_reason (nullable string)
- prices: active price records sorted by default then duration
- feature_labels: flattened, user-facing features from config mapping
- display_badges: current, recommended, popular (derived)

### Visibility and eligibility rules
- Visibility: show all active plans.
- Eligibility: disable only ineligible plans; do not hide.
- Current plan: show as selected context with disabled action.

## 4. Information Architecture

### Section A: Context strip
- Displays current subscription status (active/pending/grace/cancelled).
- Shows quick status guidance and pending-payment hint when applicable.

### Section B: Plan cards grid
- Card-first layout for scanning and conversion.
- All active plans rendered.
- Disabled cards show ineligible reason.
- Current plan card displays Current badge and disabled CTA.

### Section C: Expandable comparison
- Collapsible feature matrix below cards.
- Inspired by landing pricing section pattern.
- Includes all visible plans and baseline free plan row.

### Section D: Action area
- Desktop: CTA in each card + optional selected summary panel.
- Mobile: sticky bottom selection bar and Continue button.

## 5. Pricing and Plan Rules

- Present plan prices by available configured cycles, not hardcoded labels.
- If multiple active prices exist for a plan, render each clearly with duration label.
- Free plan appears as baseline comparison row.
- Current plan cannot be re-subscribed from this page.

## 6. Interaction and Checkout Flow

### Primary flow
1. Learner selects eligible plan and price cycle.
2. Summary modal opens with plan name, cycle, amount, and brief terms.
3. Learner clicks Continue.
4. System proceeds to payment create/process route.

### Disabled states
- Ineligible plan CTA disabled with reason text.
- Current plan CTA disabled with Current indicator.
- Active subscriber switching follows existing policy guardrails.

## 7. Visual Direction

- Align to existing learner theme with TailAdmin-quality polish.
- Strong hierarchy: clear typography, elevated cards, purposeful spacing.
- Use existing platform token language (border radii, shadows, neutrals, accents).
- Keep motion intentional: reveal transitions, card emphasis, expand/collapse feedback.
- Avoid introducing a disconnected aesthetic or conflicting color system.

## 8. Responsive Behavior

### Desktop/tablet
- Multi-column cards with comparison section below.

### Mobile
- Stacked cards.
- Sticky bottom CTA appears after valid selection.
- Comparison section uses compact horizontal scroll where needed.

## 9. Empty, Loading, and Error States

### Empty active plans
- Guided empty state with explanatory copy and optional notify intent.

### Loading
- Lightweight skeleton placeholders for cards and comparison rows.

### Errors
- Clear inline error copy for payment handoff failures with retry action.

## 10. Analytics Events

Track events:
- subscription_plans_viewed
- subscription_plan_selected
- subscription_compare_expanded
- subscription_continue_clicked
- subscription_checkout_started

Optional metadata:
- plan_id, cycle, eligibility_state, viewport_type

## 11. Accessibility

- Keyboard-navigable selection and modal controls.
- Proper aria labels for toggles/collapse and disabled explanations.
- Visible focus states meeting contrast expectations.
- Semantic table markup for comparison matrix.

## 12. Risks and Mitigations

- Risk: visual regression against learner layout.
  - Mitigation: isolate styles within subscription page scope.
- Risk: mismatch between plan prices and selected checkout payload.
  - Mitigation: server-side validation of selected plan/price before checkout handoff.
- Risk: dense comparison on small screens.
  - Mitigation: progressive disclosure and compact table strategy.

## 13. Acceptance Criteria

- Learner page renders all active admin-created plans.
- Ineligible plans are visible, disabled, and explained.
- Current plan appears as Current and cannot be re-selected.
- Multi-cycle pricing displays correctly when configured.
- Comparison section is expandable and usable on mobile.
- Continue opens summary modal and then proceeds to payment flow.
- Visual style is aligned with current learner theme and design system.
- Analytics events fire for key funnel interactions.
