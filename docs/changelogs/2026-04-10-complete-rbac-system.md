# 2026-04-10 - Complete RBAC System Rollout

## Summary
- Implemented a permission-first RBAC architecture with Spatie Permission as the authorization source of truth.
- Added deterministic canonical RBAC seeders and retained a compatibility bridge for existing seeding entry points.
- Added a super-admin gate behavior for admin reliability and introduced a role sync service for legacy users.role compatibility.
- Migrated instructor/admin/content/chat authorization paths from role-string checks to permission and policy checks.
- Introduced shared content domain services to reduce duplicated module authoring/access behavior across admin and instructor controllers.
- Added focused RBAC regression coverage across seeders, routes, policies, UI visibility, and compatibility behavior.

## Key Technical Changes
- Canonical RBAC seeding:
  - `database/seeders/PermissionSeeder.php`
  - `database/seeders/RoleSeeder.php`
  - `database/seeders/RolePermissionSeeder.php` (compatibility bridge)
  - `database/seeders/DatabaseSeeder.php` (seed pipeline wiring)
- Compatibility and governance:
  - `app/Providers/AppServiceProvider.php` (`Gate::before`, policy registration)
  - `app/Services/Admin/RoleSyncService.php`
  - `app/Services/Admin/UserManagementService.php`
- Shared content services:
  - `app/Services/Content/ContentAuthoringService.php`
  - `app/Services/Content/ContentAccessService.php`
  - `app/Services/ContentGovernanceService.php` integration for shared admin payload path
- Policy layer:
  - `app/Policies/ModulePolicy.php`
  - `app/Policies/LessonPolicy.php`
  - `app/Policies/TopicPolicy.php`
  - `app/Policies/QuizPolicy.php`
- Route and controller migration:
  - `routes/admin.php`
  - `routes/instructor.php`
  - `routes/web.php`
  - role/permission admin endpoints and permission-guarded mutation paths
  - permission/policy checks in admin/instructor/chat/auth/profile flows
- Blade visibility migration:
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/navigation.blade.php`
  - `resources/views/layouts/learner-header.blade.php`

## Test Coverage Added
- `tests/Feature/Rbac/RbacPermissionMatrixSmokeTest.php`
- `tests/Feature/Rbac/RbacRouteProtectionSmokeTest.php`
- `tests/Feature/Rbac/RbacLegacyRoleSyncSmokeTest.php`
- `tests/Feature/Rbac/RbacPermissionCatalogSeederTest.php`
- `tests/Feature/Rbac/RbacRoleCapabilityMatrixSeederTest.php`
- `tests/Feature/Rbac/RbacSeederIdempotencyTest.php`
- `tests/Feature/Rbac/RbacSuperAdminGateTest.php`
- `tests/Feature/Rbac/RbacLegacyRoleSyncTest.php`
- `tests/Feature/Rbac/RbacContentPolicyEnforcementTest.php`
- `tests/Feature/Rbac/RbacSharedContentDomainConsistencyTest.php`
- `tests/Feature/Rbac/RbacBladePermissionVisibilityTest.php`
- `tests/Feature/Rbac/RbacResidualRoleCheckRegressionTest.php`
- `tests/Feature/Admin/AdminRolePermissionManagementAuthorizationTest.php`
- `tests/Unit/Services/ContentAuthoringServiceTest.php`

## Verification Commands Run
- `php artisan test --filter=Rbac` -> PASS (16 tests, 100 assertions)
- `php artisan test --filter=AdminUsersAuthorizationTest` -> PASS (3 tests)
- `php artisan test --filter=InstructorModuleReviewSubmissionTest` -> PASS (3 tests)
- `php artisan test --filter=ChatRolePermissionMatrixTest` -> PASS (3 tests)
- `php artisan test --filter=InstructorModuleConfigValidationTest` -> PASS (4 tests)
- `php artisan test --filter=InstructorModuleGovernanceUiTest` -> PASS (1 test)
- `php artisan test --filter=InstructorSidebarRefinementTest` -> PASS (3 tests)
- `php artisan test --filter=LearnerSubscriptionParityTest` -> PASS (1 test)
- `php artisan test --filter=SubscriptionAndPaymentNotificationTest` -> PASS (3 tests)
- `php artisan test --filter=ContentGovernanceServiceTest` -> PASS (4 tests)
- `php artisan test --filter=AdminTableUxTest` -> PASS (1 test)
- `php artisan test --filter=InstructorDeleteConfirmationTest` -> PASS (1 test)
- `php artisan test --filter=ChatNotificationBadgeTest` -> PASS (4 tests)
- `php artisan test --filter=AdminUserManagementFeatureTest` -> PASS (2 tests)
- `php artisan test --filter=AdminUserRoleTransitionAuditTest` -> PASS (1 test)
- `php artisan test` -> PASS (488 tests, 1993 assertions)

## Stabilization Follow-up (2026-04-11)
- Restored missing learner subscription upgrade view contract (`resources/views/subscriptions/upgrade.blade.php`).
- Fixed instructor module payload normalization to persist `enrollment_limit` in shared authoring service.
- Restored expected UI hooks and controls for instructor/admin table and delete-confirmation test contracts.
- Added role unread-badge hook attributes for admin/instructor/learner layout/header chat badge contracts.
- Aligned governance and admin role-transition tests with current compatibility behavior (`users.role` legacy mapping + submitted->in_review workflow).

## Rollback Notes
- PR slice rollback order (latest to earliest):
  1. Controller and route permission migration.
  2. Policy registration and policy classes.
  3. Shared content services integration.
  4. Super-admin gate and legacy role sync service.
  5. Seeder pipeline changes.
- If rollback is required for RBAC behavior only, keep schema intact and revert application/service/route/view/test changes first.
- If seeding rollback is required, restore previous `RolePermissionSeeder` behavior and `DatabaseSeeder` call order together to avoid partial permission states.
