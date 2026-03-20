# Admin Integration Efficiency Redesign Design

**Date:** 2026-03-20
**Status:** Approved
**Approach:** Option 2 (Balanced refactor)

## 1. Goal

Improve admin workflow efficiency by:
- removing non-implemented items from the admin sidebar
- aligning admin login UI/UX with the learner login branding system
- replacing subscription plan creation page flow with an in-page modal flow
- simplifying plan creation inputs for non-technical admins

## 2. Scope

### In Scope
- Sidebar cleanup (hide non-implemented items and section label)
- Admin login redesign parity with learner login visual language
- Plan creation modal on plans screen (no separate create page in primary flow)
- Simplified billing setup with decimal price, plan audience selection, and date behavior
- Simplified learner entitlement selection by category

### Out of Scope
- Implementing seminars/organizations/messages/calendar modules
- Re-architecting subscription billing core
- Instructor/connectors plan audiences (future only)

## 3. Decisions Locked

1) Sidebar behavior
- Remove from sidebar for now:
  - Calendar
  - Seminars
  - Messages
  - Communication section label
  - Organizations
- Routes may remain in codebase for future implementation.

2) Admin login
- Use same design language/theme structure as learner login.
- Keep admin-specific security-focused copy.

3) Plan creation entry
- Create plan via modal from plans management screen only.

4) Pricing and currency
- Price input uses decimal major units.
- Validation: non-negative, max 2 decimal places.
- Currency fixed to PHP for this phase.

5) Billing mode + dates
- Billing mode options:
  - Monthly
  - Annual
  - Custom Period
- Monthly/Annual are optional presets (not required enum default).
- If Monthly or Annual selected:
  - UI auto-computes and displays an informative date range preview.
  - Preview uses plan creation context date for admin clarity.
  - Actual learner subscription coverage is still calculated from learner purchase/activation date.
- If Custom Period selected:
  - Start Date and End Date required.
  - End Date must be >= Start Date.

6) Plan audience
- Add plan audience dropdown:
  - Learner (enabled)
  - Instructor (future, disabled/coming soon)
  - Connectors (future, disabled/coming soon)
- Current implementation persists learner-only behavior.

7) Entitlements model (learner plans)
- Category-based checklist UI with simple controls.
- Per entitlement controls:
  - enabled toggle
  - unlimited toggle
  - numeric limit input (shown only when not unlimited)

## 4. Entitlement Catalog (Phase 1 + Future-ready)

### Account and Profile
- Unlimited Username Changes
- Profile customization perks (future: premium avatars/themes)
- Early access to profile features (future)

### Learning Access
- Certificate PDF download
- Premium module access
- Lesson attachment downloads
- Advanced topic bundles

### Quiz and Practice
- Unlimited Shields / Unlimited Quiz Retaking
- Monthly streak savers

## 5. UX Design

### Sidebar
- Keep only functional modules to reduce dead-end navigation.

### Admin Login
- Match learner auth composition (layout, spacing, visual rhythm, interaction patterns).
- Preserve admin identity and copy tone.

### Plan Modal
- Sections:
  1. Plan Basics (name, description, audience)
  2. Billing (mode, decimal price, PHP currency)
  3. Date behavior (preset preview or custom required range)
  4. Entitlements (categorized, checkbox-driven)
- Inline validations and helper text for each section.

## 6. Architecture and Data Flow

1) Controller remains thin.
2) Form Request validates admin-friendly payload.
3) Service maps payload to existing domain records (plans, prices, entitlements).
4) Transaction wraps full create process.
5) Success closes modal and refreshes plans list.

## 7. Validation and Error Handling

- Price:
  - required
  - numeric
  - min 0
  - decimal: 0-2 places
- Billing mode:
  - one of monthly/annual/custom
- Custom period:
  - start_date required
  - end_date required
  - end_date >= start_date
- Audience:
  - learner only for now
- Entitlements:
  - normalize unchecked values safely
  - limit must be integer >= 0 when unlimited is false

Errors:
- Field-level inline errors inside modal.
- Transaction rollback on persistence failures.
- Toast error/success messaging.

## 8. Testing Strategy

### Feature Tests
- Sidebar does not render removed items.
- Admin login page renders updated branded structure and copy.
- Plan create modal submission passes for:
  - monthly preset
  - annual preset
  - custom period
- Validation failures:
  - negative decimal price
  - invalid decimal precision
  - invalid custom date range

### Regression Coverage
- Existing subscription domain behavior remains intact.
- Existing learner premium gates continue to rely on current entitlements/plan linkage.

## 9. Risks and Mitigations

- Risk: mismatch between simplified UI and existing technical schema.
  - Mitigation: mapper in service layer + feature tests.
- Risk: confusion between plan preview range and subscriber real coverage.
  - Mitigation: explicit helper copy in UI.
- Risk: future audience expansion.
  - Mitigation: audience field designed now, learner-only validation enforced.

## 10. Acceptance Criteria

- Sidebar excludes non-implemented entries listed above.
- Admin login visual system aligns with learner theme while retaining admin copy.
- Plan creation occurs via modal on plans page.
- Decimal pricing with strict non-negative validation.
- Currency is PHP.
- Billing mode supports monthly, annual, or custom period.
- Monthly/annual displays informative auto date range preview.
- Custom period requires valid start/end dates.
- Audience dropdown present, learner enabled now.
- Entitlements use categorized non-technical checklist structure.
