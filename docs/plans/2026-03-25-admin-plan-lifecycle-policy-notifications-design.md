# Admin Plan Lifecycle, Policy Cleanup, and Notification Unification - Design

**Date:** 2026-03-25  
**Status:** Approved  
**Approach:** Option A (Unified lifecycle + shared wizard + policy enforcement)

## Goal

Deliver a consistent and safer admin subscription operations flow by:
- Replacing overwhelming full-screen create/edit plan forms with a modal wizard UI.
- Introducing lifecycle-safe plan operations (activate, deactivate, archive, restore).
- Removing refund and legacy subscriber recovery operations in line with platform policy.
- Standardizing admin flash feedback to Toastify.
- Adding admin top-right notification center for subscription and payment operational awareness.

## Scope

### In Scope
- Admin plans create/edit via shared modal wizard (same interaction style as instructor modal pattern).
- Wizard flow with 3 steps:
	- Step 1: plan name, description, plan purpose (learner, instructor, connectors).
	- Step 2: billing mode (monthly, annually, custom period) with billing preview.
	- Step 3: entitlement cards with checklist toggles.
- Plans table updates:
	- remove trial column.
	- replace delete action with archive action.
	- add activate/deactivate confirmation modal with impact data (subscriber counts).
	- add archive confirmation modal.
- Archive management:
	- archived plans listing page.
	- restore action with confirmation.
- Admin no-refund policy enforcement:
	- remove refund processing actions from payment table/detail flows.
	- remove internal note action tied to payment refund operations.
- Subscriber operations cleanup:
	- remove grace period extension.
	- remove retry/schedule-cancel/reactivation flows from admin surface and route paths.
- Notification and feedback unification:
	- replace old admin popup messaging with Toastify.
	- add admin top-right notification bell/dropdown (same UX family as instructor side).
- Policy text updates:
	- explicit no-refund subscriptions clause.
	- explicit deactivation behavior: existing subscribers keep entitlement until renewal/expiry.

### Out of Scope
- Real-time websocket push for admin notifications (polling/page-load fetch is enough for this phase).
- New learner-facing billing architecture changes beyond policy text updates.
- Deep analytics charting for notification trends.

## Locked Decisions

1. Shared wizard component powers both create and edit plan operations.
2. Lifecycle statuses are Active, Inactive, Archived.
3. Restored plans return in Inactive state by default for safer reactivation control.
4. Deactivated plans block new purchases but do not immediately revoke existing active subscriber entitlements.
5. Existing subscribers retain entitlements until renewal/expiry; this rule is written into terms.
6. Delete operation is replaced with archive (soft-retention pattern).
7. Refund processing is disabled in admin UI and backend admin operation paths.
8. Subscriber admin operations for grace, retry, schedule-cancel, and reactivation are removed from active flow.
9. Admin flash and action feedback is standardized to Toastify (success/error/info).
10. Admin notification center is added in top-right navbar with unread count, mark-read, and mark-all-read actions.

## Architecture

### 1. Plan Lifecycle Layer
- Continue using `SubscriptionPlan` as lifecycle authority with explicit transition methods in admin handling.
- Keep `is_active` for active/inactive and add archive state with retention-safe behavior.
- Archive behavior removes plans from default active operations and blocks new assignment.

### 2. Shared Wizard Layer
- A single wizard shell handles create and edit state.
- Wizard steps are validated incrementally in UI and fully validated server-side on submit.
- Step 2 billing preview computes and displays effective billing summary before final submission.
- Step 3 renders grouped entitlement cards with checklist toggles and selected-count summary.

### 3. Admin Policy Enforcement Layer
- Payment management removes refund and internal-note operation controls tied to refund process.
- Subscriber management removes grace/retry/schedule-cancel/reactivation actions and route endpoints.
- Policy-consistent behavior is enforced server-side, not only hidden in UI.

### 4. Admin Feedback and Notification Layer
- All touched admin actions use Toastify for success/error/info.
- Top-right admin navbar gains notification bell with unread badge and dropdown list.
- Notification payloads include title, message, type, and deep-link URL for quick follow-up.

## UI and Interaction Design

### Plans Wizard (Create/Edit)
- **Step 1 - Identity:** Name, description, plan audience/purpose.
- **Step 2 - Billing Configuration:** billing mode selector + custom period inputs + live billing preview card.
- **Step 3 - Entitlements:** grouped mini-cards with checkboxes; each card has helper text and per-group selected count.
- Navigation:
	- next/back controls.
	- submit only on final step.
	- exit warning when form is dirty.

### Plans Table
- Remove trial column.
- Action set:
	- view.
	- edit (opens wizard mode for existing plan).
	- activate/deactivate (with impact confirmation modal).
	- archive (with confirmation modal).
- Confirmation modals show subscriber impact snapshot before executing action.

### Archived Plans Page
- Dedicated admin listing of archived plans.
- Columns: plan name, billing mode, archived date, last updated.
- Actions: view details, restore.

### Admin Notification Navbar
- Add bell icon and unread badge in admin layout header.
- Dropdown shows recent notifications and quick actions:
	- mark single as read.
	- mark all as read.
	- view all (optional route in this phase if needed).

## Data Flow

1. Admin opens create/edit wizard modal.
2. Step data is collected and validated per step.
3. Final submit reaches server-side request validation and normalization.
4. Plan create/update persists identity, billing, and entitlements.
5. Plan lifecycle actions (activate/deactivate/archive/restore) request impact snapshot and then apply transition.
6. Transition emits admin activity log entries and admin notifications.
7. Payment/subscriber admin pages honor policy removals by exposing only supported actions.
8. Toastify communicates operation results immediately; notification center stores durable operational events.

## Error Handling

### Wizard Validation
- Client-side checks for completeness and flow control.
- Server-side Form Request validation remains authoritative.
- Unknown entitlement keys are rejected.

### Lifecycle Actions
- If state changes between confirmation open and submit, action fails with refresh-required message.
- Activate on archived plan is blocked unless restored first.
- Archive action can be blocked or warned when active subscriber count is high, based on policy rules.

### Policy Actions
- Disabled operations return explicit error if old endpoints are hit directly.
- UI surfaces policy rationale (no refund / lifecycle-only management).

## Testing Strategy

### Feature Tests
- Update `tests/Feature/Admin/PlanManagementFlowTest.php` for wizard create/edit and table action changes.
- Update `tests/Feature/Admin/AdminTableUxTest.php` for removed columns/actions and Toastify wiring markers.
- Add tests for archive listing and restore flow.
- Add tests for activation/deactivation confirmation impact data behavior.

### Policy Enforcement Tests
- Payment admin tests ensure refund/internal-note operation routes are removed or rejected.
- Subscriber admin tests ensure grace/retry/schedule-cancel/reactivation routes are removed or rejected.

### Notification Tests
- Verify admin notifications are created for:
	- new subscriber.
	- successful payment.
	- failed payment.
	- plan lifecycle changes.
- Verify mark-read and mark-all-read behavior.

### Terms/Policy Tests
- Verify terms page includes:
	- no-refund subscription policy.
	- deactivated-plan entitlement-until-renewal/expiry policy.

## Acceptance Criteria

- Admin plan create/edit uses modal wizard with 3 steps and billing preview.
- Entitlements step uses grouped card checklist UI.
- Plans table no longer shows trial column.
- Plan delete action is replaced with archive action and confirmation flow.
- Archived plans page exists with restore capability.
- Activate/deactivate confirmation modal displays subscriber impact counts.
- Payment admin no longer exposes refund/internal-note processing actions.
- Subscriber admin no longer exposes grace/retry/schedule-cancel/reactivation actions.
- Admin uses Toastify instead of legacy popup style in touched flows.
- Admin header has notification bell/dropdown with unread management.
- Terms include no-refund clause and entitlement-until-renewal/expiry clause.
