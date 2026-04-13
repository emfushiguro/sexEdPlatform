# Admin User Management System Implementation Plan

> For Copilot: execute this plan incrementally, preserving current architecture rules (thin controllers, Form Requests, service-owned domain logic, strict role boundaries).

Goal: Deliver centralized admin user management with account type transparency, parent-child relationship controls, learner-to-instructor lineage visibility, admin profile management entry, additional admin creation, and action-level permission checks.

Architecture: Evolutionary extension of existing admin users module using additive migrations, new admin user management service layer, request classes for validation, and UI updates that preserve current admin design language.

Tech stack: Laravel 12, PHP 8.2, Blade, Tailwind, Spatie Permission, PHPUnit.

---

## Task 1: Add Schema Support for Classification and Performance

Files:
1. Create: `database/migrations/2026_04_06_000001_add_admin_user_management_fields_to_users_table.php`
2. Modify: `app/Models/User.php`

Step 1: Add additive user table fields
1. Add `account_type` nullable string.
2. Add `age_bracket_cached` nullable string.
3. Add indexes:
   - `role,status`
   - `account_type,status`
   - `age_bracket_cached,status`

Step 2: Add model casts/constants/helpers
1. Add helper methods for account type derivation.
2. Keep backward compatibility with existing role and profile methods.

Step 3: Verification
1. `php artisan test --filter=AdminUsersUiAlignmentTest`
2. `php artisan migrate --pretend`

Commit:
`git commit -m "feat: add user classification fields for admin management"`

---

## Task 2: Introduce Form Requests for Admin User Operations

Files:
1. Create: `app/Http/Requests/Admin/StoreUserRequest.php`
2. Create: `app/Http/Requests/Admin/UpdateUserRequest.php`
3. Create: `app/Http/Requests/Admin/UpdateUserStatusRequest.php`
4. Create: `app/Http/Requests/Admin/AttachParentChildRequest.php`
5. Create: `app/Http/Requests/Admin/DetachParentChildRequest.php`
6. Create: `app/Http/Requests/Admin/ToggleParentChildVerificationRequest.php`
7. Create: `app/Http/Requests/Admin/ChangeUserRoleRequest.php`

Step 1: Move inline validation out of controller
1. Define strict validation per operation.
2. Enforce role whitelist and status whitelist including archived.
3. Enforce relationship integrity rules.

Step 2: Verification
1. `php artisan test --filter=AdminUsersUiAlignmentTest`

Commit:
`git commit -m "refactor: move admin user validation into form requests"`

---

## Task 3: Implement Admin User Management Service Layer

Files:
1. Create: `app/Services/Admin/UserManagementService.php`
2. Create: `app/Services/Admin/UserRelationshipService.php`
3. Modify: `app/Services/AdminActivityLogService.php` (only if additional helper is needed)

Step 1: Build query and classification orchestration
1. Centralize index filtering (search, role, status, account type, age bracket).
2. Add account type derivation/refresh behavior.

Step 2: Build mutation workflows
1. Create/update users with role sync.
2. Status transitions including archive/unarchive.
3. Role changes requiring transition reason and role transition record.

Step 3: Build relationship workflows
1. Attach parent-child relation.
2. Detach relation.
3. Verify/unverify relation.
4. Use transactions and emit audit logs.

Step 4: Verification
1. `php artisan test --filter=ParentChildMonitoringTest`
2. `php artisan test --filter=InstructorApplicationApprovalTest`

Commit:
`git commit -m "feat: add admin user management and relationship services"`

---

## Task 4: Refactor Admin User Controller to Thin Orchestration

Files:
1. Modify: `app/Http/Controllers/Admin/UserAdminController.php`

Step 1: Inject services and Form Requests
1. Replace inline validation with Form Requests.
2. Delegate all business logic to services.

Step 2: Add endpoints for relationship actions
1. Attach parent-child.
2. Detach parent-child.
3. Toggle verification.
4. Optional quick status/role action endpoints.

Step 3: Keep current route names stable for backward compatibility.

Step 4: Verification
1. `php artisan test --filter=AdminUsersUiAlignmentTest`

Commit:
`git commit -m "refactor: slim admin user controller with service orchestration"`

---

## Task 5: Extend Admin User Routes and Permission Gates

Files:
1. Modify: `routes/admin.php`
2. Modify: `database/seeders/RolePermissionSeeder.php`

Step 1: Add relationship action routes under admin users group
1. Relationship attach/detach.
2. Relationship verify/unverify.

Step 2: Add/ensure required permission names
1. `view users`
2. `create users`
3. `edit users`
4. `delete users`
5. `manage roles`
6. `manage user relationships` (new)

Step 3: Wire action-level permission checks in controller/service policies.

Step 4: Verification
1. `php artisan test --filter=AdminUsersAuthorizationTest`

Commit:
`git commit -m "feat: add admin user action routes and permission checks"`

---

## Task 6: Upgrade Admin Users Index UI (Unified + Segmented)

Files:
1. Modify: `resources/views/admin/users/index.blade.php`
2. Optional create: `resources/views/admin/users/partials/filters.blade.php`
3. Optional create: `resources/views/admin/users/partials/relationship-badges.blade.php`

Step 1: Add segmented tabs and richer filters
1. Tabs: all, learners, parents, instructors, admins.
2. Filters: role, status, account type, age bracket.

Step 2: Add transparency cues in rows
1. Parent/child link indicators.
2. Instructor lineage indicator.

Step 3: Keep current admin UI language and spacing patterns.

Step 4: Verification
1. `php artisan test --filter=AdminUsersUiAlignmentTest`

Commit:
`git commit -m "feat: enhance admin users index with classification and transparency filters"`

---

## Task 7: Upgrade Admin User Detail UI (Transparency Panels)

Files:
1. Modify: `resources/views/admin/users/show.blade.php`
2. Optional create: `resources/views/admin/users/partials/parent-child-panel.blade.php`
3. Optional create: `resources/views/admin/users/partials/instructor-lineage-panel.blade.php`
4. Optional create: `resources/views/admin/users/partials/role-transition-timeline.blade.php`

Step 1: Parent-child transparency panel
1. On child: show linked parent and verification metadata.
2. On parent: show linked children list.

Step 2: Learner-to-instructor lineage panel
1. Show latest and historical instructor applications.
2. Show review outcomes and timeline.
3. Show role transitions.

Step 3: Add safe relationship action controls with confirmation.

Step 4: Verification
1. `php artisan test --filter=AdminUserRelationshipTransparencyTest`

Commit:
`git commit -m "feat: add relationship and lineage transparency panels to admin user profile"`

---

## Task 8: Update Create/Edit User Forms for Role-Aware Management

Files:
1. Modify: `resources/views/admin/users/create.blade.php`
2. Modify: `resources/views/admin/users/edit.blade.php`

Step 1: Add role-aware form hints and account type presentation.
2. Add status options including archived handling for edit path.
3. Preserve current visual design system.

Step 2: Ensure admin account creation path is explicit and safe.

Step 3: Verification
1. `php artisan test --filter=AdminUserCreationAndLifecycleTest`

Commit:
`git commit -m "feat: align admin user create edit forms with role-aware management"`

---

## Task 9: Add Admin Profile Management Entry and Context

Files:
1. Modify: `resources/views/layouts/admin.blade.php`
2. Create: `app/Http/Controllers/Admin/AdminProfileController.php` (if separate admin-context page is used)
3. Modify: `routes/admin.php`
4. Create: `resources/views/admin/profile/show.blade.php` (if separate admin-context page is used)

Step 1: Add admin-context profile route and entry.
2. Reuse existing shared profile update flow where possible.
3. Keep admin shell consistency.

Step 2: Verification
1. `php artisan test --filter=AdminProfileManagementTest`

Commit:
`git commit -m "feat: add admin-context profile management entry"`

---

## Task 10: Add Focused Feature and Authorization Tests

Files:
1. Create: `tests/Feature/Admin/AdminUserManagementFeatureTest.php`
2. Create: `tests/Feature/Admin/AdminUserRelationshipTransparencyTest.php`
3. Create: `tests/Feature/Admin/AdminUserRelationshipMutationTest.php`
4. Create: `tests/Feature/Admin/AdminUserRoleTransitionAuditTest.php`
5. Create: `tests/Feature/Admin/AdminUsersAuthorizationTest.php`
6. Optional: update existing `tests/Feature/Admin/AdminUsersUiAlignmentTest.php`

Step 1: Feature behavior tests
1. CRUD and status lifecycle.
2. Relationship visibility and mutation.
3. Instructor lineage visibility.
4. Additional admin creation.

Step 2: Authorization tests
1. Non-admin and missing-permission denials.
2. Privileged action guards.

Step 3: Regression tests
1. Confirm instructor application approval still writes role transitions.
2. Confirm parent monitoring behavior remains intact.

Step 4: Verification run
1. `php artisan test --filter=AdminUser`
2. `php artisan test --filter=ParentChildMonitoringTest`
3. `php artisan test --filter=InstructorApplicationApprovalTest`
4. `php artisan test --filter=AdminUsersUiAlignmentTest`

Commit:
`git commit -m "test: cover admin user management transparency and authorization flows"`

---

## Task 11: Final Verification and Build

Step 1: Execute final checks
1. `php artisan test --filter=AdminUser`
2. `php artisan test --filter=ParentChildMonitoringTest`
3. `php artisan test --filter=InstructorApplicationApprovalTest`
4. `php artisan test --filter=AdminUsersUiAlignmentTest`
5. `npm run build`

Step 2: If failures occur
1. Fix only relevant issues introduced by this feature.
2. Re-run impacted tests.

Step 3: Completion criteria
1. All required tests pass.
2. No regression in parent-child and instructor lifecycle behavior.
3. Admin users module provides centralized management with relationship transparency.

---

## Rollout Notes

1. This plan intentionally avoids parent role migration.
2. All-admin-equal capability is accepted for this phase.
3. Full platform RBAC matrix remains future work.

## Deliverables

1. Centralized admin users management enhancements.
2. Relationship transparency and mutation controls.
3. Learner-to-instructor lineage transparency.
4. Admin profile entry in admin context.
5. Additional admin account support.
6. Permission-enforced action-level gates.
7. Focused feature and regression test suite.
