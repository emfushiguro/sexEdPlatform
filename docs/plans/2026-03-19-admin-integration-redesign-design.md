# Admin Integration Redesign Design

**Date:** 2026-03-19
**Status:** Approved (with locked decisions)
**Scope:** Full end-to-end admin integration redesign for subscriptions, plans, payments, users, and learner-side subscription parity.

## 1. Objective

Redesign the admin integration into a platform-grade subscription and operations system that is reliable, auditable, and aligned with the existing Concious Connections UI language.

This design covers:
- Plan and subscription domain normalization
- Admin dashboard and operations UX
- Users, subscribers, plans, and payments table standards
- Learner-side subscription consistency
- Admin login page redesign aligned to learner auth theme

## 2. Locked Decisions

- Rollout strategy: **Phased by domain**
- Duration model: **Preset + custom duration support**
- Pricing model: **One plan with multiple duration prices**
- Features model: **Feature catalog + pivot entitlements**
- Limit types: **Boolean + quota + unlimited**
- User creation: **Single role-aware create-user flow**
- Immediate user actions: **create/edit/deactivate/reactivate/reset password link**
- Lifecycle states: **active, scheduled_cancel, grace_period, expired**
- Failed payment: **grace period + retries + reminders**
- Learner UX priority: **all key areas, delivered in phases**
- Dashboard focus: **operational risk first (hybrid command center)**
- Table interaction: **balanced table + side drawer details**
- Audit strictness: **full audit trail**
- Permission model for v1: **single admin role (with future-ready permission hooks)**
- Success criteria: **operational + financial + UX outcomes with measurable targets**
- Plan lineup: **Free Learner + Premium Learner only (no Family Premium in this release)**
- Feature naming: **unlimited_shields** is the canonical feature (replaces legacy quiz attempts terminology)
- UI component source for admin redesign: **reuse/adapt from !tail-admin**

## 3. Architecture

Use a normalized subscription domain that serves both admin and learner contexts from the same source of truth.

Core entities:
- Plan
- PlanPrice (duration-specific pricing)
- FeatureCatalog
- PlanFeatureEntitlement
- Subscription
- Payment/Invoice links
- AdminActivityLog

Business logic remains service-centric in `app/Services`, with thin controllers and form requests for validation.

## 4. Data Model and Schema

### 4.1 Plans
- `plans`: id, code, name, description, is_active, display_order, created_by, updated_by, timestamps

### 4.2 Prices and Durations
- `plan_prices`: id, plan_id, duration_mode (preset|custom), duration_unit (day|week|month|year), duration_count, duration_label, amount_minor, currency, compare_at_minor nullable, is_default, is_active, timestamps
- Rule: a plan may have multiple active prices; only one default active price.

### 4.3 Feature Catalog and Entitlements
- `feature_catalog`: id, key, name, description, value_type (boolean|quota), unit_label nullable, category, is_active
- `plan_feature_entitlements`: id, plan_id, feature_id, is_enabled, quota_value nullable, is_unlimited, timestamps

Canonical launch features:
- `unlimited_shields` (boolean)
- `certificate_pdf_download` (boolean)
- `premium_attachment_download` (boolean)
- `monthly_streak_savers_quota` (quota)
- `priority_support` (boolean)

### 4.4 Subscriptions
- `subscriptions`: id, user_id, plan_id, plan_price_id, status, starts_at, ends_at, grace_ends_at nullable, cancel_at nullable, canceled_at nullable, next_billing_at nullable, source_provider, source_reference, timestamps
- Status enum: `active`, `scheduled_cancel`, `grace_period`, `expired`

### 4.5 Audit
- `admin_activity_logs`: id, admin_user_id, action, entity_type, entity_id, before_json nullable, after_json nullable, meta_json nullable, ip_address, user_agent, created_at

## 5. Subscription Lifecycle and Billing Rules

### 5.1 Purchase
- Payment success activates subscription and sets billing boundaries.

### 5.2 Renewal
- Successful renewal extends `ends_at` and updates `next_billing_at`.

### 5.3 Cancel at Period End
- Sets `cancel_at`, status `scheduled_cancel`; benefits remain until term end.

### 5.4 Failed Payment
- Move to `grace_period`
- Execute configured retries
- Send reminders at defined cadence
- Move to `expired` after grace ends if unrecovered

### 5.5 Recovery
- Successful payment during grace restores `active`.

## 6. Entitlements Resolution

Entitlement evaluation order:
1. Active subscription and selected plan price
2. Plan feature entitlements
3. Subscription state gates

Outputs:
- Boolean allow/deny
- Quota numeric limit
- Unlimited override

Provide centralized service methods for feature checks to prevent duplicated logic in controllers/views.

## 7. Admin IA and UI/UX Strategy

### 7.1 Information Architecture
- Dashboard
- Subscriptions
- Plans
- Subscribers
- Payments
- Users
- Activity Logs

### 7.2 UI Implementation Direction
- Reuse/adapt component patterns from `!tail-admin/resources/views/components` and layout structures from `!tail-admin/resources/views/layouts`
- Preserve Concious Connections branding/tokens and platform visual identity
- Keep Blade + Alpine + Tailwind approach

### 7.3 Shared Patterns
- Stat cards with trend indicators
- Filter bars with saved views
- Data table + side drawer details
- Explicit action buttons with confirmation guards
- Consistent empty/loading/error states

## 8. Admin Main Dashboard Specification

Chosen model: **Hybrid Command Center** (most appropriate)

Lane A: Immediate risk (action now)
- Failed renewals today
- Grace period expiring in 24h/72h
- Webhook mismatches
- Refunds pending beyond SLA

Lane B: Revenue leakage (action this week)
- Recovery rate from failed renewals
- Churn from non-recovered grace accounts
- Plans with highest downgrade/exit rate

Lane C: Growth and platform health (optimize next)
- New premium conversions
- Active learners by age bracket
- Plan distribution and trend

Every priority card includes a direct CTA (retry, reconcile, review subscriber, extend grace, etc.).

## 9. Table Standards

### 9.1 Users Table
Columns:
- name, email, role, status, age_bracket (learner), created_at, last_login, subscription_status
Actions:
- view, edit, deactivate/reactivate, send reset password link

### 9.2 Subscribers Table
Columns:
- user, plan, duration, status, next_billing_at, grace_ends_at, last_payment_status
Actions:
- timeline, retry payment, extend grace, schedule cancel, reactivate

### 9.3 Plans Table
Columns:
- plan_name, active_prices_count, default_price, active_subscribers, status, updated_at
Actions:
- edit, duplicate, archive/unarchive, reorder

### 9.4 Payments Table
Columns:
- payment_ref, user, subscription_ref, amount, provider, status, paid_at, failure_reason
Actions:
- detail, reconcile, refund, add internal note

## 10. Admin Operational Workflows

- Plan authoring: create shell -> add prices/durations -> configure entitlements -> publish
- Subscriber operations: inspect timeline -> execute guarded action -> log activity
- Payment operations: triage failed/refund/reconciliation queues
- User management: single role-aware create form and safe lifecycle actions

## 11. Learner-Side Subscription Experience

- Plan comparison with monthly/annual options
- Status clarity: active, scheduled cancel, grace period, expired
- Self-service actions: cancel/renew/reactivate/refund request
- Feature lock messaging tied to entitlement checks
- Admin and learner data views must always match

## 12. Admin Login Page Redesign (New Requirement)

Goal: redesign admin login UI/UX to align with current theme and learner auth quality.

References:
- Learner auth style: `resources/views/auth/learner-login.blade.php`
- Current admin auth target: `resources/views/auth/admin-login.blade.php`

Design direction:
- Keep shared auth skeleton patterns for consistency
- Reuse TailAdmin-inspired structural primitives (card shell, spacing rhythm, typography hierarchy, feedback components)
- Apply Concious Connections branding and role-specific messaging for Admin
- Ensure mobile-first responsive behavior and proper accessibility states

Non-goal:
- No auth flow logic changes in this design scope (UI/UX refinement only)

## 13. Permissions and Security

v1 permission posture:
- Single admin role, but enforce explicit permission checks to stay migration-ready
- Sensitive actions require confirmation and reason inputs
- Refund/manual subscription overrides must be logged and reviewable

## 14. Audit and Traceability

Capture all admin mutations for:
- users
- plans/prices/features
- subscriptions
- payments/refunds

Store before/after payloads and operator metadata for operational investigations.

## 15. Migration and Rollout

Phase 1:
- Introduce normalized tables + compatibility adapters

Phase 2:
- Backfill existing plans and subscriber states into normalized schema

Phase 3:
- Switch admin writes to new services and validate with shadow reads

Phase 4:
- Switch learner subscription rendering/entitlement checks fully to normalized domain

Phase 5:
- Remove deprecated compatibility paths after stability window

## 16. Testing and Verification

- Unit tests: pricing, durations, entitlement resolution, state transitions
- Feature tests: admin workflows and learner subscription UX parity
- Permission tests: guarded financial/user actions
- Regression tests: shield-related behavior remains correct with `unlimited_shields`

## 17. Success Metrics

Operational:
- faster resolution for failed renewals/refund queues

Financial:
- improved recovery rate, reduced involuntary churn

UX:
- fewer admin navigation hops and faster task completion

Reliability:
- zero entitlement mismatch between admin and learner views

## 18. Risks and Mitigations

Risks:
- data migration mismatch
- provider state mismatch
- entitlement regressions

Mitigations:
- phased rollout
- compatibility layer
- shadow validation + targeted test matrix

## 19. Out of Scope

- Family Premium plan
- usage-based metered billing
- multi-admin role matrix (beyond prepared hooks)
- full auth backend changes for admin login
