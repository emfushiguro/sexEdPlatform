# Admin Subscription Entitlements Phase 1 - Design

**Date:** 2026-03-20  
**Status:** Approved  
**Approach:** Option 1 (Thin UI refactor + event-driven entitlement grant)

## Goal

Implement an end-to-end admin-to-learner subscription flow where admin-created plan entitlements are applied immediately after successful payment, with Phase 1 focused on two learner entitlements:
- Unlimited quiz shields
- Certificate PDF download/print access

## Scope

### In Scope
- Convert admin create-plan UX from overlapping horizontal modal to fullscreen overlay modal.
- Auto-close and lock admin sidebar while fullscreen modal is open.
- Persist boolean entitlement flags in plan configuration for learner audience.
- Surface eligible plans on learner subscription page.
- Apply entitlements immediately after successful payment callback/webhook.
- Show payment plus entitlement grant state in admin payments view.
- Enforce these entitlements in learner runtime:
  - unlimited_shields
  - certificate_pdf_download_access

### Out of Scope
- Non-learner audiences and role-specific entitlement behavior.
- Numeric or quota-style entitlement controls.
- Additional entitlement types beyond the two listed above.

## Locked Decisions

1. Fullscreen overlay modal without route change.
2. Sidebar auto-closes and remains locked while modal is open.
3. Dirty-form close confirmation appears only when modal form is modified.
4. Learner-only audience for this milestone.
5. Entitlement config is boolean-only in this phase.
6. Free baseline remains 3 shields with fixed daily server reset.
7. Learners can always view certificates; download/print requires entitlement.
8. Entitlements activate immediately after payment success event.
9. Entitlement grant failure retries automatically; terminal failure is admin-visible.
10. Plan switch replaces old entitlement set (no merge).
11. Admin payments page shows payment status, learner, plan, and entitlement grant state.
12. Acceptance flow is verified locally first, then staging.

## Architecture

### 1. Admin Plan Creation Layer
- Keep existing plan management backend as foundation.
- Replace create interaction in plans index with fullscreen overlay modal.
- Modal behavior:
  - Open: close sidebar and lock it.
  - Close: restore previous sidebar state.
- Plan payload includes boolean entitlement flags:
  - unlimited_shields
  - certificate_pdf_download_access

### 2. Learner Subscription Layer
- Learner subscription page lists active learner plans.
- Plan cards display entitlement summaries.
- Learner selects plan and initiates payment.
- No entitlement is granted before payment success.

### 3. Payment and Activation Layer
- Payment success callback/webhook is the activation trigger.
- On success:
  - persist payment transaction
  - link learner and plan
  - run entitlement grant service
- Grant operation is idempotent per successful transaction.

### 4. Entitlement Application Layer
- Effective learner entitlements are replaced by selected active plan entitlements.
- Runtime enforcement:
  - unlimited_shields=true bypasses shield limit/decrement checks.
  - certificate_pdf_download_access=true allows certificate download/print.
- Free defaults remain when flags are false:
  - shield cap/refresh behavior
  - certificate view-only behavior

### 5. Admin Observability Layer
- Admin payments list/detail includes grant status lifecycle:
  - pending
  - granted
  - retrying
  - failed
- Terminal failures are visible for support follow-up.

## Data Flow

1. Admin opens create modal from subscription plans list.
2. Sidebar auto-closes; admin configures plan + boolean entitlements.
3. Plan is saved and appears in learner subscription listing.
4. Learner subscribes and completes payment.
5. Payment success event is processed and persisted.
6. Entitlement grant service applies effective learner entitlements.
7. Admin payments page shows successful payment and entitlement grant state.
8. Learner experiences unlocked features immediately.

## Error Handling

### Admin UX
- Dirty modal close prompts confirmation.
- Validation errors are shown inline and prevent partial save.

### Payment/Grant Reliability
- Duplicate callback handling is idempotent.
- Grant failures are retried with bounded backoff.
- Final failure is marked and exposed in admin payment UI.

### Enforcement Safety
- Certificate download/print checks are server-side authoritative.
- UI hide/disable is secondary to backend enforcement.

## Testing Strategy

### UI and Validation
- Fullscreen modal behavior and sidebar lock behavior.
- Dirty close confirmation behavior.
- Plan creation with boolean entitlement flags.

### End-to-End
- Admin create plan -> learner subscribe -> payment success -> payment visible in admin -> entitlement active.

### Entitlement Runtime
- Unlimited shields bypasses shield exhaustion gate.
- Free user still capped and reset by existing baseline behavior.
- Certificate view remains accessible for all, while download/print is entitlement-gated.

### Reliability
- Duplicate success callback does not double-apply entitlements.
- Forced grant failure enters retry path and terminal failure visibility path.

## Acceptance Criteria

- Admin can create learner plans via fullscreen overlay modal from plans index.
- Sidebar auto-closes and stays closed while modal is open.
- Plan entitlement booleans are persisted and visible in plan data.
- Learner can subscribe and complete payment successfully.
- Admin payments page shows learner, plan, payment status, and entitlement grant state.
- Entitlements are applied immediately after payment success.
- unlimited_shields and certificate_pdf_download_access are both enforced server-side.
- Local and staging verification pass for the full flow.
