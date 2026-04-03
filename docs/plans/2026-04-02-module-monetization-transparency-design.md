# Module Monetization Transparency and Revenue Ledger Design

**Date:** 2026-04-02  
**Status:** Approved  
**Approach:** Approach B (Dedicated revenue ledger + policy tables)

## 1. Objective

Implement a transparent and auditable monetization system for paid modules so Admin and Instructor users can clearly track:
1. Module sales
2. Platform commission
3. Instructor earnings
4. Payout lifecycle status

The design extends the current module purchase and payment flow without replacing it.

## 2. Locked Decisions

1. Real-time split accounting on successful module payment.
2. Commission scope: global default + per-instructor override.
3. Commission versioning: snapshot at purchase time (historical rows never mutate).
4. Rounding: standard financial rounding (half-up) to 2 decimals.
5. Tax basis: configurable (gross/net basis).
6. Refund allocation policy is configurable, but refunds are operationally disabled in this phase per Terms/Policy.
7. Instructor page includes payout lifecycle states.
8. Learner name is shown in instructor earnings table.
9. Instructor earnings table supports soft delete with confirmation.
10. Any admin can update commission policy, with mandatory audit log.
11. Admin reporting includes KPI cards + transaction table + instructor rollup.
12. No historical backfill; ledger starts at deployment point.

## 3. Scope

### In Scope
1. Commission policy management (global and per-instructor).
2. Real-time sale split computation and ledger persistence.
3. Admin monetization reporting pages.
4. Instructor earnings page with summary cards and detailed table.
5. Transparency notices in instructor pricing UI and earnings page.
6. Payout status states and transitions for visibility.
7. Audit trails for commission policy and payout status changes.
8. Soft-delete visibility behavior for instructor earnings rows.

### Out of Scope
1. Automatic bank/wallet disbursement integration.
2. Historical ledger backfill for pre-deployment module purchases.
3. Live refund workflow execution.
4. Changes to learner checkout UX beyond current payment completion hooks.

## 4. Current-State Baseline

1. `payments` and `module_purchases` already exist and complete module payment flows.
2. `ModulePurchaseService` already finalizes module purchases and enrollment unlock behavior.
3. Admin currently has payment management but no normalized commission policy control.
4. Instructor panel has module pricing input and dashboard patterns but no monetization ledger view.
5. Existing observer and webhook idempotency patterns can be reused for reliability.

Gap:
1. No dedicated ledger table for split accounting.
2. No commission policy lifecycle model.
3. No admin/instructor revenue transparency pages for module monetization.

## 5. Architecture Overview

Approach B introduces a dedicated financial layer while preserving the existing payment backbone:
1. Existing payment flow remains the transaction source.
2. A monetization service computes and writes split ledger rows at completion time.
3. Admin and Instructor reporting read from ledger tables, not raw payment metadata.
4. Policy resolution service selects effective commission settings with instructor overrides.
5. Ledger records snapshot financial rules for historical immutability.

## 6. Domain Model Additions

### 6.1 `commission_policies`

Purpose: Store commission and policy configuration with effective dating.

Proposed fields:
1. `id`
2. `scope_type` (`global`, `instructor`)
3. `scope_id` (nullable for global; instructor user id for instructor scope)
4. `commission_percent` (decimal)
5. `tax_basis` (`gross`, `net`)
6. `refund_policy` (`disabled`, `platform_absorbs`, `proportional`, etc.)
7. `is_active`
8. `effective_from`
9. `effective_to` nullable
10. `updated_by`
11. timestamps

Rules:
1. One active global policy at a time.
2. One active instructor override per instructor at a time.
3. No overlap in effective windows for same scope.

### 6.2 `module_sale_ledgers`

Purpose: Canonical per-sale split accounting record.

Proposed fields:
1. `id`
2. `payment_id` (unique)
3. `module_purchase_id`
4. `module_id`
5. `instructor_id`
6. `learner_id`
7. `learner_name_snapshot`
8. `currency`
9. `gross_amount`
10. `basis_amount`
11. `commission_percent_snapshot`
12. `commission_amount`
13. `instructor_earnings_amount`
14. `tax_basis_snapshot`
15. `refund_policy_snapshot`
16. `sale_status` (`completed`, `reversed`)
17. `payout_status` (`pending`, `payable`, `paid`, `reversed`)
18. `payout_batch_reference` nullable
19. `occurred_at`
20. timestamps

Rules:
1. Unique on `payment_id` enforces idempotency.
2. Snapshot fields never mutate after row creation except payout state and controlled reversal state.

### 6.3 `instructor_earnings_visibility`

Purpose: Track instructor-initiated soft delete/hide behavior without deleting core ledger rows.

Proposed fields:
1. `id`
2. `module_sale_ledger_id`
3. `instructor_id`
4. `deleted_at` nullable
5. `deleted_by`
6. `delete_reason` nullable
7. timestamps

Rules:
1. Admin reports always include base ledger regardless of instructor visibility state.
2. Instructor list/totals exclude hidden rows by default.

### 6.4 `commission_policy_audits`

Purpose: Immutable trail of policy edits.

Proposed fields:
1. `id`
2. `actor_admin_id`
3. `action_type`
4. `before_payload` json
5. `after_payload` json
6. `request_meta` json
7. `occurred_at`

## 7. Core Financial Computation Rules

On successful module payment completion:
1. Resolve active policy (instructor override first; global fallback).
2. Determine basis amount using tax basis.
3. Compute commission amount:
   - `commission = round_half_up(basis_amount * percent / 100, 2)`
4. Compute instructor earnings:
   - `instructor = round_half_up(gross_amount - commission, 2)`
5. Persist one ledger row transactionally.

Example:
1. Gross = 199.99
2. Commission = 10%
3. Platform commission = 20.00
4. Instructor earnings = 179.99

## 8. Data Flow and Idempotency

1. Learner initiates paid module purchase (existing flow).
2. Payment becomes `completed` (webhook primary, fallback checks secondary).
3. `ModulePurchaseService` delegates to `ModuleSaleLedgerService`.
4. Ledger service verifies payment scope is `module_purchase`.
5. Ledger service checks unique `payment_id` guard.
6. If no ledger exists, compute split and write ledger row.
7. Enrollment unlock logic continues unchanged.

Idempotency protections:
1. Unique database constraint on `module_sale_ledgers.payment_id`.
2. Service-level early return when ledger already exists.
3. Retry-safe behavior for duplicate webhook events.

## 9. Admin Feature Design

### 9.1 Commission Settings

Admin capabilities:
1. Set global commission rate.
2. Add/update instructor override rate.
3. Configure tax basis.
4. Configure refund policy value (shown as inactive in phase due to no-refund policy).
5. Activate/deactivate policy records with effective date constraints.

Governance:
1. Any admin can edit.
2. Every create/update/deactivate action writes audit log row.

### 9.2 Module Revenue Dashboard

KPIs:
1. Total module sales
2. Total gross module revenue
3. Total platform commission
4. Total instructor earnings

Tables:
1. Sale transaction table with module, learner, instructor, purchase date, gross, commission, instructor share, payout status.
2. Instructor rollup table with aggregated gross, commission, net earnings, and sales count.

Filters:
1. Date range
2. Instructor
3. Module
4. Payout status

## 10. Instructor Feature Design

### 10.1 Earnings Dashboard

Summary cards:
1. Total Modules Sold
2. Total Revenue Generated
3. Total Platform Commission
4. Net Instructor Earnings

Detailed table columns:
1. No.
2. Module Name
3. Learner Name
4. Purchase Date
5. Module Price
6. Platform Commission
7. Instructor Earnings
8. Payment Status

Actions:
1. View details
2. Delete (soft delete with confirmation)

### 10.2 Detail View

Display:
1. Split formula snapshot used for the transaction.
2. Effective commission rate and source (global vs override).
3. Payout status timeline.

## 11. Transparency Requirements

### 11.1 Module Creation/Edit Notice

When instructor sets paid price:
1. Show notice with effective commission percent.
2. Show computed earnings preview for entered price.
3. Show source label: global policy or instructor override.

### 11.2 Instructor Earnings Explanation

Display a static explanation card:
1. Current commission percent
2. Formula summary
3. Snapshot policy rule for historical records
4. Current no-refund policy note

## 12. Payout State Lifecycle

States:
1. `pending` (newly created split)
2. `payable` (ready for payout run)
3. `paid` (settled by admin action in this phase)
4. `reversed` (manual correction)

Phase behavior:
1. Automatic transition to `pending` on sale completion.
2. Admin-managed state transitions to `payable` and `paid`.
3. Reversal requires explicit admin reason and audit log.

## 13. No-Refund Policy Alignment

1. Refund workflows are disabled in this release.
2. UI labels indicate that module purchases are non-refundable per policy.
3. Refund policy settings are retained for future extensibility but marked inactive in behavior.
4. Any financial correction must use controlled reversal workflow with full audit logging.

## 14. Security and Access Control

1. Admin monetization pages are protected by `auth` + `role:admin`.
2. Instructor earnings pages are scoped to authenticated instructor ownership.
3. Service-level ownership checks prevent cross-instructor ledger access.
4. Input validation enforces safe policy ranges and valid effective windows.
5. Audit payloads redact sensitive request fields while preserving traceability.

## 15. Error Handling and Observability

1. Missing active policy blocks ledger creation and logs actionable error.
2. Duplicate completion event returns idempotent success.
3. Invalid policy date overlaps are rejected with user-facing validation errors.
4. Failed ledger write aborts transaction and prevents partial financial state.
5. Structured logs include payment id, module purchase id, policy id, and actor id.

## 16. Testing Strategy

Required test groups:
1. Schema and relation tests for new monetization tables.
2. Policy resolution precedence tests.
3. Rounding and tax-basis computation tests.
4. Payment completion to ledger creation integration tests.
5. Duplicate webhook idempotency tests.
6. Admin policy management authorization and audit tests.
7. Admin revenue page data contract tests.
8. Instructor earnings page data, filters, and soft-delete behavior tests.
9. Transparency notice rendering tests in instructor module pricing modal/page.
10. Regression tests confirming existing module purchase + enrollment flow stability.

## 17. Rollout Plan

1. Deploy additive schema migrations.
2. Seed an initial active global commission policy.
3. Release ledger generation behind module-purchase completion events.
4. Release admin commission settings and admin revenue dashboard.
5. Release instructor earnings dashboard and transparency notices.
6. Communicate reporting start date because historical rows are not backfilled.

## 18. Reporting Start-Date Rule (No Backfill)

1. Financial transparency reporting begins at deployment date.
2. Transactions completed before deployment are excluded from monetization ledger totals.
3. Admin and instructor screens display a clear note:
   - "Revenue analytics include sales from <release date> onward."
