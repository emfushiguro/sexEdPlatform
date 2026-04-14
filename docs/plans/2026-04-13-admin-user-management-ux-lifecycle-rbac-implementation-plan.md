# Admin User Management UX, Lifecycle, and RBAC Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver a theme-consistent admin user-management upgrade with focused stats, enhanced index filtering/search, shared create-edit wizard modal, permission-first role assignment UI, dedicated relationship management page, optional rich-text role notes, and rule-based lifecycle governance.

**Architecture:** Extend existing Laravel Blade admin flows with thin-controller orchestration and service-owned behavior. Preserve permission-first contracts and route ownership while introducing componentized views and additive schema updates for permission descriptions and role-change notes.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, TinyMCE, Spatie Laravel Permission, PHPUnit.

---

I am using the writing-plans skill to create the implementation plan.

## Task 1: Add Schema Support for Permission Descriptions and Role Notes
Files:
- Create: database/migrations/2026_04_13_000001_add_description_to_permissions_table.php
- Create: database/migrations/2026_04_13_000002_add_custom_notes_to_role_transitions_table.php
- Modify: app/Models/RoleTransition.php

Step 1: Write failing tests
- Create failing assertions in tests/Feature/Admin/AdminUserRoleTransitionAuditTest.php for nullable custom notes persistence.
- Create failing assertions in tests/Feature/Admin/AdminPermissionDescriptionDisplayTest.php for permission description availability.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUserRoleTransitionAuditTest
Expected: FAIL due to missing custom_notes column.

Run: php artisan test --filter=AdminPermissionDescriptionDisplayTest
Expected: FAIL due to missing description column.

Step 3: Write minimal implementation
- Add nullable description column to permissions table.
- Add nullable longText custom_notes column to role_transitions table.
- Add custom_notes to fillable/casts in RoleTransition model.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUserRoleTransitionAuditTest
Run: php artisan test --filter=AdminPermissionDescriptionDisplayTest
Expected: PASS.

Step 5: Commit
Run:
- git add database/migrations/2026_04_13_000001_add_description_to_permissions_table.php database/migrations/2026_04_13_000002_add_custom_notes_to_role_transitions_table.php app/Models/RoleTransition.php tests/Feature/Admin/AdminUserRoleTransitionAuditTest.php tests/Feature/Admin/AdminPermissionDescriptionDisplayTest.php
- git commit -m "feat: add permission descriptions and role transition custom notes"

## Task 2: Update Form Requests for New Filters and Optional Notes
Files:
- Modify: app/Http/Requests/Admin/ChangeUserRoleRequest.php
- Modify: app/Http/Requests/Admin/UpdateUserRequest.php
- Create: app/Http/Requests/Admin/IndexUserRequest.php

Step 1: Write failing tests
- Add failing tests to tests/Feature/Admin/AdminUsersFilterBehaviorTest.php covering created_from/created_to/date_preset/per_page and debounced search query acceptance.
- Add failing tests to tests/Feature/Admin/AdminUserRoleTransitionAuditTest.php confirming role reason is no longer required.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUsersFilterBehaviorTest
Expected: FAIL due to unsupported query validation.

Run: php artisan test --filter=AdminUserRoleTransitionAuditTest
Expected: FAIL because reason remains required.

Step 3: Write minimal implementation
- Allow optional custom_notes field and remove mandatory reason requirements.
- Add index request validation for search, role, status, created_from, created_to, date_preset, per_page.
- Keep strict authorization checks in requests.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUsersFilterBehaviorTest
Run: php artisan test --filter=AdminUserRoleTransitionAuditTest
Expected: PASS.

Step 5: Commit
Run:
- git add app/Http/Requests/Admin/ChangeUserRoleRequest.php app/Http/Requests/Admin/UpdateUserRequest.php app/Http/Requests/Admin/IndexUserRequest.php tests/Feature/Admin/AdminUsersFilterBehaviorTest.php tests/Feature/Admin/AdminUserRoleTransitionAuditTest.php
- git commit -m "refactor: update admin user requests for filters and optional role notes"

## Task 3: Enhance UserManagementService Query and Stats Behavior
Files:
- Modify: app/Services/Admin/UserManagementService.php

Step 1: Write failing tests
- Add failing tests to tests/Feature/Admin/AdminUsersFilterBehaviorTest.php for:
  - debounced search semantics (name/email/role).
  - created date range filtering.
  - date presets.
  - per-page selector.
  - continuous row numbering metadata support.
- Add failing tests to tests/Feature/Admin/AdminUsersStatsCardsTest.php for four-card stat payload.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUsersFilterBehaviorTest
Expected: FAIL on query behavior mismatches.

Run: php artisan test --filter=AdminUsersStatsCardsTest
Expected: FAIL due to current broader stats set.

Step 3: Write minimal implementation
- Add role-aware search support against legacy role metadata and assigned role relation.
- Add created date filters (range + preset).
- Enforce configurable per-page values (10/25/50/100).
- Return focused stats set: total, active, suspended, archived.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUsersFilterBehaviorTest
Run: php artisan test --filter=AdminUsersStatsCardsTest
Expected: PASS.

Step 5: Commit
Run:
- git add app/Services/Admin/UserManagementService.php tests/Feature/Admin/AdminUsersFilterBehaviorTest.php tests/Feature/Admin/AdminUsersStatsCardsTest.php
- git commit -m "feat: improve admin user query filters, pagination, and focused stats"

## Task 4: Add Role/Permission Delta and New Role Inline Support in Service Layer
Files:
- Modify: app/Services/Admin/UserManagementService.php
- Modify: app/Services/Admin/RoleSyncService.php
- Create: app/Support/Permissions/PermissionOverrideDelta.php

Step 1: Write failing tests
- Add failing coverage to tests/Feature/Admin/AdminUserPermissionOverrideFlowTest.php for inherited baseline plus add/remove delta behavior.
- Add failing coverage to tests/Feature/Admin/AdminUserPermissionOverrideFlowTest.php for inline new role creation path.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUserPermissionOverrideFlowTest
Expected: FAIL due to missing delta/new-role behavior.

Step 3: Write minimal implementation
- Implement permission delta normalization helper.
- Apply inherited + overrides safely.
- Support inline creation of curated new role during wizard save.
- Ensure all mutations are transactional and auditable.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUserPermissionOverrideFlowTest
Expected: PASS.

Step 5: Commit
Run:
- git add app/Services/Admin/UserManagementService.php app/Services/Admin/RoleSyncService.php app/Support/Permissions/PermissionOverrideDelta.php tests/Feature/Admin/AdminUserPermissionOverrideFlowTest.php
- git commit -m "feat: add permission override delta and inline role creation support"

## Task 5: Update Admin User Controller and Add Relationship Management Endpoints
Files:
- Modify: app/Http/Controllers/Admin/UserAdminController.php
- Create: app/Http/Controllers/Admin/UserRelationshipAdminController.php
- Modify: routes/admin.php

Step 1: Write failing tests
- Add failing tests in tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php for new relationship management index/actions.
- Add failing tests in tests/Feature/Admin/AdminUsersAuthorizationTest.php for permission-gated access.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUserRelationshipManagementPageTest
Expected: FAIL due to missing routes/controller.

Run: php artisan test --filter=AdminUsersAuthorizationTest
Expected: FAIL due to missing/changed guards.

Step 3: Write minimal implementation
- Add dedicated relationship management page/action routes under admin users namespace.
- Keep existing attach/detach/verify mutation behavior routed through dedicated controller.
- Keep UserAdminController thin and focused.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUserRelationshipManagementPageTest
Run: php artisan test --filter=AdminUsersAuthorizationTest
Expected: PASS.

Step 5: Commit
Run:
- git add app/Http/Controllers/Admin/UserAdminController.php app/Http/Controllers/Admin/UserRelationshipAdminController.php routes/admin.php tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php tests/Feature/Admin/AdminUsersAuthorizationTest.php
- git commit -m "feat: add admin relationship management endpoints and controller split"

## Task 6: Refactor Users Index UI with Theme-Consistent Components
Files:
- Modify: resources/views/admin/users/index.blade.php
- Create: resources/views/admin/users/partials/stats-cards.blade.php
- Create: resources/views/admin/users/partials/filter-toolbar.blade.php
- Create: resources/views/admin/users/partials/users-table.blade.php

Step 1: Write failing tests
- Add view assertions in tests/Feature/Admin/AdminUsersUiAlignmentTest.php for:
  - four required cards only.
  - No. column present.
  - Transparency column absent.
  - filter controls and per-page selector present.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUsersUiAlignmentTest
Expected: FAIL with missing/extra UI elements.

Step 3: Write minimal implementation
- Replace index composition with partials.
- Add responsive stacked row behavior on small screens.
- Keep current admin theme class vocabulary and spacing rhythm.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUsersUiAlignmentTest
Expected: PASS.

Step 5: Commit
Run:
- git add resources/views/admin/users/index.blade.php resources/views/admin/users/partials/stats-cards.blade.php resources/views/admin/users/partials/filter-toolbar.blade.php resources/views/admin/users/partials/users-table.blade.php tests/Feature/Admin/AdminUsersUiAlignmentTest.php
- git commit -m "feat: refactor admin users index with focused cards and enhanced table UX"

## Task 7: Implement Shared Create/Edit Modal Wizard UI
Files:
- Modify: resources/views/admin/users/index.blade.php
- Modify: resources/views/admin/users/create.blade.php
- Modify: resources/views/admin/users/edit.blade.php
- Create: resources/views/admin/users/partials/user-wizard-modal.blade.php
- Create: resources/views/admin/users/partials/user-wizard-steps.blade.php

Step 1: Write failing tests
- Add failing coverage to tests/Feature/Admin/AdminUserWizardFlowTest.php for:
  - modal launch from create and edit actions.
  - step rendering and required field prompts.
  - final confirmation checkbox enforcement.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUserWizardFlowTest
Expected: FAIL due to missing wizard modal and steps.

Step 3: Write minimal implementation
- Implement shared Alpine-powered modal wizard used by create and edit flows.
- Prefill edit flow values.
- Enforce step-by-step progression and validation feedback.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUserWizardFlowTest
Expected: PASS.

Step 5: Commit
Run:
- git add resources/views/admin/users/index.blade.php resources/views/admin/users/create.blade.php resources/views/admin/users/edit.blade.php resources/views/admin/users/partials/user-wizard-modal.blade.php resources/views/admin/users/partials/user-wizard-steps.blade.php tests/Feature/Admin/AdminUserWizardFlowTest.php
- git commit -m "feat: add shared create-edit user modal wizard"

## Task 8: Build Permission Cards UI with Description and Toggle Behavior
Files:
- Modify: app/Http/Controllers/Admin/UserAdminController.php
- Create: resources/views/admin/users/partials/permission-cards.blade.php
- Modify: resources/views/admin/users/partials/user-wizard-steps.blade.php

Step 1: Write failing tests
- Add failing checks to tests/Feature/Admin/AdminUserPermissionUiTest.php for:
  - inherited panel collapsed by default.
  - permission description visible.
  - toggle rows available.
  - override section behavior.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUserPermissionUiTest
Expected: FAIL due to missing card/toggle behaviors.

Step 3: Write minimal implementation
- Provide permission payload with descriptions.
- Render entitlement-style cards with toggle interactions.
- Keep default inherited permissions panel collapsed.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUserPermissionUiTest
Expected: PASS.

Step 5: Commit
Run:
- git add app/Http/Controllers/Admin/UserAdminController.php resources/views/admin/users/partials/permission-cards.blade.php resources/views/admin/users/partials/user-wizard-steps.blade.php tests/Feature/Admin/AdminUserPermissionUiTest.php
- git commit -m "feat: add permission cards with descriptions and toggle overrides"

## Task 9: Redesign User Profile for Visibility-Only Relationship Panels and Expanded Quick Links
Files:
- Modify: resources/views/admin/users/show.blade.php
- Create: resources/views/admin/users/partials/relationship-visibility-panel.blade.php
- Create: resources/views/admin/users/partials/quick-links-panel.blade.php

Step 1: Write failing tests
- Add failing tests to tests/Feature/Admin/AdminUserRelationshipTransparencyTest.php for profile visibility-only behavior.
- Add failing tests to tests/Feature/Admin/AdminUserProfileQuickLinksTest.php for required quick links.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUserRelationshipTransparencyTest
Expected: FAIL if mutation controls still present in profile.

Run: php artisan test --filter=AdminUserProfileQuickLinksTest
Expected: FAIL if expected links are missing.

Step 3: Write minimal implementation
- Remove profile attachment mutation controls.
- Keep relationship transparency cards and communication context links.
- Add required quick links with prefilled query context.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUserRelationshipTransparencyTest
Run: php artisan test --filter=AdminUserProfileQuickLinksTest
Expected: PASS.

Step 5: Commit
Run:
- git add resources/views/admin/users/show.blade.php resources/views/admin/users/partials/relationship-visibility-panel.blade.php resources/views/admin/users/partials/quick-links-panel.blade.php tests/Feature/Admin/AdminUserRelationshipTransparencyTest.php tests/Feature/Admin/AdminUserProfileQuickLinksTest.php
- git commit -m "feat: simplify profile relationship visibility and expand quick links"

## Task 10: Add Dedicated Relationship Management Page UI
Files:
- Create: resources/views/admin/users/relationships/index.blade.php
- Modify: app/Http/Controllers/Admin/UserRelationshipAdminController.php
- Modify: resources/views/layouts/admin.blade.php

Step 1: Write failing tests
- Extend tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php for list/search/filter and attach/detach/verify actions.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUserRelationshipManagementPageTest
Expected: FAIL for missing page elements and actions.

Step 3: Write minimal implementation
- Build relationship management page with consistent admin card/table patterns.
- Wire actions with confirmation and authorization.
- Add navigation entry under admin user management grouping.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUserRelationshipManagementPageTest
Expected: PASS.

Step 5: Commit
Run:
- git add resources/views/admin/users/relationships/index.blade.php app/Http/Controllers/Admin/UserRelationshipAdminController.php resources/views/layouts/admin.blade.php tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php
- git commit -m "feat: add dedicated admin relationship management page"

## Task 11: Add TinyMCE Optional Role Notes and Lifecycle Transition Guardrails
Files:
- Modify: resources/views/admin/users/show.blade.php
- Modify: app/Services/Admin/UserManagementService.php
- Modify: app/Http/Controllers/Admin/UserAdminController.php
- Modify: app/Http/Requests/Admin/ChangeUserRoleRequest.php

Step 1: Write failing tests
- Extend tests/Feature/Admin/AdminUserRoleTransitionAuditTest.php for:
  - optional custom notes accepted.
  - required reason no longer enforced.
  - notes persisted and auditable.
- Add tests in tests/Feature/Admin/AdminUserLifecycleGovernanceTest.php for transition guardrails.

Step 2: Run tests to verify failure
Run: php artisan test --filter=AdminUserRoleTransitionAuditTest
Expected: FAIL for old reason requirement.

Run: php artisan test --filter=AdminUserLifecycleGovernanceTest
Expected: FAIL for missing guardrails.

Step 3: Write minimal implementation
- Integrate TinyMCE on optional custom notes field (limited toolbar).
- Store sanitized notes in role transition custom_notes.
- Apply lifecycle confirmation and guardrail checks in service layer.

Step 4: Run tests to verify pass
Run: php artisan test --filter=AdminUserRoleTransitionAuditTest
Run: php artisan test --filter=AdminUserLifecycleGovernanceTest
Expected: PASS.

Step 5: Commit
Run:
- git add resources/views/admin/users/show.blade.php app/Services/Admin/UserManagementService.php app/Http/Controllers/Admin/UserAdminController.php app/Http/Requests/Admin/ChangeUserRoleRequest.php tests/Feature/Admin/AdminUserRoleTransitionAuditTest.php tests/Feature/Admin/AdminUserLifecycleGovernanceTest.php
- git commit -m "feat: add optional TinyMCE role notes and lifecycle transition guardrails"

## Task 12: Final Verification and Theme Consistency Regression Sweep
Files:
- Modify as needed from failed tests only

Step 1: Run focused test suites
Run:
- php artisan test --filter=AdminUsersFilterBehaviorTest
- php artisan test --filter=AdminUsersStatsCardsTest
- php artisan test --filter=AdminUserWizardFlowTest
- php artisan test --filter=AdminUserPermissionOverrideFlowTest
- php artisan test --filter=AdminUserPermissionUiTest
- php artisan test --filter=AdminUserRelationshipTransparencyTest
- php artisan test --filter=AdminUserRelationshipManagementPageTest
- php artisan test --filter=AdminUserRoleTransitionAuditTest
- php artisan test --filter=AdminUserLifecycleGovernanceTest
- php artisan test --filter=AdminUsersAuthorizationTest
- php artisan test --filter=AdminUsersUiAlignmentTest

Expected:
- All listed suites PASS.

Step 2: Run broad regression checks for known adjacent surfaces
Run:
- php artisan test --filter=ParentChildMonitoringTest
- php artisan test --filter=InstructorApplicationApprovalTest

Expected:
- PASS with no regressions.

Step 3: Build frontend assets
Run:
- npm run build

Expected:
- Successful Vite build.

Step 4: Validate final UI consistency manually
- Check admin users index and profile in desktop and mobile widths.
- Confirm cards, badges, spacing, and typography stay aligned with existing admin theme.

Step 5: Commit final stabilization changes
Run:
- git add -A
- git commit -m "test: verify admin user management ux, rbac, and lifecycle redesign"

---

## Delivery Checklist
1. Focused cards (total/active/suspended/archived) are live.
2. Users table supports approved filters, search, No. column continuity, and pagination behavior.
3. Create/Edit use shared modal wizard with confirmation step.
4. Permission assignment UI supports inherited and override behavior with descriptions.
5. Profile relationship visibility is clear and mutation controls are relocated.
6. Dedicated relationship management page is available and permission-gated.
7. Role change uses optional TinyMCE notes without mandatory reason.
8. Lifecycle status semantics and transition guardrails are enforced.
9. UI remains consistent with current admin theme.

---

Plan complete and saved to docs/plans/2026-04-13-admin-user-management-ux-lifecycle-rbac-implementation-plan.md. Two execution options:

1. Subagent-Driven (this session) - dispatch a fresh subagent per task, review between tasks.
2. Parallel Session (separate) - open a new session with executing-plans for batched implementation.
