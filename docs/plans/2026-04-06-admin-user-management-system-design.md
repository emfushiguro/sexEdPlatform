# Admin User Management System Design

Date: 2026-04-06
Status: Approved
Approach: Structured incremental architecture over existing admin foundation

## 1. Goal

Deliver a centralized Admin User Management system that allows administrators to manage all platform users while preserving relationship transparency and role safety.

This design covers:

1. Unified user management for learners, parents, instructors, and admins.
2. Parent-child transparency and relationship controls.
3. Learner-to-instructor lineage transparency through application and role transition history.
4. Admin profile management in admin context.
5. Additional admin account creation.
6. Action-level permission checks for user operations.

## 2. Context Snapshot

Current implementation already provides:

1. Existing admin users CRUD routes and views.
2. Parent-child relationships via `parent_child_accounts` and user relationships.
3. Instructor application lifecycle and role transition auditing.
4. Spatie roles and permissions infrastructure.
5. Admin shell with reusable design primitives.

Design intent is evolutionary: extend the current system rather than replacing core auth and role mechanics.

## 3. Locked Decisions

The following are locked for this rollout:

1. Parent representation: Keep current role behavior and add explicit account type classification for admin transparency.
2. Age grouping: Derive from birthdate and persist a cached value for efficient filtering.
3. Instructor creation: Allow both direct admin instructor creation and learner-origin transitions, with audit requirements.
4. Manual role changes: Must sync role + Spatie role and record role transition with reason.
5. Parent-child relationship controls: Support attach/detach and verify/unverify with safeguards and full auditing.
6. Lifecycle states: Include archived as an explicit soft-offboarding state.
7. Admin profile: Keep shared profile baseline and add admin-focused entry/workspace in admin context.
8. Admin capability model: All admins equal in phase 1.
9. Instructor transparency depth: Show latest + historical applications + review timeline + role transition log.
10. UI model: Unified table with segmented tabs and detailed profile panels.
11. RBAC rollout: Add action-level permission checks now without full platform RBAC rewrite.
12. Verification depth: Feature + permission/policy + relationship transparency + regression coverage.

## 4. Architecture

### 4.1 Domain Strategy

Keep single `users` identity model with role and status while introducing explicit admin-facing classification.

Primary dimensions:

1. Role dimension: learner, instructor, admin (plus existing legacy roles retained for compatibility).
2. Status dimension: active, inactive, suspended, archived.
3. Account type dimension (admin visibility): learner-child, learner-teen, learner-adult, parent, instructor, admin.

### 4.2 Layering

1. Controllers remain orchestration-only.
2. Form Requests own validation contracts.
3. Service layer owns user management workflows and relationship mutations.
4. Eloquent relationships remain source-of-truth for relationship graph reads.

## 5. Data Model and Schema

### 5.1 Additive Schema Changes

Introduce additive fields and indexes only:

1. Cached age bracket field for fast learner filtering.
2. Optional account type classification field for admin indexing and stable sorting/filtering.
3. Supporting indexes for:
   - role + status
   - cached age bracket + status
   - account type + status

No destructive migration to existing role enum or parent-child schema in this phase.

### 5.2 Classification Rules

1. Learner age group is derived from birthdate and cached.
2. Parent account type is inferred by existing child links.
3. Instructor classification comes from role and instructor profile/application context.
4. Admin classification comes from role.

### 5.3 Relationship Sources

1. Parent-child links: `parent_child_accounts`.
2. Instructor lineage: `instructor_applications` + `role_transitions`.

## 6. Core Workflows

### 6.1 Centralized Admin User Management

Admins can:

1. Browse all users with role/type/status/age-group filters.
2. Create users (learner, parent-represented learner account, instructor, admin).
3. Edit details, role, and status.
4. Archive/unarchive accounts.
5. Access relationship panels and lineage timelines.

### 6.2 Parent-Child Transparency and Management

1. Child profile shows parent account details and relationship verification state.
2. Parent profile shows all linked child accounts.
3. Admin actions:
   - attach child to parent
   - detach child from parent
   - verify/unverify relationship
4. Guardrails:
   - no self-link
   - no duplicate links
   - mutation logging required

### 6.3 Learner-to-Instructor Transparency

Instructor profile view includes:

1. Origin learner context.
2. Latest application details.
3. Historical applications and reviews.
4. Role transition timeline with reason and approver.

### 6.4 Role Change Safety

When admin changes role manually:

1. Sync `users.role` and Spatie roles.
2. Require explicit reason.
3. Record `role_transitions` entry.

## 7. UI and UX Specification

### 7.1 Listing Experience

Keep a unified users page with:

1. Segmented tabs for quick type-focused browsing.
2. Filter bar (search, role, account type, status, age group).
3. Badges for role/status/account type.
4. Relationship indicators (has parent, has children, has instructor lineage).

### 7.2 Detail Experience

User detail pages include:

1. Identity and lifecycle controls.
2. Relationship transparency cards.
3. Instructor application and role transition timeline block.
4. Quick admin actions with confirmations.

### 7.3 Admin Profile

Add admin-context profile entry from admin panel with existing shared profile backend compatibility.

### 7.4 Theme Consistency

All views follow current admin language:

1. Existing spacing and card rhythm.
2. Existing status/badge color semantics.
3. Existing table and filter component patterns.

## 8. Authorization and RBAC

### 8.1 Route Gate

Retain admin role middleware at route group level.

### 8.2 Action-Level Permissions

Add explicit permission checks for sensitive operations:

1. view users
2. create users
3. edit users
4. delete users
5. manage roles
6. relationship mutation permissions

All admins are equal capability in this phase, but checks are added to future-proof split-admin models.

## 9. Audit and Traceability

All admin user mutations must be logged with before/after payloads and actor metadata.

Logged operations:

1. create/update/delete/archive/unarchive user
2. status transitions
3. role changes
4. parent-child attach/detach/verify/unverify

## 10. Error Handling and Data Integrity

1. Form requests enforce structured validation.
2. Relationship and role-change mutations run in transactions.
3. Authorization failures return appropriate forbidden responses.
4. Conflict conditions return actionable feedback.

## 11. Testing and Verification Strategy

### 11.1 Feature Coverage

1. User creation and update across supported types.
2. Status and archive flows.
3. Parent-child transparency read coverage.
4. Parent-child relationship write coverage.
5. Instructor lineage and transition visibility coverage.
6. Additional admin account creation coverage.

### 11.2 Security Coverage

1. Non-admin denial.
2. Permission-gated action denial.
3. Self-destructive protection checks.

### 11.3 Regression Coverage

1. Existing instructor application approval and transition workflow remains intact.
2. Existing parent monitoring behavior remains intact.
3. Existing admin users UI alignment baseline remains intact.

## 12. Rollout Plan

1. Phase 1: Additive schema + backfill-safe classification service.
2. Phase 2: Controller refactor to service + form requests + permission checks.
3. Phase 3: UI transparency panels + relationship mutation actions.
4. Phase 4: Focused test suite + build verification + hardening fixes.

## 13. Risks and Mitigations

1. Risk: classification drift on legacy users.
   - Mitigation: deterministic derivation and refresh routine.
2. Risk: relationship mutation misuse.
   - Mitigation: policy/permission checks + transaction + audit logs.
3. Risk: role change regressions.
   - Mitigation: mandatory transition logging and regression tests.

## 14. Out of Scope

1. Dedicated parent role migration in this release.
2. Full platform-wide RBAC rewrite.
3. Multi-tier admin hierarchy.
4. Auth flow rewrites beyond admin profile access context improvements.

## 15. Acceptance Criteria

Design is considered complete when:

1. Admin can manage all target user categories from one workspace.
2. Parent-child and learner-instructor relationships are transparent and actionable.
3. Admin profile management and additional admin account support are available.
4. Action-level permission checks are active for user operations.
5. Required feature, authorization, and regression tests pass.
