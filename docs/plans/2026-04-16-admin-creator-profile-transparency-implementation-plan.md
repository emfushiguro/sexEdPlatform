# Admin Creator Profile Transparency Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a dedicated admin creator profile domain with secure edit capabilities and learner-facing ownership transparency using a View Full Information page flow.

**Architecture:** Additive domain-first implementation with a dedicated admin creator profile table, thin controllers, Form Request validation, service-layer ownership display normalization, and learner module UI integration that mirrors existing instructor information interaction patterns.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, Spatie Permission, PHPUnit.

---

## Task 1: Add Admin Creator Profile Schema

**Files:**
- Create: database/migrations/2026_04_16_000100_create_admin_creator_profiles_table.php

**Step 1: Write the failing test**
- Create migration-focused feature test assertions that expect the new table and required columns.

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=AdminCreatorProfileSchemaTest
- Expected: FAIL because table does not exist.

**Step 3: Write minimal implementation**
- Add additive migration with:
  - user_id unique foreign key
  - public_display_name
  - bio nullable
  - affiliation
  - avatar_path nullable
  - show_individual_attribution boolean default false
  - timestamps

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=AdminCreatorProfileSchemaTest
- Expected: PASS.

**Step 5: Commit**
- git add database/migrations/2026_04_16_000100_create_admin_creator_profiles_table.php tests/Feature/Admin/AdminCreatorProfileSchemaTest.php
- git commit -m "feat: add admin creator profile schema"

## Task 2: Add Model and User Relationships

**Files:**
- Create: app/Models/AdminCreatorProfile.php
- Modify: app/Models/User.php

**Step 1: Write the failing test**
- Add relationship test cases for:
  - user hasOne adminCreatorProfile
  - admin creator profile belongsTo user

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=AdminCreatorProfileRelationshipTest
- Expected: FAIL because model or relation is missing.

**Step 3: Write minimal implementation**
- Add fillable and casts on AdminCreatorProfile model.
- Add relation on User model:
  - adminCreatorProfile

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=AdminCreatorProfileRelationshipTest
- Expected: PASS.

**Step 5: Commit**
- git add app/Models/AdminCreatorProfile.php app/Models/User.php tests/Feature/Admin/AdminCreatorProfileRelationshipTest.php
- git commit -m "feat: add admin creator profile model and relationships"

## Task 3: Add Form Request Validation

**Files:**
- Create: app/Http/Requests/Admin/UpdateAdminCreatorProfileRequest.php

**Step 1: Write the failing test**
- Add validation tests for:
  - required public_display_name
  - required affiliation
  - valid image constraints for avatar
  - boolean handling for show_individual_attribution

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=UpdateAdminCreatorProfileRequestTest
- Expected: FAIL because request class is missing.

**Step 3: Write minimal implementation**
- Implement authorize true.
- Implement validation rules aligned to existing profile standards.

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=UpdateAdminCreatorProfileRequestTest
- Expected: PASS.

**Step 5: Commit**
- git add app/Http/Requests/Admin/UpdateAdminCreatorProfileRequest.php tests/Feature/Admin/UpdateAdminCreatorProfileRequestTest.php
- git commit -m "feat: add admin creator profile update validation"

## Task 4: Add Service Layer for Profile and Ownership Display

**Files:**
- Create: app/Services/Admin/AdminCreatorProfileService.php
- Create: app/Services/Content/AdminOwnershipDisplayService.php

**Step 1: Write the failing test**
- Add service tests for:
  - profile upsert behavior
  - avatar path replacement behavior
  - fallback DTO for missing profile
  - team-first display with optional individual attribution

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=AdminCreatorProfileServiceTest
- Run: php artisan test --filter=AdminOwnershipDisplayServiceTest
- Expected: FAIL because services do not exist.

**Step 3: Write minimal implementation**
- AdminCreatorProfileService:
  - getOrCreateForUser
  - updateFromValidatedPayload
- AdminOwnershipDisplayService:
  - normalize owner display data for module contexts
  - team-first fallback behavior

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=AdminCreatorProfileServiceTest
- Run: php artisan test --filter=AdminOwnershipDisplayServiceTest
- Expected: PASS.

**Step 5: Commit**
- git add app/Services/Admin/AdminCreatorProfileService.php app/Services/Content/AdminOwnershipDisplayService.php tests/Feature/Admin/AdminCreatorProfileServiceTest.php tests/Feature/Learner/AdminOwnershipDisplayServiceTest.php
- git commit -m "feat: add admin creator profile and ownership display services"

## Task 5: Extend Admin Profile Controller and Admin Profile Routes

**Files:**
- Modify: app/Http/Controllers/Admin/AdminProfileController.php
- Modify: routes/admin.php

**Step 1: Write the failing test**
- Add admin feature tests for:
  - show admin profile page includes admin creator profile data
  - edit page accessible to admin
  - update profile persists allowed fields
  - restricted fields are not mutable

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=AdminProfileControllerTest
- Expected: FAIL due missing edit update endpoints.

**Step 3: Write minimal implementation**
- Add edit and update methods.
- Wire request validation and service calls.
- Preserve thin-controller orchestration pattern.

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=AdminProfileControllerTest
- Expected: PASS.

**Step 5: Commit**
- git add app/Http/Controllers/Admin/AdminProfileController.php routes/admin.php tests/Feature/Admin/AdminProfileControllerTest.php
- git commit -m "feat: add admin creator profile edit and update flow"

## Task 6: Add Admin Profile Edit View and Enhance Admin Profile Show View

**Files:**
- Modify: resources/views/admin/profile/show.blade.php
- Create: resources/views/admin/profile/edit.blade.php

**Step 1: Write the failing test**
- Add view tests asserting:
  - edit form fields exist
  - role permissions are read-only or absent from editable form
  - success feedback appears after update

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=AdminProfileViewTest
- Expected: FAIL due missing edit view structure.

**Step 3: Write minimal implementation**
- Add professional profile edit page with:
  - avatar upload + preview
  - public display name
  - bio
  - affiliation
  - attribution toggle
- Keep styling and spacing consistent with existing profile conventions.

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=AdminProfileViewTest
- Expected: PASS.

**Step 5: Commit**
- git add resources/views/admin/profile/show.blade.php resources/views/admin/profile/edit.blade.php tests/Feature/Admin/AdminProfileViewTest.php
- git commit -m "feat: add admin profile edit UI"

## Task 7: Add Learner-Facing Admin Creator Public Controller and Route

**Files:**
- Create: app/Http/Controllers/Learner/AdminCreatorProfileController.php
- Modify: routes/web.php

**Step 1: Write the failing test**
- Add learner feature tests for:
  - admin creator public page route resolves
  - only whitelisted fields are shown
  - non-admin target returns 404

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=LearnerAdminCreatorProfilePageTest
- Expected: FAIL due missing controller and route.

**Step 3: Write minimal implementation**
- Add controller show method using safe projection.
- Add learner route parallel to instructor profile route style.

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=LearnerAdminCreatorProfilePageTest
- Expected: PASS.

**Step 5: Commit**
- git add app/Http/Controllers/Learner/AdminCreatorProfileController.php routes/web.php tests/Feature/Learner/LearnerAdminCreatorProfilePageTest.php
- git commit -m "feat: add learner-facing admin creator information page"

## Task 8: Add Public Admin Creator View

**Files:**
- Create: resources/views/learner/admin-creators/show.blade.php

**Step 1: Write the failing test**
- Add view assertions for:
  - header identity elements
  - contribution summary blocks
  - View Full Information page semantics

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=AdminCreatorPublicViewTest
- Expected: FAIL because view does not exist.

**Step 3: Write minimal implementation**
- Build public information page with:
  - role label Platform Developer
  - display name avatar affiliation bio
  - modules published latest module learners reached

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=AdminCreatorPublicViewTest
- Expected: PASS.

**Step 5: Commit**
- git add resources/views/learner/admin-creators/show.blade.php tests/Feature/Learner/AdminCreatorPublicViewTest.php
- git commit -m "feat: add admin creator public info view"

## Task 9: Integrate Admin Creator Information Into Learner Module Overview

**Files:**
- Modify: resources/views/learner/modules/show.blade.php
- Create: resources/views/learner/modules/partials/admin-creator-info-card.blade.php
- Modify: resources/views/components/learner/module-card-active.blade.php
- Modify: resources/views/components/learner/module-card-recommended.blade.php
- Modify: resources/views/learner/modules/index.blade.php

**Step 1: Write the failing test**
- Add learner module surface tests for:
  - admin-owned module shows admin creator information panel
  - CTA uses View Full Information page text
  - instructor-owned module remains unchanged
  - fallback to team avatar and team name when profile incomplete

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=LearnerModuleOwnershipTransparencyTest
- Expected: FAIL due missing admin info card integration.

**Step 3: Write minimal implementation**
- Integrate owner-type switch logic.
- Render admin creator partial for admin-owned modules.
- Keep instructor info flow untouched for instructor-owned modules.

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=LearnerModuleOwnershipTransparencyTest
- Expected: PASS.

**Step 5: Commit**
- git add resources/views/learner/modules/show.blade.php resources/views/learner/modules/partials/admin-creator-info-card.blade.php resources/views/components/learner/module-card-active.blade.php resources/views/components/learner/module-card-recommended.blade.php resources/views/learner/modules/index.blade.php tests/Feature/Learner/LearnerModuleOwnershipTransparencyTest.php
- git commit -m "feat: add admin creator transparency on learner module surfaces"

## Task 10: Add Authorization Policy Coverage

**Files:**
- Create: app/Policies/AdminCreatorProfilePolicy.php
- Modify: app/Providers/AuthServiceProvider.php

**Step 1: Write the failing test**
- Add policy tests for:
  - owner can update own profile
  - non-owner cannot update
  - public page access projection is safe

**Step 2: Run test to verify it fails**
- Run: php artisan test --filter=AdminCreatorProfilePolicyTest
- Expected: FAIL because policy is missing.

**Step 3: Write minimal implementation**
- Add policy and register mapping.
- Apply authorization checks in controller update path.

**Step 4: Run test to verify it passes**
- Run: php artisan test --filter=AdminCreatorProfilePolicyTest
- Expected: PASS.

**Step 5: Commit**
- git add app/Policies/AdminCreatorProfilePolicy.php app/Providers/AuthServiceProvider.php tests/Feature/Admin/AdminCreatorProfilePolicyTest.php
- git commit -m "feat: add admin creator profile authorization policy"

## Task 11: Run Focused Verification Suite

**Files:**
- Modify: docs/changelogs/2026-04-16-admin-creator-profile-transparency.md

**Step 1: Run focused tests**
- php artisan test --filter=AdminCreatorProfileSchemaTest
- php artisan test --filter=AdminCreatorProfileRelationshipTest
- php artisan test --filter=UpdateAdminCreatorProfileRequestTest
- php artisan test --filter=AdminCreatorProfileServiceTest
- php artisan test --filter=AdminOwnershipDisplayServiceTest
- php artisan test --filter=AdminProfileControllerTest
- php artisan test --filter=AdminProfileViewTest
- php artisan test --filter=LearnerAdminCreatorProfilePageTest
- php artisan test --filter=AdminCreatorPublicViewTest
- php artisan test --filter=LearnerModuleOwnershipTransparencyTest
- php artisan test --filter=AdminCreatorProfilePolicyTest

Expected: PASS for all focused tests.

**Step 2: Run regression anchors**
- php artisan test --filter=AdminModuleAuthoringWorkflowTest
- php artisan test --filter=LearnerModulePageTest
- php artisan test --filter=InstructorProfileControllerTest

Expected: PASS to confirm no ownership display regressions.

**Step 3: Manual QA checklist**
- Verify admin edit profile UX and field restrictions.
- Verify View Full Information page route for admin-owned modules.
- Verify instructor-owned surfaces remain unchanged.
- Verify fallback behavior with incomplete admin creator profiles.

**Step 4: Commit**
- git add docs/changelogs/2026-04-16-admin-creator-profile-transparency.md
- git commit -m "docs: add admin creator profile transparency verification report"

---

Plan complete and saved to docs/plans/2026-04-16-admin-creator-profile-transparency-implementation-plan.md.

Two execution options:

1. Subagent-Driven (this session) - dispatch a fresh subagent per task with review checkpoints between tasks.
2. Parallel Session (separate) - open a dedicated execution session and run the plan in larger task batches.

Which approach?