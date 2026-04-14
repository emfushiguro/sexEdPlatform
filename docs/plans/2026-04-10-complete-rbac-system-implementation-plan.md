# Complete RBAC System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement a complete permission-first RBAC system using Spatie Laravel Permission, consolidate shared content authorization behavior for admin and instructor flows, and preserve compatibility with existing route contracts and legacy role metadata.

**Architecture:** Use a phased compatibility-safe migration. Introduce canonical permission and role seeders, enforce action-level permissions in middleware, policies, controllers, and Blade views, and centralize shared content authorization logic. Keep route names/URLs stable and keep `users.role` synchronized during transition while Spatie remains the authorization source of truth.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS, Spatie Laravel Permission, PHPUnit

## Execution Outcome (2026-04-10)

- Task 1 to Task 14: Completed.
- Task 15: Full-suite verification completed and green after stabilization follow-up.
- Task 16: Documentation and handoff completed in `docs/changelogs/2026-04-10-complete-rbac-system.md`.

### Focused Verification Status

- `php artisan test --filter=Rbac` -> PASS
- `php artisan test --filter=AdminUsersAuthorizationTest` -> PASS
- `php artisan test --filter=InstructorModuleReviewSubmissionTest` -> PASS
- `php artisan test --filter=ChatRolePermissionMatrixTest` -> PASS

### Full Verification Status

- `php artisan test` -> PASS (488 tests, 1993 assertions).

---

## Execution Notes

- I'm using the writing-plans skill to create the implementation plan.
- Use TDD for each task: write failing test, run fail, implement minimal fix, run pass, commit.
- Keep controllers thin and domain logic in services/policies.
- Avoid destructive schema changes in this phase.
- Preserve route compatibility in `routes/admin.php`, `routes/instructor.php`, and `routes/web.php`.
- Use permission checks for actions. Do not add new role-string business checks.

## Task 1: Create a Focused RBAC Baseline Test Suite

**Files:**
- Create: `tests/Feature/Rbac/RbacPermissionMatrixSmokeTest.php`
- Create: `tests/Feature/Rbac/RbacRouteProtectionSmokeTest.php`
- Create: `tests/Feature/Rbac/RbacLegacyRoleSyncSmokeTest.php`

**Step 1: Write the failing test**

Add smoke tests that assert:
- admin can pass a representative privileged action by permission
- instructor cannot access publish/review actions
- learner cannot access instructor/admin actions
- parent can access monitoring actions only

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacPermissionMatrixSmokeTest`  
Expected: FAIL due to missing canonical seeding and updated permission guards.

**Step 3: Write minimal implementation**

Only scaffold the test classes and assertions. Do not change app logic yet.

**Step 4: Run test to verify failure is deterministic**

Run: `php artisan test --filter=RbacRouteProtectionSmokeTest`  
Expected: FAIL with clear authorization mismatch output.

**Step 5: Commit**

```bash
git add tests/Feature/Rbac/RbacPermissionMatrixSmokeTest.php tests/Feature/Rbac/RbacRouteProtectionSmokeTest.php tests/Feature/Rbac/RbacLegacyRoleSyncSmokeTest.php
git commit -m "test: add RBAC baseline smoke coverage"
```

## Task 2: Add Canonical Permission Seeder

**Files:**
- Create: `database/seeders/PermissionSeeder.php`
- Modify: `database/seeders/RolePermissionSeeder.php`
- Test: `tests/Feature/Rbac/RbacPermissionCatalogSeederTest.php`

**Step 1: Write the failing test**

Create a test asserting the canonical permission catalog exists after seeding and that legacy stable permissions still exist.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacPermissionCatalogSeederTest`  
Expected: FAIL because `PermissionSeeder` does not exist yet.

**Step 3: Write minimal implementation**

Implement `PermissionSeeder` with:
- cache reset via `PermissionRegistrar`
- canonical permission list using `Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'])`
- compatibility aliases for existing stable names

Keep `RolePermissionSeeder` as a temporary bridge but reduce responsibility to avoid duplication with new canonical seeder.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RbacPermissionCatalogSeederTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add database/seeders/PermissionSeeder.php database/seeders/RolePermissionSeeder.php tests/Feature/Rbac/RbacPermissionCatalogSeederTest.php
git commit -m "feat: add canonical permission seeder"
```

## Task 3: Add Role Seeder and Capability Matrix Assignment

**Files:**
- Create: `database/seeders/RoleSeeder.php`
- Modify: `database/seeders/PermissionSeeder.php`
- Test: `tests/Feature/Rbac/RbacRoleCapabilityMatrixSeederTest.php`

**Step 1: Write the failing test**

Assert roles exist (`admin`, `instructor`, `learner`, `parent`) and each role has the expected capability boundaries.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacRoleCapabilityMatrixSeederTest`  
Expected: FAIL because role matrix seeding is not finalized.

**Step 3: Write minimal implementation**

Implement `RoleSeeder`:
- `admin` receives all permissions
- `instructor` receives content-authoring and learner-operations permissions, excludes review/publish and governance/billing
- `learner` receives consumption permissions
- `parent` receives monitoring permissions

Use `syncPermissions` to keep deterministic state.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RbacRoleCapabilityMatrixSeederTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add database/seeders/RoleSeeder.php database/seeders/PermissionSeeder.php tests/Feature/Rbac/RbacRoleCapabilityMatrixSeederTest.php
git commit -m "feat: add RBAC role capability seeder"
```

## Task 4: Register Seeder Pipeline and Transition Compatibility

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `database/seeders/RolePermissionSeeder.php`
- Test: `tests/Feature/Rbac/RbacSeederIdempotencyTest.php`

**Step 1: Write the failing test**

Test seeding twice and assert permission/role counts and key assignments remain stable.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacSeederIdempotencyTest`  
Expected: FAIL due to old mixed seeder behavior.

**Step 3: Write minimal implementation**

Update `DatabaseSeeder` call order:
1. `PermissionSeeder`
2. `RoleSeeder`
3. transitional `RolePermissionSeeder` compatibility path as needed
4. existing required app seeders

Ensure no duplicate grants.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RbacSeederIdempotencyTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add database/seeders/DatabaseSeeder.php database/seeders/RolePermissionSeeder.php tests/Feature/Rbac/RbacSeederIdempotencyTest.php
git commit -m "refactor: wire deterministic RBAC seed pipeline"
```

## Task 5: Add Super-Admin Gate and Role Sync Compatibility Service

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `app/Services/Admin/RoleSyncService.php`
- Modify: `app/Services/Admin/UserManagementService.php`
- Test: `tests/Feature/Rbac/RbacSuperAdminGateTest.php`
- Test: `tests/Feature/Rbac/RbacLegacyRoleSyncTest.php`

**Step 1: Write the failing test**

Add tests for:
- `Gate::before` admin allow behavior
- `users.role` stays synced when role assignment changes through admin workflows

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacSuperAdminGateTest`  
Expected: FAIL because gate-before behavior is missing.

Run: `php artisan test --filter=RbacLegacyRoleSyncTest`  
Expected: FAIL because compatibility sync is not centralized.

**Step 3: Write minimal implementation**

- Add `Gate::before` in `AppServiceProvider` for users with admin role.
- Add `RoleSyncService` for syncing `users.role` after role assignment updates.
- Integrate sync service in `UserManagementService` role mutation paths.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=RbacSuperAdminGateTest`  
Run: `php artisan test --filter=RbacLegacyRoleSyncTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add app/Providers/AppServiceProvider.php app/Services/Admin/RoleSyncService.php app/Services/Admin/UserManagementService.php tests/Feature/Rbac/RbacSuperAdminGateTest.php tests/Feature/Rbac/RbacLegacyRoleSyncTest.php
git commit -m "feat: add super-admin gate and legacy role sync"
```

## Task 6: Migrate Route Protection to Permission-First Matrix

**Files:**
- Modify: `routes/admin.php`
- Modify: `routes/instructor.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Rbac/RbacRouteProtectionSmokeTest.php`
- Test: `tests/Feature/Chat/ChatRolePermissionMatrixTest.php`

**Step 1: Write the failing test**

Extend route tests to verify:
- instructor content actions are permission gated
- admin can enter instructor tools through permission
- chat routes use permission middleware behavior
- parent routes require monitoring permissions plus existing relationship constraints

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacRouteProtectionSmokeTest`  
Expected: FAIL with old role-only constraints.

**Step 3: Write minimal implementation**

- Keep route names and URLs unchanged.
- Keep admin shell boundary.
- Apply permission middleware for action groups.
- Replace chat role-list route middleware with permission middleware.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=RbacRouteProtectionSmokeTest`  
Run: `php artisan test --filter=ChatRolePermissionMatrixTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add routes/admin.php routes/instructor.php routes/web.php tests/Feature/Rbac/RbacRouteProtectionSmokeTest.php tests/Feature/Chat/ChatRolePermissionMatrixTest.php
git commit -m "refactor: apply permission-first route protection"
```

## Task 7: Add Content Policies for Resource-Level Authorization

**Files:**
- Create: `app/Policies/ModulePolicy.php`
- Create: `app/Policies/LessonPolicy.php`
- Create: `app/Policies/TopicPolicy.php`
- Create: `app/Policies/QuizPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/Rbac/RbacContentPolicyEnforcementTest.php`

**Step 1: Write the failing test**

Add tests for owner and non-owner behavior with permission boundaries for module/lesson/topic/quiz actions.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacContentPolicyEnforcementTest`  
Expected: FAIL because policies do not exist.

**Step 3: Write minimal implementation**

Implement policies with:
- permission checks by action
- ownership checks using `created_by` and related module ownership
- governance checks for publish/review actions

Register policies using gate registration approach available in current provider flow.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RbacContentPolicyEnforcementTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add app/Policies/ModulePolicy.php app/Policies/LessonPolicy.php app/Policies/TopicPolicy.php app/Policies/QuizPolicy.php app/Providers/AppServiceProvider.php tests/Feature/Rbac/RbacContentPolicyEnforcementTest.php
git commit -m "feat: add content resource policies for RBAC"
```

## Task 8: Refactor Content Controllers to Authorize by Permission and Policy

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminModuleController.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/LessonController.php`
- Modify: `app/Http/Controllers/Instructor/TopicController.php`
- Modify: `app/Http/Controllers/Instructor/QuizManagementController.php`
- Test: `tests/Feature/Instructor/InstructorModuleReviewSubmissionTest.php`
- Test: `tests/Feature/Admin/AdminContentReviewWorkflowTest.php`

**Step 1: Write the failing test**

Expand feature tests to prove:
- authorized users can perform expected content actions
- unauthorized users are blocked despite route access
- instructor cannot publish
- admin can publish/review

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorModuleReviewSubmissionTest`  
Run: `php artisan test --filter=AdminContentReviewWorkflowTest`  
Expected: FAIL due to mixed role and implicit checks.

**Step 3: Write minimal implementation**

Add `authorize(...)` calls and remove role-string action decisions in these controllers. Keep ownership checks aligned with policies and existing governance services.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=InstructorModuleReviewSubmissionTest`  
Run: `php artisan test --filter=AdminContentReviewWorkflowTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/AdminModuleController.php app/Http/Controllers/Instructor/ModuleController.php app/Http/Controllers/Instructor/LessonController.php app/Http/Controllers/Instructor/TopicController.php app/Http/Controllers/Instructor/QuizManagementController.php tests/Feature/Instructor/InstructorModuleReviewSubmissionTest.php tests/Feature/Admin/AdminContentReviewWorkflowTest.php
git commit -m "refactor: enforce permission-policy auth in content controllers"
```

## Task 9: Consolidate Shared Content Authorization and Ownership Logic in Service Layer

**Files:**
- Create: `app/Services/Content/ContentAccessService.php`
- Create: `app/Services/Content/ContentAuthoringService.php`
- Modify: `app/Services/ContentGovernanceService.php`
- Modify: `app/Http/Controllers/Admin/AdminModuleController.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Test: `tests/Unit/Services/ContentAuthoringServiceTest.php`
- Test: `tests/Feature/Rbac/RbacSharedContentDomainConsistencyTest.php`

**Step 1: Write the failing test**

Add service tests for shared ownership and action handling, then feature tests to confirm admin and instructor controllers resolve to consistent domain behavior.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ContentAuthoringServiceTest`  
Expected: FAIL because shared authoring service is missing.

**Step 3: Write minimal implementation**

- Introduce shared service methods for create/update/ownership checks.
- Wire both admin and instructor module flows through shared service methods.
- Keep route contracts unchanged.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ContentAuthoringServiceTest`  
Run: `php artisan test --filter=RbacSharedContentDomainConsistencyTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add app/Services/Content/ContentAccessService.php app/Services/Content/ContentAuthoringService.php app/Services/ContentGovernanceService.php app/Http/Controllers/Admin/AdminModuleController.php app/Http/Controllers/Instructor/ModuleController.php tests/Unit/Services/ContentAuthoringServiceTest.php tests/Feature/Rbac/RbacSharedContentDomainConsistencyTest.php
git commit -m "feat: consolidate shared content auth and ownership services"
```

## Task 10: Migrate Chat Authorization to Permission-First

**Files:**
- Modify: `app/Http/Controllers/Chat/ConversationController.php`
- Modify: `app/Http/Controllers/Chat/MessageController.php`
- Modify: `app/Http/Controllers/Chat/MessageRequestController.php`
- Modify: `app/Services/Chat/ChatService.php`
- Test: `tests/Feature/Chat/ChatRolePermissionMatrixTest.php`
- Test: `tests/Feature/Chat/ChatHttpFlowTest.php`

**Step 1: Write the failing test**

Add assertions that chat actions are permission-gated and no privileged chat behavior depends on role strings.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ChatRolePermissionMatrixTest`  
Expected: FAIL due to current role checks.

**Step 3: Write minimal implementation**

Refactor chat controllers/services:
- replace role-string branching for privileged actions with permission checks
- keep participant validation intact

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ChatRolePermissionMatrixTest`  
Run: `php artisan test --filter=ChatHttpFlowTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/Chat/ConversationController.php app/Http/Controllers/Chat/MessageController.php app/Http/Controllers/Chat/MessageRequestController.php app/Services/Chat/ChatService.php tests/Feature/Chat/ChatRolePermissionMatrixTest.php tests/Feature/Chat/ChatHttpFlowTest.php
git commit -m "refactor: migrate chat authorization to permission-first"
```

## Task 11: Permission-Protect Admin Role and Permission Management Surface

**Files:**
- Modify: `app/Http/Controllers/Admin/UserAdminController.php`
- Create: `app/Http/Controllers/Admin/RoleAdminController.php`
- Create: `app/Http/Controllers/Admin/PermissionAdminController.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Admin/AdminUsersAuthorizationTest.php`
- Create: `tests/Feature/Admin/AdminRolePermissionManagementAuthorizationTest.php`

**Step 1: Write the failing test**

Add tests for:
- role assignment requires `assign roles`
- permission mapping requires `manage permissions`
- unauthorized actors receive 403

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminRolePermissionManagementAuthorizationTest`  
Expected: FAIL because dedicated management endpoints/controllers are missing.

**Step 3: Write minimal implementation**

Create thin controllers for role/permission admin operations and enforce permission checks on every mutation endpoint.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=AdminUsersAuthorizationTest`  
Run: `php artisan test --filter=AdminRolePermissionManagementAuthorizationTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/UserAdminController.php app/Http/Controllers/Admin/RoleAdminController.php app/Http/Controllers/Admin/PermissionAdminController.php routes/admin.php tests/Feature/Admin/AdminUsersAuthorizationTest.php tests/Feature/Admin/AdminRolePermissionManagementAuthorizationTest.php
git commit -m "feat: add permission-guarded admin role management endpoints"
```

## Task 12: Migrate Blade Navigation and Action Visibility to Permission Checks

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/layouts/navigation.blade.php`
- Modify: `resources/views/layouts/learner-header.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Rbac/RbacBladePermissionVisibilityTest.php`

**Step 1: Write the failing test**

Add view rendering tests that assert critical navigation items/actions are visible only when user has required permissions.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacBladePermissionVisibilityTest`  
Expected: FAIL because role-based checks still exist.

**Step 3: Write minimal implementation**

Replace role-based action visibility conditions with permission checks while preserving existing layout structure and copy.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RbacBladePermissionVisibilityTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add resources/views/layouts/app.blade.php resources/views/layouts/navigation.blade.php resources/views/layouts/learner-header.blade.php resources/views/layouts/admin.blade.php tests/Feature/Rbac/RbacBladePermissionVisibilityTest.php
git commit -m "refactor: apply permission-based blade visibility checks"
```

## Task 13: Replace Residual Role-String Authorization Checks in Critical Flows

**Files:**
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Modify: `app/Http/Controllers/Auth/AdminAuthController.php`
- Modify: `app/Http/Controllers/Auth/InstructorAuthController.php`
- Modify: `app/Http/Controllers/Learner/ProfileCompletionController.php`
- Modify: `app/Http/Controllers/Learner/InstructorProfileController.php`
- Test: `tests/Feature/Rbac/RbacResidualRoleCheckRegressionTest.php`

**Step 1: Write the failing test**

Add targeted regression tests for critical login/profile routes to assert authorization behavior works with permission-first strategy and does not depend on direct role-string business checks.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RbacResidualRoleCheckRegressionTest`  
Expected: FAIL due to current direct role checks.

**Step 3: Write minimal implementation**

Refactor only authorization-sensitive role checks in these flows to use role assignment APIs and permissions as appropriate, while preserving route redirects and user experience.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RbacResidualRoleCheckRegressionTest`  
Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/Auth/AuthenticatedSessionController.php app/Http/Controllers/Auth/AdminAuthController.php app/Http/Controllers/Auth/InstructorAuthController.php app/Http/Controllers/Learner/ProfileCompletionController.php app/Http/Controllers/Learner/InstructorProfileController.php tests/Feature/Rbac/RbacResidualRoleCheckRegressionTest.php
git commit -m "refactor: remove residual role-string authorization checks"
```

## Task 14: Run Focused Verification Pack (Phase B)

**Files:**
- Modify (if needed): `tests/Feature/Rbac/*.php`
- Modify (if needed): `tests/Feature/Chat/*.php`
- Modify (if needed): `tests/Feature/Admin/*.php`
- Modify (if needed): `tests/Feature/Instructor/*.php`

**Step 1: Run focused suite**

Run:
- `php artisan test --filter=Rbac`
- `php artisan test --filter=AdminUsersAuthorizationTest`
- `php artisan test --filter=InstructorModuleReviewSubmissionTest`
- `php artisan test --filter=ChatRolePermissionMatrixTest`

Expected: PASS for focused RBAC + regression gates.

**Step 2: Fix only failing cases related to this scope**

Keep fixes scoped and minimal.

**Step 3: Re-run focused suite**

Expected: all selected suites PASS.

**Step 4: Commit**

```bash
git add tests
git commit -m "test: stabilize focused RBAC verification pack"
```

## Task 15: Run Full Verification Pack (Phase C)

**Files:**
- No required file changes unless regressions are found.

**Step 1: Run full suite**

Run: `php artisan test`

Expected: PASS

**Step 2: If regressions exist, fix and rerun**

Apply minimal corrective changes and rerun full suite.

**Step 3: Commit final stabilization**

```bash
git add .
git commit -m "chore: finalize complete RBAC rollout verification"
```

## Task 16: Documentation and Rollout Handoff

**Files:**
- Modify: `docs/changelogs/2026-04-10-complete-rbac-system.md`
- Modify: `docs/plans/2026-04-10-complete-rbac-system-design.md`
- Modify: `docs/plans/2026-04-10-complete-rbac-system-implementation-plan.md`

**Step 1: Write rollout summary**

Include:
- permission catalog changes
- route protection changes
- policy/controller migration summary
- compatibility notes for `users.role`

**Step 2: Add rollback notes**

Document how to rollback each PR phase safely.

**Step 3: Commit docs**

```bash
git add docs/changelogs/2026-04-10-complete-rbac-system.md docs/plans/2026-04-10-complete-rbac-system-design.md docs/plans/2026-04-10-complete-rbac-system-implementation-plan.md
git commit -m "docs: add complete RBAC rollout changelog and handoff"
```

---

## PR Slicing Recommendation

1. PR1: Tasks 1 to 4
2. PR2: Tasks 5 to 7
3. PR3: Tasks 8 to 9
4. PR4: Tasks 10 to 12
5. PR5: Tasks 13 to 16

## Completion Gate

Do not mark complete until:

1. Focused suite (Phase B) is green.
2. Full suite (Phase C) is green.
3. Route compatibility is preserved.
4. Admin, instructor, learner, and parent capability boundaries match the approved matrix.
5. Action-level authorization no longer depends on direct role-string checks.
