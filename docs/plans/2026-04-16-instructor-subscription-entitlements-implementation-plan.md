# Instructor Subscription Entitlements and Plan Management Integration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement instructor subscription entitlements with dynamic admin-configurable limits and monetization permissions, including free baseline support, service-layer enforcement, and instructor upgrade UX.

**Architecture:** Reuse the existing subscription and entitlement domain as the single authority, add instructor capability resolution on top of plan audience filtering, and enforce limits in service and runtime flows. Keep controllers thin, validation in Form Requests, and UI synchronized with backend decision outcomes.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, PHPUnit.

---

I'm using the writing-plans skill to create the implementation plan.

## Task 1: Add canonical instructor entitlement keys and aliases

**Files:**
- Modify: `app/Support/SubscriptionFeatureKeys.php`
- Modify: `app/Services/SubscriptionService.php`
- Test: `tests/Unit/Services/SubscriptionServiceFeatureEntitlementTest.php`

**Step 1: Write the failing test**
Add tests asserting canonical instructor keys are readable and optional legacy aliases still resolve during transition.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=SubscriptionServiceFeatureEntitlementTest`
Expected: FAIL with missing instructor key coverage.

**Step 3: Write minimal implementation**
Add canonical constants and alias mapping for instructor feature keys in the existing entitlement resolution path.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=SubscriptionServiceFeatureEntitlementTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Support/SubscriptionFeatureKeys.php app/Services/SubscriptionService.php tests/Unit/Services/SubscriptionServiceFeatureEntitlementTest.php
git commit -m "feat(subscription): add canonical instructor entitlement keys"
```

## Task 2: Ensure instructor feature catalog keys exist and are admin-visible

**Files:**
- Modify: `database/seeders/FeatureCatalogSeeder.php`
- Modify: `app/Http/Controllers/Admin/SubscriptionPlanAdminController.php`
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Test: `tests/Feature/Admin/AdminInstructorPlanEntitlementDefaultsTest.php`

**Step 1: Write the failing test**
Add assertions that admin subscription plan UI and feature API expose instructor canonical quota and boolean keys.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=AdminInstructorPlanEntitlementDefaultsTest`
Expected: FAIL because required instructor keys are missing or not surfaced.

**Step 3: Write minimal implementation**
Add or upsert instructor feature catalog entries and ensure plan wizard displays instructor audience features correctly.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=AdminInstructorPlanEntitlementDefaultsTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add database/seeders/FeatureCatalogSeeder.php app/Http/Controllers/Admin/SubscriptionPlanAdminController.php resources/views/admin/subscription-plans/index.blade.php tests/Feature/Admin/AdminInstructorPlanEntitlementDefaultsTest.php
git commit -m "feat(admin-plans): expose instructor entitlement catalog defaults"
```

## Task 3: Add instructor capability resolution service with free baseline fallback

**Files:**
- Create: `app/Services/Instructor/InstructorPlanCapabilityService.php`
- Modify: `app/Services/EntitlementService.php`
- Test: `tests/Unit/Services/InstructorPlanCapabilityServiceTest.php`

**Step 1: Write the failing test**
Cover:
- paid instructor plan resolution
- fallback to free instructor baseline plan
- cap resolution by module access type
- split monetization booleans

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=InstructorPlanCapabilityServiceTest`
Expected: FAIL because service does not yet exist.

**Step 3: Write minimal implementation**
Implement capability service that resolves plan audience, reads entitlements, and returns normalized capability values and usage snapshot.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=InstructorPlanCapabilityServiceTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Services/Instructor/InstructorPlanCapabilityService.php app/Services/EntitlementService.php tests/Unit/Services/InstructorPlanCapabilityServiceTest.php
git commit -m "feat(instructor): add plan capability resolution with free baseline fallback"
```

## Task 4: Enforce dynamic enrollment caps at request validation and authoring payload level

**Files:**
- Modify: `app/Http/Requests/Instructor/StoreModuleRequest.php`
- Modify: `app/Http/Requests/Instructor/UpdateModuleRequest.php`
- Modify: `app/Services/Content/ContentAuthoringService.php`
- Test: `tests/Feature/Instructor/InstructorModuleConfigValidationTest.php`

**Step 1: Write the failing test**
Update validation tests to assert enrollment limits are constrained by resolved instructor plan capability rather than fixed cap.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=InstructorModuleConfigValidationTest`
Expected: FAIL due fixed cap assumptions.

**Step 3: Write minimal implementation**
Replace fixed max cap validation with plan-resolved cap checks and keep payload normalization behavior intact.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=InstructorModuleConfigValidationTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Requests/Instructor/StoreModuleRequest.php app/Http/Requests/Instructor/UpdateModuleRequest.php app/Services/Content/ContentAuthoringService.php tests/Feature/Instructor/InstructorModuleConfigValidationTest.php
git commit -m "feat(instructor-modules): enforce dynamic enrollment limit validation"
```

## Task 5: Enforce published module quota at publish and approval transition

**Files:**
- Modify: `app/Services/ContentGovernanceService.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/ModuleReviewController.php`
- Test: `tests/Feature/Instructor/InstructorModulePlanEnforcementTest.php`

**Step 1: Write the failing test**
Add tests verifying:
- publish/approval transition is blocked when published quota is reached
- actionable error feedback is returned
- draft and review preparation behavior remains unaffected

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=InstructorModulePlanEnforcementTest`
Expected: FAIL because quota is not yet enforced.

**Step 3: Write minimal implementation**
Add capability checks at publish and approval transition paths, return clear blocking reason and upgrade context.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=InstructorModulePlanEnforcementTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Services/ContentGovernanceService.php app/Http/Controllers/Instructor/ModuleController.php app/Http/Controllers/Instructor/ModuleReviewController.php tests/Feature/Instructor/InstructorModulePlanEnforcementTest.php
git commit -m "feat(instructor-governance): enforce published module quota on publish transitions"
```

## Task 6: Enforce paid module monetization split permissions

**Files:**
- Modify: `app/Services/Content/ContentAuthoringService.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/ModuleEarningsController.php`
- Test: `tests/Feature/Instructor/InstructorPaidModuleEntitlementTest.php`
- Create: `tests/Feature/Learner/LearnerEnrollmentCapByInstructorPlanTest.php`

**Step 1: Write the failing tests**
Cover:
- instructor paid publish blocked when entitlement is off
- paid enrollments blocked when receive-paid-entrollments entitlement is off
- earnings visibility gated by dedicated entitlement

**Step 2: Run tests to verify they fail**
Run: `php artisan test --filter=InstructorPaidModuleEntitlementTest`
Run: `php artisan test --filter=LearnerEnrollmentCapByInstructorPlanTest`
Expected: FAIL for missing split permission enforcement.

**Step 3: Write minimal implementation**
Add split permission checks in authoring, learner purchase/enrollment runtime, and instructor earnings surface.

**Step 4: Run tests to verify they pass**
Run: `php artisan test --filter=InstructorPaidModuleEntitlementTest`
Run: `php artisan test --filter=LearnerEnrollmentCapByInstructorPlanTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Services/Content/ContentAuthoringService.php app/Http/Controllers/Learner/ModuleController.php app/Http/Controllers/Instructor/ModuleEarningsController.php tests/Feature/Instructor/InstructorPaidModuleEntitlementTest.php tests/Feature/Learner/LearnerEnrollmentCapByInstructorPlanTest.php
git commit -m "feat(monetization): enforce split instructor paid module permissions"
```

## Task 7: Add instructor plan usage and limit transparency in module management UI

**Files:**
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `resources/views/instructor/modules/index.blade.php`
- Modify: `resources/views/instructor/modules/partials/module-modal.blade.php`
- Test: `tests/Feature/Instructor/InstructorModulesIndexUiTest.php`

**Step 1: Write the failing test**
Assert instructor module index renders current plan, usage metrics, and entitlement-aware limit hints.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=InstructorModulesIndexUiTest`
Expected: FAIL for missing plan usage UI.

**Step 3: Write minimal implementation**
Pass capability snapshot from controller and render usage and limit hints in module list and modal.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=InstructorModulesIndexUiTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Instructor/ModuleController.php resources/views/instructor/modules/index.blade.php resources/views/instructor/modules/partials/module-modal.blade.php tests/Feature/Instructor/InstructorModulesIndexUiTest.php
git commit -m "feat(instructor-ui): show plan usage and dynamic module limits"
```

## Task 8: Add dedicated instructor subscription offers page and sidebar navigation

**Files:**
- Modify: `routes/instructor.php`
- Create: `app/Http/Controllers/Instructor/SubscriptionController.php`
- Create: `resources/views/instructor/subscriptions/index.blade.php`
- Modify: instructor sidebar layout file(s) under `resources/views/layouts/` or `resources/views/instructor/`
- Test: `tests/Feature/Instructor/InstructorSubscriptionOffersPageTest.php`

**Step 1: Write the failing test**
Add tests for route access, page rendering, plan cards filtered for instructor audience, and sidebar navigation visibility.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=InstructorSubscriptionOffersPageTest`
Expected: FAIL because route/page/menu do not exist.

**Step 3: Write minimal implementation**
Create controller, route, and Blade page with current plan summary, baseline comparison, and upgrade CTA flow aligned with instructor panel style.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=InstructorSubscriptionOffersPageTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add routes/instructor.php app/Http/Controllers/Instructor/SubscriptionController.php resources/views/instructor/subscriptions/index.blade.php tests/Feature/Instructor/InstructorSubscriptionOffersPageTest.php
git commit -m "feat(instructor-subscriptions): add offers page and sidebar entry"
```

## Task 9: Add free baseline plan visibility and dynamic baseline messaging in admin

**Files:**
- Modify: `app/Http/Controllers/Admin/SubscriptionPlanAdminController.php`
- Modify: `resources/views/admin/subscription-plans/index.blade.php`
- Test: `tests/Feature/Admin/PlanManagementFlowTest.php`

**Step 1: Write the failing test**
Add assertions that baseline free instructor feature information is visible and editable in plan management workflows.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=PlanManagementFlowTest`
Expected: FAIL on new baseline visibility assertions.

**Step 3: Write minimal implementation**
Add baseline helper display and ensure instructor free-plan feature quotas are surfaced clearly in admin plan UI.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=PlanManagementFlowTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Admin/SubscriptionPlanAdminController.php resources/views/admin/subscription-plans/index.blade.php tests/Feature/Admin/PlanManagementFlowTest.php
git commit -m "feat(admin-plans): surface instructor free baseline entitlement controls"
```

## Task 10: Add rollout mode switch and strict enforcement transition support

**Files:**
- Modify: `config/subscription_features.php`
- Modify: capability and enforcement service files added in earlier tasks
- Create: `tests/Feature/Instructor/InstructorSubscriptionRolloutModeTest.php`

**Step 1: Write the failing test**
Cover soft mode warning behavior and strict mode blocking behavior.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=InstructorSubscriptionRolloutModeTest`
Expected: FAIL because rollout mode switch does not exist.

**Step 3: Write minimal implementation**
Add config-driven mode flag and route enforcement behavior through shared capability decisions.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=InstructorSubscriptionRolloutModeTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add config/subscription_features.php tests/Feature/Instructor/InstructorSubscriptionRolloutModeTest.php
# include modified enforcement files from prior tasks
git commit -m "feat(instructor-subscriptions): add soft-to-strict rollout mode control"
```

## Task 11: Execute focused verification suite and document outcomes

**Files:**
- Modify: `docs/changelogs/2026-04-16-instructor-subscription-entitlements.md`

**Step 1: Run focused tests**
Run:
- `php artisan test --filter=InstructorPlanCapabilityServiceTest`
- `php artisan test --filter=AdminInstructorPlanEntitlementDefaultsTest`
- `php artisan test --filter=InstructorModulePlanEnforcementTest`
- `php artisan test --filter=InstructorSubscriptionOffersPageTest`
- `php artisan test --filter=InstructorSubscriptionRolloutModeTest`
- `php artisan test --filter=LearnerEnrollmentCapByInstructorPlanTest`

Expected: PASS.

**Step 2: Run regression smoke tests for adjacent flows**
Run:
- `php artisan test --filter=PlanManagementFlowTest`
- `php artisan test --filter=InstructorModuleConfigValidationTest`
- `php artisan test --filter=InstructorPaidModuleEntitlementTest`
- `php artisan test --filter=LearnerModuleCapacityBehaviorTest`

Expected: PASS.

**Step 3: Record actual outputs and residual risk notes**
Document exact commands and pass/fail summary in changelog.

**Step 4: Commit verification notes**
```bash
git add docs/changelogs/2026-04-16-instructor-subscription-entitlements.md
git commit -m "docs(changelog): record instructor subscription entitlement rollout verification"
```

---

Plan complete and saved to `docs/plans/2026-04-16-instructor-subscription-entitlements-implementation-plan.md`. Two execution options:

**1. Subagent-Driven (this session)** - I dispatch fresh subagent per task, review between tasks, fast iteration

**2. Parallel Session (separate)** - Open new session with executing-plans, batch execution with checkpoints

Which approach?