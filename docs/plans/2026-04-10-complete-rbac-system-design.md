# Complete RBAC System Design

**Date:** 2026-04-10  
**Status:** Implemented and fully verified  
**Approach:** Permission-first RBAC with shared content domain and compatibility-safe migration

## Execution Status (2026-04-10)

- Design decisions in this document were executed in code.
- Focused RBAC verification gates are green.
- Full-suite verification is green after stabilization follow-up.
- Detailed command output and final verification status are documented in `docs/changelogs/2026-04-10-complete-rbac-system.md`.

## Goal

Implement a complete Role-Based Access Control (RBAC) architecture using Spatie Laravel Permission as the source of truth for authorization decisions, while preserving existing route compatibility and avoiding duplicated content systems.

## Desired End State

- Admin has full governance control and can manage users, roles, permissions, monetization, and moderation.
- Admin can access the content creation domain used by instructors.
- Instructor can author content and manage related learning operations but cannot publish or moderate platform content.
- Learner can consume learning content.
- Parent can monitor linked learner progress and enrollments.
- Authorization logic is action-based (permissions), not identity-string-based role checks.

## Constraints and Non-Negotiables

1. Roles represent identity only.
2. Permissions represent actions only.
3. Business logic authorization relies on permissions, policies, and gates.
4. Keep controllers thin and domain behavior in services.
5. Preserve route ownership and route compatibility:
   - `routes/admin.php`
   - `routes/instructor.php`
   - `routes/web.php`
6. Avoid content CRUD duplication across admin and instructor systems.
7. Keep existing governance and age-visibility behavior intact.
8. Keep `users.role` during transition, but treat Spatie roles/permissions as authoritative.

## Current State Summary

### Verified Baseline

- Spatie `HasRoles` is already used in `User` model.
- Auth default guard is `web`.
- Spatie permission tables already exist.
- Existing route protection is mostly role middleware based.
- Existing role/permission seeding is centralized in one mixed `RolePermissionSeeder`.
- Current module content creation has role-separated controller surfaces (`AdminModuleController` and `Instructor\ModuleController`).

### Observed Gaps

1. Permission coverage is incomplete and inconsistent for full platform operations.
2. Several code paths still check `users.role` or `hasRole(...)` directly for business decisions.
3. Instructor routes are largely role-gated instead of permission-gated.
4. UI visibility still contains role-based conditions in key layouts.
5. Admin/instructor content tools have duplicated controller behavior.

## Approved Decisions

1. Scope: Full end-to-end with controller consolidation.
2. Legacy role field: Keep and sync during transition.
3. Parent taxonomy: Separate `parent` role.
4. Route compatibility: Preserve existing route paths and names.
5. Permission naming: Hybrid migration (keep stable names, add canonical names, deprecate inconsistent names).
6. Consolidation scope: Modules + Lessons + Topics + Quizzes in this phase.
7. Enforcement order: Middleware + policy/controller hardening in same phase.
8. Admin UX for instructor tools: Allow both admin and instructor access by permission.
9. Ownership semantics: Keep existing ownership/governance columns as canonical.
10. Seeder transition: Introduce dedicated seeders with compatibility path.
11. Super-admin behavior: Use `Gate::before` for admin reliability.
12. Role assignment policy: Single-role by default.
13. Parent-child checks: Permission plus relationship ownership checks.
14. Chat migration: Full permission-based migration in this phase.
15. Verification depth: Focused suite first, then full suite.
16. Rollout strategy: Multi-PR phased rollout.

## RBAC Model

### Identity Roles

- `admin`
- `instructor`
- `learner`
- `parent`

### Canonical Permission Domains

#### Access
- `access admin panel`
- `access instructor panel`
- `access learner platform`
- `access parent dashboard`

#### User Management
- `manage users`
- `view users`
- `create users`
- `edit users`
- `delete users`
- `archive users`
- `manage user relationships`

#### Roles and Permissions
- `manage roles`
- `assign roles`
- `manage permissions`
- `view role assignments`
- `update role assignments`

#### Content Governance and Publishing
- `view modules`
- `create modules`
- `edit modules`
- `delete modules`
- `submit modules`
- `resubmit modules`
- `withdraw module submissions`
- `review modules`
- `publish modules`
- `moderate modules`

#### Lessons and Topics
- `view lessons`
- `create lessons`
- `edit lessons`
- `delete lessons`
- `reorder lessons`
- `move lessons`
- `create lesson topics`
- `edit lesson topics`
- `delete lesson topics`
- `reorder lesson topics`

#### Quizzes and Assessment
- `view quizzes`
- `create quizzes`
- `edit quizzes`
- `delete quizzes`
- `manage quiz questions`
- `import quiz questions`
- `view assessment logs`

#### Enrollment and Learner Operations
- `view learners`
- `view enrollments`
- `manage enrollments`
- `approve enrollments`
- `reject enrollments`

#### Learner Consumption
- `take quizzes`
- `view certificates`
- `generate certificates`
- `download certificates`
- `enroll modules`
- `purchase modules`

#### Parent Monitoring
- `view learner progress`
- `view learner enrollments`

#### Chat
- `access chat`
- `start conversations`
- `send messages`
- `edit own messages`
- `delete own messages`
- `report messages`
- `manage message requests`
- `moderate chat`

#### Billing and Platform Governance
- `view payments`
- `manage payments`
- `manage subscription plans`
- `manage monetization settings`
- `view analytics`
- `view activity logs`
- `manage system settings`
- `manage notifications`

## Role Capability Matrix

### Admin

- Receives all permissions.
- Can access instructor content tools by permission.
- Can manage users, role assignments, permission mappings, moderation, billing, and settings.

### Instructor

Allowed:
- create/edit/delete/submit modules
- manage lessons/topics/quizzes
- view learners
- manage enrollments
- access chat
- view assessment logs

Not allowed:
- publish modules
- review modules
- manage users/roles/permissions
- manage payments/system settings

### Learner

Allowed:
- browse and consume modules/lessons
- take quizzes
- access chat

Not allowed:
- content authoring
- platform governance

### Parent

Allowed:
- monitor linked learner progress
- monitor linked learner enrollments

Not allowed:
- content authoring
- governance/billing operations

## Route Protection Design

### Admin Routes (`routes/admin.php`)

- Keep admin shell boundary with `auth` + `role:admin` for dashboard context.
- Apply permission checks inside route groups and controllers for all actions.
- Add/retain permission enforcement for users, roles, permissions, moderation, payments, plans, and monetization.

### Instructor Routes (`routes/instructor.php`)

- Preserve existing path/name contracts.
- Move from role-only protection to permission-driven action groups for shared content tooling.
- Permit admin access where admin has relevant permissions.

### Learner/Public Routes (`routes/web.php`)

- Preserve learner and parent route contracts.
- Replace role-based action gating with permissions where action semantics apply.
- Keep relationship integrity checks for parent-child actions.

### Chat Routes (`routes/web.php` chat group)

- Replace role list middleware with permission middleware (`access chat` and action-specific permissions).
- Keep conversation participant checks and ownership checks in controller/service layers.

## Controller and Service Architecture

## Objective

Unify the content authoring behavior used by admin and instructor to avoid duplicated business logic.

### Strategy

1. Introduce shared content domain orchestration in services.
2. Keep route compatibility by maintaining route files and names.
3. Refactor role-specific controllers into thin wrappers around shared services and policies.
4. Ensure ownership/governance behavior stays centralized and consistent.

### Consolidation Scope in This Phase

- Modules
- Lessons
- Topics
- Quizzes

## Ownership and Governance Semantics

Use existing schema fields as canonical in this rollout:

- `created_by`
- `content_owner_type`
- `current_review_status`
- `published_by_admin_id`
- `is_published`

### Rules

1. Instructor-authored modules remain instructor-owned.
2. Admin-authored modules remain admin-owned.
3. Instructor module publication still requires admin review/publish permission.
4. Admin can create official platform modules through the same content domain.

## Legacy `users.role` Compatibility Plan

### During Migration

- Keep `users.role` synchronized for compatibility with legacy flows.
- Spatie role assignment remains source of truth.
- Migrate business-action checks away from direct role strings first.

### Decommission Criteria

- No authorization decision depends on `users.role`.
- Residual role field usage limited to compatibility display or transitional reporting.

## UI Authorization Design

### Principle

UI visibility follows permissions, not role labels.

### Targets

- Sidebars
- Action buttons
- Management panels

### Server-side Requirement

UI checks are supplementary. Every privileged action must be revalidated server-side.

## Super-Admin Behavior

- Add `Gate::before` for admin to ensure resilient top-level access.
- Keep ownership and relationship safety checks in policies/services so super-admin bypass does not accidentally bypass domain integrity constraints.

## Error Handling and Security

1. Unauthorized action responses return `403` (or safe `404` masking where ownership leakage is sensitive).
2. Use Form Requests for input validation.
3. Use policies/authorize checks for resource actions.
4. Wrap role/permission mutation and moderation transitions in transactions.
5. Log privileged actions for auditability.

## Testing and Verification Strategy

### Phase B (Focused)

Run focused RBAC and regression suites:

- seeder consistency/idempotency
- route middleware/authorization behavior
- content governance publish/review boundaries
- content authoring access matrix (admin/instructor)
- parent monitoring access constraints
- chat permission matrix
- critical UI visibility checks

### Phase C (Full)

Run full test suite after focused green baseline and before completion claim.

## Rollout Plan (Multi-PR)

1. PR1: Permission taxonomy + dedicated seeders + compatibility bridge.
2. PR2: Global gate behavior + route permission middleware mapping.
3. PR3: Shared content domain consolidation for modules/lessons/topics/quizzes.
4. PR4: Policy/controller hardening and UI permission migration.
5. PR5: Chat permission migration and parent monitoring permission hardening.
6. PR6: Regression pass, cleanup, and legacy compatibility tightening.

## Risks and Mitigations

### Risk: Permission drift across code and seeders

Mitigation:
- Single permission catalog source in seeders/constants.
- Focused authorization tests per domain.

### Risk: Route regressions from middleware changes

Mitigation:
- Preserve route names/paths.
- Add/expand route authorization tests.

### Risk: Content ownership behavior changes during consolidation

Mitigation:
- Centralize ownership checks in policies/services.
- Add explicit owner/non-owner test cases.

### Risk: Chat regressions under new permissions

Mitigation:
- Extend existing chat matrix and HTTP flow tests.
- Keep participant membership checks unchanged.

### Risk: Legacy role column mismatch

Mitigation:
- Add sync strategy in role assignment flow.
- Add tests asserting role column consistency on role changes.

## Acceptance Criteria

1. Admin has full control through permissions and can access instructor content tools.
2. Instructor has scoped creation and learner-management actions but no publish/review authority.
3. Learner and parent capabilities are permission-scoped and non-overlapping.
4. Action-level authorization relies on permission checks and policies, not role-string checks.
5. Shared content domain is consolidated; no duplicated admin/instructor business logic.
6. Routes/controllers/UI are permission-protected with compatibility preserved.
7. Seeders produce deterministic RBAC state across environments.
8. Focused and full verification suites pass in sequence.

## Out of Scope for This Phase

1. Multi-role user assignment strategy beyond approved single-role baseline.
2. Large-scale schema replacement for ownership/governance fields.
3. Full removal of legacy `users.role` column.
4. Non-RBAC redesigns unrelated to authorization and shared content domain behavior.
