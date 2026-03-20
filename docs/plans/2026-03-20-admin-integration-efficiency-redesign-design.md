# Admin Integration Efficiency Redesign Design

**Date:** 2026-03-20  
**Status:** Approved  
**Approach:** Option 2 (Balanced refactor)

## Goal

Improve admin workflow efficiency by removing dead-end navigation, aligning admin login with platform branding, and simplifying subscription plan creation into a modal-first flow for non-technical admins.

## Scope

### In Scope
- Remove non-implemented items from the admin sidebar.
- Align admin login UI/UX with learner login visual language.
- Replace separate plan-create page flow with plans-page modal flow.
- Simplify plan creation inputs: billing mode, dates, audience, and learner entitlements.

### Out of Scope
- Implementing calendar, seminars, messages, or organizations modules.
- Re-architecting the subscription core domain.
- Enabling non-learner audiences in production behavior.

## Locked Decisions

### 1. Sidebar Behavior
- Remove these entries from sidebar navigation:
  - Calendar
  - Seminars
  - Messages
  - Communication section label
  - Organizations
- Routes can stay in codebase for future implementation.

### 2. Admin Login
- Use the same visual system as learner login (layout rhythm, structure, and interaction style).
- Keep admin-specific, security-focused copy.

### 3. Plan Creation Entry
- Plan creation is modal-first from plans management screen.
- Dedicated create page is no longer the primary path.

### 4. Pricing and Currency
- Price input uses decimal major units.
- Validation: non-negative, max 2 decimal places.
- Currency is fixed to PHP for this phase.

### 5. Billing Mode and Date Behavior
- Billing mode options:
  - Monthly
  - Annual
  - Custom Period
- Monthly and Annual are presets, not a forced default.
- If Monthly or Annual is selected:
  - UI auto-computes and displays an informative date-range preview.
  - Preview is based on plan creation context date for admin clarity.
  - Real subscription coverage still starts from learner purchase or activation date.
- If Custom Period is selected:
  - Start Date and End Date are required.
  - End Date must be on or after Start Date.

### 6. Plan Audience
- Add dropdown options:
  - Learner (enabled)
  - Instructor (future, disabled/coming soon)
  - Connectors (future, disabled/coming soon)
- Current behavior persists learner-only enforcement.

### 7. Entitlements Model (Learner Plans)
- Use category-based checklist UI.
- Per entitlement controls:
  - Enabled toggle
  - Unlimited toggle
  - Numeric limit input (visible only when unlimited is off)

## Entitlement Catalog

### Account and Profile
- Unlimited Username Changes
- Profile customization perks (future)
- Early access to profile features (future)

### Learning Access
- Certificate PDF download
- Premium module access
- Lesson attachment downloads
- Advanced topic bundles

### Quiz and Practice
- Unlimited Shields / Unlimited Quiz Retaking
- Monthly streak savers

## UX Structure

### Sidebar
- Show only implemented modules to avoid dead-end clicks.

### Admin Login
- Match learner auth composition and spacing patterns.
- Preserve admin identity and security messaging.

### Plan Modal Sections
1. Plan Basics: name, description, audience.
2. Billing: mode, decimal price, PHP currency.
3. Date Behavior: preset preview or required custom range.
4. Entitlements: categorized checklist with simple controls.

Inline validation and helper text must be visible in-context.

## Architecture and Data Flow

1. Controller stays thin.
2. Form Request validates admin-friendly payload.
3. Service-layer mapping translates UI payload into existing plan, price, and entitlement records.
4. Transaction wraps create flow for all-or-nothing persistence.
5. On success: close modal, refresh plans list, show toast.

## Validation and Error Handling

### Validation Rules
- Price: required, numeric, min 0, max 2 decimals.
- Billing mode: monthly, annual, or custom.
- Custom period: start_date required, end_date required, end_date >= start_date.
- Audience: learner-only accepted in current phase.
- Entitlements: safely normalize unchecked values; limit must be integer >= 0 when not unlimited.

### Error Handling
- Field-level inline errors in modal.
- Transaction rollback on any persistence failure.
- Toastify success and error notifications.

## Testing Strategy

### Feature Coverage
- Sidebar no longer renders removed entries.
- Admin login renders updated branded structure and admin copy.
- Plan modal submissions pass for monthly, annual, and custom modes.
- Validation failures covered for:
  - negative price
  - invalid decimal precision
  - invalid custom date range

### Regression Coverage
- Subscription domain behavior remains intact.
- Learner premium gates continue to work with plan-entitlement linkage.

## Risks and Mitigations

- Schema mismatch between simplified UI and existing storage shape.
  - Mitigation: strict mapper + feature tests.
- Confusion between preview date range and real subscriber coverage.
  - Mitigation: explicit helper copy in UI.
- Future audience expansion complexity.
  - Mitigation: audience field introduced now with learner-only enforcement.

## Acceptance Criteria

- Sidebar excludes listed non-implemented entries.
- Admin login aligns with learner visual system while retaining admin copy.
- Plan creation occurs via plans-page modal flow.
- Decimal pricing validation enforces non-negative 2-decimal input.
- Currency is PHP.
- Billing supports monthly, annual, and custom period.
- Monthly and annual show informative auto date-range preview.
- Custom period requires valid start and end dates.
- Audience dropdown is present, with learner enabled now.
- Entitlements use categorized, non-technical checklist structure.
