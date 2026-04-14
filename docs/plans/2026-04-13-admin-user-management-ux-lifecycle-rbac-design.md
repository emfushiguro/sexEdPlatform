# Admin User Management UX, Lifecycle, and RBAC Design

## 1. Purpose
Redesign the admin-side user management experience to improve usability, scalability, and lifecycle governance while preserving the platform's existing admin visual language and permission-first architecture.

Primary outcomes:
- Improve admin productivity with faster user discovery and clearer lifecycle controls.
- Upgrade create/edit user workflows into a guided modal wizard.
- Strengthen role and permission assignment transparency.
- Clarify parent-child relationship visibility and move mutation workflows into a dedicated management page.
- Keep all enhancements visually consistent with the current admin theme.

## 2. Approved Product Decisions
The following decisions were explicitly selected during brainstorming:

1. Wizard placement: modal launched from user table actions, while keeping existing backend create/edit endpoints.
2. Stat cards: keep only Total Users, Active Users, Suspended Users, Archived Users.
3. Active count rule: count status = active only.
4. Pagination: server-side pagination with per-page selector (10/25/50/100).
5. Search: debounced instant search (300ms), URL-stateful.
6. Search scope for role: search both legacy role column and assigned roles.
7. Created date filter: support both presets and from-to range.
8. No. column: continuous numbering across paginated pages.
9. Mobile table behavior: responsive stacked-card row presentation for small screens.
10. Create and edit both use same modal wizard.
11. Validation model: per-step UX validation plus final server-side validation.
12. Role source: dynamic from roles table with curated allowlist.
13. Others role path: inline new role creation inside wizard.
14. Permission descriptions: add description field to permission records.
15. Permission overrides: baseline inherited permissions plus explicit add/remove delta behavior.
16. Create-flow status control: Active/Inactive toggle only.
17. Review step: required confirmation checkbox.
18. Edit role-change note: optional TinyMCE rich text note.
19. TinyMCE scope: limited toolbar (formatting, lists, links).
20. Relationship mutation location: dedicated Relationship Management page.
21. Communication context: quick links to relevant chat context, RBAC-gated.
22. Quick links scope: payments, enrollments, reports, subscriptions.
23. Lifecycle governance: rule-based transition model.
24. Archived visibility: dedicated archived tab.

## 3. Scope
In scope:
- Admin users index card, filter, search, table, pagination, and responsiveness upgrades.
- Shared modal wizard for create and edit user flows.
- Role and permission assignment UX with inherited and override behavior.
- User profile relationship visibility cleanup and quick links expansion.
- Dedicated relationship management page for attach/detach/verification operations.
- Role-change workflow update to optional rich text notes.
- Lifecycle status meaning clarification and transition guardrails.

Out of scope:
- Moving to SPA/API-first architecture.
- Replacing Spatie permission architecture.
- Rebuilding unrelated admin domains.

## 4. Architecture Strategy (Approach B)
Approach B (approved): componentized admin UX refactor while preserving existing backend contracts.

Key principles:
- Preserve route ownership in admin routes.
- Keep controllers thin and service-owned behavior.
- Keep Form Request validation as the server source of truth.
- Use additive migrations for metadata support.
- Reuse existing Blade/Tailwind patterns to avoid visual drift.

## 5. Information Architecture and Screen Model
### 5.1 User Management Index
Sections:
1. Focused stat cards (4 cards only).
2. Segment tabs, including archived-focused visibility entry.
3. Unified filter/search toolbar.
4. Responsive users table with continuous No. column.
5. URL-stateful pagination controls.

### 5.2 Shared User Wizard Modal (Create and Edit)
Steps:
1. Basic user details.
2. Account security.
3. Role, permission model, and status toggle.
4. Review and confirmation.

Behavior:
- Create opens blank wizard.
- Edit opens prefilled wizard with existing role/direct permission data.

### 5.3 User Profile (Visibility-First)
Changes:
- Remove inline parent-child attachment forms and mutation controls.
- Show relationship visibility cards only where applicable.
- Keep lifecycle and role controls in profile utility panel.
- Expand quick links to related activity records.

### 5.4 Relationship Management Page (New)
Purpose:
- Centralize relationship mutation actions.

Capabilities:
- Attach parent-child relationship.
- Detach relationship.
- Verify/unverify relationship.
- Filter and search relationship records.

## 6. Detailed UX Design
### 6.1 Stat Cards
Cards displayed:
- Total Users
- Active Users
- Suspended Users
- Archived Users

Design notes:
- Keep current card shell style, spacing, border, and shadow language.
- Keep value prominence and short labels.

### 6.2 Search and Filters
Search:
- Debounced text input (300ms) across name, email, and role.
- Search state persisted in query string.

Filters:
- Status filter.
- Role filter.
- Created date range (from/to).
- Created date presets (today/7d/30d).
- Segment and archived tab controls.
- Per-page selector.

### 6.3 Users Table
Column changes:
- Add No. column.
- Remove Transparency column.

Other behavior:
- Keep status badge consistency.
- Preserve action affordances for view/edit.
- Mobile mode presents stacked data cards preserving action controls.

### 6.4 Wizard Step Design
Step 1: Basic details
- Full name
- Email
- Birthdate

Step 2: Security
- Password
- Confirm password

Step 3: Role and permissions
- Role selection with dynamic curated role list.
- Others path opens inline role creation UI.
- Inherited permissions panel is collapsed by default.
- Permission rows include name, short description, and toggle state.
- Override panel supports add/remove from inherited baseline.
- Create flow status toggle: Active/Inactive only.

Step 4: Review
- Full summary of all submitted values.
- Required confirmation checkbox before final submit.

### 6.5 Role Change UX
Update:
- Remove mandatory role-change reason requirement.
- Replace with optional TinyMCE custom notes field.

Editor configuration:
- Limited formatting features only.
- Links and basic structure supported.
- Content sanitized and logged for audit context when provided.

### 6.6 Profile Relationship Visibility
Child profile visibility:
- Linked parent account summary.
- Relationship verification status.
- Communication context shortcut when permitted.

Parent profile visibility:
- Linked child account list.
- Relationship overview status indicators.

Mutation operations:
- Removed from profile page.
- Moved to dedicated relationship management page.

### 6.7 Quick Links
Required links:
- Payment history
- Module enrollments
- Reports
- Subscription records

All links should preserve admin shell style and prefill searchable identifiers where possible.

## 7. RBAC and Permission Model
Role assignment:
- Dynamic role loading, curated allowlist for operational safety.

Permission inheritance:
- Selected role provides baseline permission set.

Override model:
- Explicit additive/removal delta over inherited baseline.
- User-level direct permissions remain the persistence mechanism.

Permission descriptions:
- Add a dedicated description field in permission records.

Authorization:
- Viewing inherited permissions allowed for eligible admin operators.
- Override and role/permission mutation controls require permission management capability.

## 8. Lifecycle Governance Design
Lifecycle definitions:
- Active: fully functional account.
- Inactive: existing account, currently idle, not penalized.
- Suspended: administrative restriction.
- Archived: historical/non-active participant state.

Governance:
- Rule-based transition model with confirmation on high-impact transitions.
- Archived users remain accessible via dedicated archived tab.

## 9. Data and Backend Contract Changes
Planned backend contract updates:
1. Request validation updates for search/filter/query controls and optional rich text notes.
2. Service-level query enhancements for role-aware search and date filtering.
3. Service-level stats updates for focused card set.
4. Add permission description support.
5. Add relationship-management page routes and handlers.

Compatibility constraints:
- Preserve role sync compatibility with existing legacy role metadata.
- Keep route naming stable where feasible.

## 10. Error Handling and Safety
Wizard safety:
- Block step progression on invalid required data.
- Revalidate all data at final submit.
- Surface duplicate email and permission conflicts clearly.

Relationship safety:
- Prevent duplicate links and invalid direction combinations.
- Keep confirmation prompts for detach and verification changes.

Role/lifecycle safety:
- Preserve anti-self-destructive constraints where currently enforced.

## 11. Theme Consistency Enforcement
Hard requirement:
- All new and updated UI must remain consistent with current admin/platform theme.

Applied constraints:
1. Reuse existing admin layout shell.
2. Preserve existing card, badge, button, and spacing semantics.
3. Keep status color semantics aligned with existing conventions.
4. Avoid introducing conflicting typography scale or interaction language.

## 12. Testing Strategy
Coverage focus:
1. Filter/search/pagination/date-range behavior.
2. No. column continuity across pages.
3. Wizard create/edit happy and edge cases.
4. Permission inheritance and override delta persistence.
5. Role-change optional rich text notes behavior.
6. Relationship visibility in profile and mutation in dedicated page.
7. Lifecycle transition guardrails and archived tab behavior.
8. Authorization boundaries around all sensitive actions.

## 13. Rollout and Risk Management
Risks:
1. Regression in existing admin user workflows.
2. Permission cache mismatches during role/permission changes.
3. Increased UI complexity from wizard and relationship separation.

Mitigations:
1. Preserve existing endpoint contracts.
2. Add focused feature and authorization tests.
3. Keep componentized UI structure with isolated partials.

## 14. Acceptance Criteria
This design is accepted when:
1. All eight requested enhancement groups are reflected in implementation.
2. Users index supports approved search, filters, and pagination behavior.
3. Create/edit flows use the shared modal wizard with review confirmation.
4. RBAC permission assignment UX supports inheritance, descriptions, and overrides.
5. Profile relationship visibility is clear and mutation controls are relocated.
6. Role change supports optional TinyMCE notes without mandatory reason.
7. Lifecycle status semantics and transition behavior are implemented clearly.
8. Updated UI remains fully aligned with current admin theme conventions.

---

Approval status: Approved by user during brainstorming on 2026-04-13.
