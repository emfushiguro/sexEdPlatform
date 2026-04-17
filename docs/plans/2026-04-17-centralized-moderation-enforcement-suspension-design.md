# Centralized Moderation, Violation, Enforcement, and Suspension System Design

Date: 2026-04-17
Status: Approved
Approach: Dual-write transition with central moderation orchestrator

## 1. Purpose and Goals

Create a centralized moderation pipeline that unifies fragmented moderation flows across:

1. Module publishing review
2. Instructor application review
3. Learner to instructor reporting
4. Learner to module reporting
5. Chat message reporting
6. Admin governance actions

Primary goals:

1. Enforce one lifecycle from report to resolution
2. Separate reports from confirmed violations
3. Apply proportional, policy-safe enforcement
4. Support manual plus automatic hybrid enforcement
5. Preserve ownership boundaries (admins do not directly mutate instructor-authored content through moderation)
6. Provide full auditability and explainability
7. Standardize admin moderation UI to Payment Management table and palette baseline

## 2. Approved Decisions Matrix

Locked choices from clarification:

1. Rollout strategy: Dual-write transitional
2. Backfill scope: All historical moderation artifacts
3. Moderation case status model: reported, triaged, investigating, confirmed_violation, no_violation, resolved
4. Severity framework: minor, moderate, major, critical
5. Violation points model: severity base plus type multiplier
6. Violation expiration model: expiration by severity tier
7. Escalation strictness: allow skips only for major and critical
8. Permanent suspension authority: manual admin only
9. Permanent suspension appeal eligibility: admin-controlled per case
10. Appeal submission window: 14 days
11. Appeal thread visibility: admin plus suspended user plus parent (if learner and linked)
12. Automation conflict handling: highest-severity resulting action wins
13. Automation execution mode: async queue with idempotency
14. Rule governance: versioned rules with activate and rollback
15. Suspension middleware scope: block all authenticated routes except allowlist
16. Admin moderation dashboard depth: stat cards plus filterable table plus pagination with full Payment-style UI parity
17. Notification channels: in-app plus email
18. Dual-write exit criteria: tests pass plus data parity checks plus admin signoff

## 3. Canonical Moderation Lifecycle

All moderation processes must follow this normalized lifecycle:

1. Report or review input received
2. Moderation case created
3. Case investigation and decision
4. Confirmed violation (or explicit no-violation)
5. Enforcement action issued if violation confirmed
6. Suspension record created when enforcement requires suspension
7. Appeal workflow handled through appeal domain and thread
8. Resolution tracking and closure

Hard rule:

1. Report does not equal violation
2. Only confirmed cases create violations
3. Only confirmed violations can trigger automation

## 4. Target Domain Architecture

### 4.1 Central Orchestrator Pattern

Introduce a central service layer that coordinates case, violation, enforcement, suspension, and appeal behaviors.

Proposed service boundary:

1. ModerationCaseIntakeService (source normalization)
2. ModerationCaseWorkflowService (state transitions)
3. ViolationService (issuance and expiry)
4. EnforcementActionService (manual and automated actions)
5. SuspensionService (status and lifecycle)
6. SuspensionAppealService (submission, review, communication)
7. ModerationAutomationService (rules engine and async execution)

### 4.2 Source Adapters

Each moderation source maps into a unified intake DTO:

1. module_review
2. chat_report
3. learner_report
4. instructor_application_review
5. admin_manual_review
6. system_flagged_event

Adapters preserve source-specific metadata while producing normalized case attributes.

### 4.3 Ownership and Governance Boundary

Moderation actions can:

1. Review and classify policy violations
2. Issue violations
3. Apply enforcement and suspensions
4. Handle appeals

Moderation actions cannot:

1. Directly modify instructor-owned content bodies through moderation flow

## 5. Data Model

## 5.1 moderation_cases

Fields:

1. id
2. case_reference_code (unique, format CASE-MOD-YYYY-NNNNNN)
3. reporter_id nullable
4. reported_user_id required
5. content_type
6. content_id
7. case_source
8. status
9. severity_level
10. decision
11. reviewed_by_admin_id nullable
12. reviewed_at nullable
13. notes nullable
14. metadata JSON
15. timestamps

Indexes:

1. unique case_reference_code
2. case_source, status
3. reported_user_id, created_at
4. content_type, content_id

## 5.2 violations

Fields:

1. id
2. user_id
3. moderation_case_id
4. violation_type
5. severity_level
6. violation_points
7. trigger_source
8. expires_at nullable
9. issued_by_admin_id nullable
10. timestamps

Rules:

1. Must reference a case with decision confirmed_violation
2. Expiration is supported and required by fairness policy except explicitly exempted policy cases

Indexes:

1. user_id, created_at
2. user_id, expires_at
3. violation_type, severity_level

## 5.3 enforcement_actions

Fields:

1. id
2. user_id
3. moderation_case_id nullable
4. action_type
5. severity_level
6. trigger_type (manual, automatic, hybrid)
7. starts_at
8. ends_at nullable
9. status
10. issued_by_admin_id nullable
11. notes nullable
12. timestamps

Action set:

1. warning
2. chat_restriction
3. module_publish_restriction
4. temporary_suspension
5. extended_suspension
6. permanent_suspension

## 5.4 automation_rule_logs

Fields:

1. id
2. rule_id
3. target_user_id
4. matched_violation_ids JSON
5. condition_snapshot JSON
6. action_executed
7. enforcement_action_id nullable
8. status (executed, skipped, failed)
9. executed_at
10. error_message nullable
11. timestamps

## 5.5 user_suspensions

Fields:

1. id
2. user_id
3. moderation_case_id nullable
4. enforcement_action_id
5. reason_category
6. severity_level
7. trigger_type
8. starts_at
9. ends_at nullable
10. status (active, expired, revoked, appeal_pending)
11. appeal_status
12. issued_by_admin_id nullable
13. timestamps

Rules:

1. Permanent suspension always has ends_at null
2. Permanent suspension creation is manual admin-only

## 5.6 suspension_appeals

Fields:

1. id
2. suspension_id
3. submitted_by_user_id
4. appeal_text
5. attachment_path nullable
6. status (pending_review, approved, rejected, clarification_requested)
7. reviewed_by_admin_id nullable
8. decision_notes nullable
9. submitted_at
10. reviewed_at nullable
11. timestamps

Rules:

1. Temporary and extended suspensions are appealable by default
2. Permanent suspension appeal eligibility is admin-controlled per suspension
3. Default status is pending_review

## 5.7 appeal_thread_messages

Fields:

1. id
2. appeal_id
3. sender_role (admin, learner, instructor, parent)
4. sender_user_id
5. message
6. attachment_path nullable
7. timestamps

Rule:

1. Appeal communication is thread-based only; no chat bypass

## 5.8 moderation_automation_rules and versions

Add configurable and versioned rules to avoid hardcoded automation behavior.

moderation_automation_rules fields:

1. id
2. name
3. is_active
4. priority
5. threshold_count
6. window_days
7. violation_type_filters JSON
8. severity_filters JSON
9. resulting_action_type
10. trigger_type
11. requires_manual_confirmation
12. created_by
13. updated_by
14. timestamps

moderation_automation_rule_versions fields:

1. id
2. rule_id
3. snapshot_payload JSON
4. change_summary
5. changed_by
6. timestamps

## 6. Automation Model and Safety

## 6.1 Trigger Source Safety

1. Automation reads only confirmed, non-expired violations
2. Raw reports never trigger actions

## 6.2 Default Presets

Initial defaults (modifiable in admin settings):

1. 3 confirmed violations within 7 days -> temporary suspension
2. 2 confirmed chat abuse violations -> chat restriction
3. 2 module policy violations -> module publish restriction

## 6.3 Validation Constraints

Before saving rules:

1. threshold_count must be greater than 0
2. window_days must be greater than 0
3. resulting action must be in allowed action set
4. conflicting conditions are rejected
5. permanent suspension cannot be configured as automatic output

## 6.4 Conflict Resolution

If multiple rules match, execute highest-severity resulting action.

## 6.5 Explainability

Every automation execution writes automation_rule_logs with matched evidence and action outcome.

## 7. Escalation and Enforcement Policy

Enforcement ladder:

1. warning
2. restriction
3. temporary suspension
4. extended suspension
5. permanent suspension

Skip policy:

1. No skipping for minor and moderate by default
2. Skips allowed for major and critical with mandatory rationale

Permanent suspension policy:

1. Manual admin only
2. Never triggered automatically

## 8. Suspension Enforcement and Middleware

Middleware: CheckUserSuspensionStatus

Behavior:

1. Applied to authenticated routes
2. Redirect active suspended users to suspension status page
3. Allowlist:
- suspension status pages
- appeal submission endpoints
- appeal thread endpoints
- logout and essential account endpoints

This provides consistent suspension enforcement across learner, instructor, parent, and shared authenticated surfaces.

## 9. User Transparency Surfaces

## 9.1 Suspension Status Page (/suspension-status)

Displays:

1. Reason category
2. Severity
3. Duration
4. Case reference
5. Appeal eligibility
6. Appeal status

## 9.2 Appeal Submission

1. Submit appeal text and optional evidence attachment
2. Notify admins (in-app plus email)
3. Track status from pending_review onward

## 9.3 Appeal Review Interface

Admin can:

1. Review suspension and violation history
2. View attachment evidence
3. Reply via appeal thread
4. Approve, reject, or request clarification

## 10. Admin Moderation UI and UX Standardization

New admin destination:

1. Admin -> Moderation -> Suspensions

Dashboard requirements:

1. Stat cards for active, pending appeals, expired, revoked
2. Filterable table with user, role, severity, duration, trigger type, status, appeal status
3. Search and pagination

Design constraints:

1. Follow Payment Management layout and components
2. Reuse admin table partial patterns for filter bar, row actions, pagination footer
3. Keep spacing, typography, icon styling, and color semantics consistent with existing Payment baseline
4. Do not introduce a new visual pattern family

## 11. Dual-Write Migration Strategy

## 11.1 Phase A: Dual-Write Activation

1. Existing moderation flows continue writing legacy tables
2. Same flows also write centralized moderation domain
3. Begin historical backfill into centralized tables

## 11.2 Phase B: Parity Validation

1. Reconciliation reports compare legacy and centralized outcomes
2. Run targeted regression suite across all moderation sources
3. Validate admin operational parity

## 11.3 Phase C: Cutover

1. Switch admin moderation read surfaces to centralized tables
2. Disable legacy writes after signoff
3. Keep legacy data read-only for retention window

Exit criteria:

1. Tests pass
2. Data parity checks pass
3. Admin signoff complete

## 12. Error Handling and Auditability

1. Critical moderation transitions are transaction-safe
2. Async automation has retries and dead-letter handling
3. Every transition stores actor, timestamp, rationale, and snapshot context
4. Rule executions store matched conditions and resulting action
5. Appeal decisions store reviewer and decision notes

## 13. Testing and Verification Strategy

Test layers:

1. Unit tests for workflow transitions, severity/points, expiry, escalation, and rule validator
2. Feature tests for source adapters, dual-write behavior, middleware redirects, suspension status, and appeals
3. Queue tests for automation idempotency and conflict resolution
4. UI tests for admin moderation pages and Payment-style parity
5. Backfill tests for historical migration correctness

## 14. Implementation Order (Critical)

The build sequence is fixed:

1. moderation_cases
2. violations
3. enforcement_actions
4. automation_rule_logs
5. user_suspensions
6. suspension_appeals
7. appeal_thread_messages
8. middleware
9. dashboards
10. notifications

Additional note:

1. moderation_automation_rules and versioning are introduced alongside step 4 to satisfy dynamic configuration and safety controls.

## 15. Scope Boundaries

In scope:

1. Central moderation lifecycle and data model
2. Hybrid enforcement and suspension domain
3. Appeal workflow and thread communication
4. Dynamic automation rule configuration and audit logging
5. Middleware enforcement and user transparency pages
6. Admin dashboard and UI standardization
7. Dual-write migration and parity cutover

Out of scope for this phase:

1. Non-moderation chat redesign
2. Full analytics dashboard beyond moderation operations
3. Cross-product rule authoring outside moderation domain

## 16. Success Criteria

Design is successful when:

1. Every moderation source is represented in centralized moderation cases
2. Violations are created only from confirmed cases
3. Enforcement and suspensions are consistent and traceable
4. Appeals are structured and thread-based
5. Automation is configurable, safe, and explainable
6. Suspension middleware enforces platform-wide behavior
7. Admin moderation UI aligns with Payment Management standards
8. Migration completes with parity checks and admin signoff
