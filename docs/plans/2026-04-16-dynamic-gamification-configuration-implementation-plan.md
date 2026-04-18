# Dynamic Gamification Configuration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace hardcoded gamification mechanics with a single dynamic admin-managed policy that powers both backend behavior and learner-facing displays.

**Architecture:** Introduce an active gamification policy with version history, resolve it through a centralized resolver service, and refactor gamification services/controllers/views to consume only resolved config values. Keep controllers thin, use Form Requests for validation, enforce cross-field invariants, and preserve existing learner progress while applying new settings forward.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, PHPUnit Feature and Unit tests.

---

I'm using the writing-plans skill to create the implementation plan.

## Task 1: Add policy persistence tables and baseline seed support

**Files:**
- Create: `database/migrations/2026_04_16_120000_create_gamification_policies_table.php`
- Create: `database/migrations/2026_04_16_120100_create_gamification_policy_versions_table.php`
- Create: `database/seeders/GamificationPolicySeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Gamification/GamificationPolicyPersistenceTest.php`

**Step 1: Write the failing test**
Create coverage for:
1. `gamification_policies` and `gamification_policy_versions` tables exist.
2. A baseline active policy is seeded.
3. Baseline payload includes all required top-level sections.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationPolicyPersistenceTest`
Expected: FAIL because tables/seeder do not exist.

**Step 3: Write minimal implementation**
1. Add tables and indexes.
2. Seed one active baseline policy matching current behavior.
3. Wire seeder in `DatabaseSeeder`.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationPolicyPersistenceTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add database/migrations/2026_04_16_120000_create_gamification_policies_table.php database/migrations/2026_04_16_120100_create_gamification_policy_versions_table.php database/seeders/GamificationPolicySeeder.php database/seeders/DatabaseSeeder.php tests/Feature/Gamification/GamificationPolicyPersistenceTest.php
git commit -m "feat(gamification): add policy persistence and baseline seed"
```

## Task 2: Add models for active policy and version snapshots

**Files:**
- Create: `app/Models/GamificationPolicy.php`
- Create: `app/Models/GamificationPolicyVersion.php`
- Test: `tests/Unit/Models/GamificationPolicyModelTest.php`

**Step 1: Write the failing test**
Verify:
1. model casts payload as array/json.
2. relations between policy and versions work.
3. helper scope for active policy works.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationPolicyModelTest`
Expected: FAIL because models/scopes are missing.

**Step 3: Write minimal implementation**
1. Add models, fillable/casts, and relations.
2. Add `scopeActive` and `latestActive` helper methods.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationPolicyModelTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Models/GamificationPolicy.php app/Models/GamificationPolicyVersion.php tests/Unit/Models/GamificationPolicyModelTest.php
git commit -m "feat(gamification): add policy and version models"
```

## Task 3: Implement policy defaults, normalization, and invariant validator

**Files:**
- Create: `app/Services/Gamification/GamificationPolicyDefaults.php`
- Create: `app/Services/Gamification/GamificationPolicyValidator.php`
- Create: `app/Services/Gamification/GamificationPolicyNormalizer.php`
- Test: `tests/Unit/Services/Gamification/GamificationPolicyValidatorTest.php`

**Step 1: Write the failing test**
Add tests for:
1. required sections exist after merge.
2. negative values are rejected.
3. milestone day uniqueness is enforced.
4. threshold monotonicity is enforced.
5. full refill cost lower than single refill is rejected.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationPolicyValidatorTest`
Expected: FAIL because validator/defaults do not exist.

**Step 3: Write minimal implementation**
1. Define immutable defaults.
2. Normalize payload to proper int/bool shapes.
3. Validate cross-field invariants and return actionable errors.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationPolicyValidatorTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Services/Gamification/GamificationPolicyDefaults.php app/Services/Gamification/GamificationPolicyValidator.php app/Services/Gamification/GamificationPolicyNormalizer.php tests/Unit/Services/Gamification/GamificationPolicyValidatorTest.php
git commit -m "feat(gamification): add policy defaults normalization and invariants"
```

## Task 4: Build policy resolver with cache and fallback safety

**Files:**
- Create: `app/Services/Gamification/GamificationPolicyResolver.php`
- Modify: `config/cache.php` (only if dedicated key/ttl needed)
- Test: `tests/Unit/Services/Gamification/GamificationPolicyResolverTest.php`

**Step 1: Write the failing test**
Verify:
1. resolver returns active merged config.
2. resolver caches result.
3. cache invalidates on explicit clear.
4. malformed active policy falls back to defaults or last valid safely.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationPolicyResolverTest`
Expected: FAIL because resolver does not exist.

**Step 3: Write minimal implementation**
1. Resolve active policy + merge defaults + validate.
2. Cache resolved payload.
3. Add `clearCache()` API.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationPolicyResolverTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Services/Gamification/GamificationPolicyResolver.php config/cache.php tests/Unit/Services/Gamification/GamificationPolicyResolverTest.php
git commit -m "feat(gamification): add cached policy resolver with safe fallback"
```

## Task 5: Add admin requests and service for update + version snapshot + restore

**Files:**
- Create: `app/Http/Requests/Admin/UpdateGamificationPolicyRequest.php`
- Create: `app/Services/Gamification/GamificationPolicyAdminService.php`
- Test: `tests/Feature/Admin/GamificationPolicyAdminServiceTest.php`

**Step 1: Write the failing test**
Cover:
1. valid update creates new active policy snapshot.
2. version entry is recorded.
3. invalid payload is rejected and active policy unchanged.
4. restore creates new active from historical version.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationPolicyAdminServiceTest`
Expected: FAIL because request/service do not exist.

**Step 3: Write minimal implementation**
1. request-level rules for basic shape and ranges.
2. service-level strict validator invocation.
3. atomic transaction for update/restore + version snapshot + cache clear.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationPolicyAdminServiceTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Requests/Admin/UpdateGamificationPolicyRequest.php app/Services/Gamification/GamificationPolicyAdminService.php tests/Feature/Admin/GamificationPolicyAdminServiceTest.php
git commit -m "feat(gamification): add admin policy update and restore service"
```

## Task 6: Add admin controller and admin routes for gamification settings

**Files:**
- Create: `app/Http/Controllers/Admin/GamificationSettingsController.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Admin/GamificationSettingsRouteTest.php`

**Step 1: Write the failing test**
Verify:
1. admin can open settings page.
2. admin can update policy.
3. admin can restore version.
4. unauthorized users are denied.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationSettingsRouteTest`
Expected: FAIL because route/controller do not exist.

**Step 3: Write minimal implementation**
1. add thin controller using admin service.
2. add named routes under `routes/admin.php`.
3. keep permission checks aligned with existing admin patterns.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationSettingsRouteTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Admin/GamificationSettingsController.php routes/admin.php tests/Feature/Admin/GamificationSettingsRouteTest.php
git commit -m "feat(admin): add gamification settings routes and controller"
```

## Task 7: Build admin gamification settings page (tabbed UI)

**Files:**
- Create: `resources/views/admin/gamification/settings.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/GamificationSettingsViewTest.php`

**Step 1: Write the failing test**
Assert:
1. tabs render: Points, Streak, Leveling, Shields, Safeguards, History.
2. current active values are prefilled.
3. history entries render after update.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationSettingsViewTest`
Expected: FAIL because page is missing.

**Step 3: Write minimal implementation**
1. implement tabbed form layout using existing admin design language.
2. group inputs clearly and include concise helper text.
3. include version history list and restore action.
4. add sidebar navigation entry.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationSettingsViewTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/admin/gamification/settings.blade.php resources/views/layouts/admin.blade.php tests/Feature/Admin/GamificationSettingsViewTest.php
git commit -m "feat(admin-ui): add tabbed gamification settings management page"
```

## Task 8: Refactor GamificationService to consume resolver config

**Files:**
- Modify: `app/Services/GamificationService.php`
- Test: `tests/Feature/Gamification/GamificationServiceTest.php`
- Create: `tests/Unit/Services/Gamification/GamificationServiceDynamicConfigTest.php`

**Step 1: Write the failing test**
Add tests for:
1. level computation uses hybrid config.
2. milestone bonus lookup uses configured list.
3. no hardcoded fallback values used when config provided.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationServiceDynamicConfigTest`
Expected: FAIL because service still uses hardcoded values.

**Step 3: Write minimal implementation**
1. inject resolver into service.
2. replace constants/literals with resolved config keys.
3. keep method signatures stable where possible.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationServiceDynamicConfigTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Services/GamificationService.php tests/Feature/Gamification/GamificationServiceTest.php tests/Unit/Services/Gamification/GamificationServiceDynamicConfigTest.php
git commit -m "refactor(gamification): make service fully policy-driven"
```

## Task 9: Refactor learner controllers to remove hardcoded rewards/costs

**Files:**
- Modify: `app/Http/Controllers/Learner/LessonController.php`
- Modify: `app/Http/Controllers/Learner/QuizController.php`
- Modify: `app/Http/Controllers/Learner/StreakSaverController.php`
- Modify: `app/Http/Controllers/Learner/ShieldRefillController.php`
- Modify: `app/Http/Controllers/Learner/CertificateController.php`
- Modify: `app/Http/Controllers/CertificateController.php`
- Test: `tests/Feature/Learner/LearnerGamificationDynamicFlowTest.php`

**Step 1: Write the failing test**
Cover:
1. topic completion awards configured points.
2. lesson completion awards configured points.
3. quiz pass/perfect/fail rewards follow configured bands.
4. saver purchase and shield refill costs use config values.
5. module/certificate rewards use configured values.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerGamificationDynamicFlowTest`
Expected: FAIL because controllers still contain literals.

**Step 3: Write minimal implementation**
1. replace literal values with resolver/service calls.
2. keep controller orchestration thin.
3. align session flash payloads with dynamic values.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerGamificationDynamicFlowTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/LessonController.php app/Http/Controllers/Learner/QuizController.php app/Http/Controllers/Learner/StreakSaverController.php app/Http/Controllers/Learner/ShieldRefillController.php app/Http/Controllers/Learner/CertificateController.php app/Http/Controllers/CertificateController.php tests/Feature/Learner/LearnerGamificationDynamicFlowTest.php
git commit -m "refactor(learner): remove hardcoded gamification values from flows"
```

## Task 10: Refactor UserDailyShield behavior for dynamic defaults and caps

**Files:**
- Modify: `app/Models/UserDailyShield.php`
- Test: `tests/Feature/Gamification/UserDailyShieldDynamicConfigTest.php`

**Step 1: Write the failing test**
Validate:
1. daily shield creation uses configured default.
2. refill methods respect configured cap.
3. drain floors correctly at zero.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=UserDailyShieldDynamicConfigTest`
Expected: FAIL because model currently uses hardcoded cap/default.

**Step 3: Write minimal implementation**
1. read default/cap from resolver.
2. apply cap in refill methods.
3. keep premium entitlement separation unchanged.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=UserDailyShieldDynamicConfigTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Models/UserDailyShield.php tests/Feature/Gamification/UserDailyShieldDynamicConfigTest.php
git commit -m "refactor(gamification): make user daily shield defaults and caps configurable"
```

## Task 11: Update learner dashboard computations for dynamic leveling and costs

**Files:**
- Modify: `app/Http/Controllers/Learner/DashboardController.php`
- Modify: `resources/views/components/learner/gamification-panel.blade.php`
- Modify: `resources/views/components/learner/streak-card.blade.php`
- Modify: `resources/views/components/learner/out-of-shields-modal.blade.php`
- Test: `tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php`

**Step 1: Write the failing test**
Assert:
1. XP progress denominator and percent use dynamic leveling settings.
2. saver purchase button thresholds and labels use dynamic config.
3. shield labels/caps in learner widgets use dynamic values.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerGamificationDashboardDynamicViewTest`
Expected: FAIL due to hardcoded 100 XP, 75 saver cost, and 3-cap strings.

**Step 3: Write minimal implementation**
1. compute view-model values in controller from resolver.
2. pass explicit config-derived props to components.
3. remove hardcoded user-facing values from components.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerGamificationDashboardDynamicViewTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/DashboardController.php resources/views/components/learner/gamification-panel.blade.php resources/views/components/learner/streak-card.blade.php resources/views/components/learner/out-of-shields-modal.blade.php tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php
git commit -m "refactor(learner-ui): use dynamic gamification values in dashboard components"
```

## Task 12: Refactor learner rules page to dynamic values and milestone list rendering

**Files:**
- Modify: `app/Http/Controllers/Learner/GamificationController.php`
- Modify: `resources/views/learner/gamification/rules.blade.php`
- Test: `tests/Feature/Learner/GamificationRulesDynamicDisplayTest.php`

**Step 1: Write the failing test**
Verify:
1. rules page shows configured points/costs.
2. milestones are rendered from config list.
3. shield/saver caps and costs match active policy.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationRulesDynamicDisplayTest`
Expected: FAIL because rules view currently contains static literals.

**Step 3: Write minimal implementation**
1. build rules view model from resolver in controller.
2. render numeric values dynamically in static-copy sections.
3. ensure wording remains clear and learner-friendly.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationRulesDynamicDisplayTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/GamificationController.php resources/views/learner/gamification/rules.blade.php tests/Feature/Learner/GamificationRulesDynamicDisplayTest.php
git commit -m "feat(learner-rules): render gamification rules from active policy"
```

## Task 13: Add admin and learner authorization/policy checks for settings operations

**Files:**
- Modify: `app/Providers/AuthServiceProvider.php` (if needed)
- Modify: `routes/admin.php`
- Test: `tests/Feature/Admin/GamificationSettingsAuthorizationTest.php`

**Step 1: Write the failing test**
Validate:
1. authorized admin can update/restore.
2. unauthorized role gets forbidden response.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=GamificationSettingsAuthorizationTest`
Expected: FAIL due to missing/insufficient protection.

**Step 3: Write minimal implementation**
1. enforce proper permission middleware or policy checks.
2. verify route ownership remains in `routes/admin.php`.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=GamificationSettingsAuthorizationTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Providers/AuthServiceProvider.php routes/admin.php tests/Feature/Admin/GamificationSettingsAuthorizationTest.php
git commit -m "chore(auth): enforce authorization boundaries for gamification settings"
```

## Task 14: Add changelog and developer notes for maintainability

**Files:**
- Create: `docs/changelogs/2026-04-16-dynamic-gamification-configuration.md`
- Modify: `docs/ADMIN_DEVELOPMENT_GUIDE.md` (brief section pointer)

**Step 1: Write docs update**
Document:
1. new policy architecture
2. admin page usage
3. restore behavior
4. validation constraints
5. known non-goals

**Step 2: Validate docs consistency**
Confirm docs references and paths are correct.

**Step 3: Commit**
```bash
git add docs/changelogs/2026-04-16-dynamic-gamification-configuration.md docs/ADMIN_DEVELOPMENT_GUIDE.md
git commit -m "docs: add dynamic gamification configuration changelog and admin notes"
```

## Task 15: Run verification suite before completion

**Files:**
- No code changes expected unless regressions are found.

**Step 1: Run focused gamification and admin tests**
Run:
- `php artisan test --filter=Gamification`
- `php artisan test --filter=LearnerGamification`
- `php artisan test --filter=GamificationSettings`

Expected: PASS.

**Step 2: Run high-risk related suites**
Run:
- `php artisan test --filter=QuizController`
- `php artisan test --filter=LessonController`
- `php artisan test --filter=DashboardController`

Expected: PASS.

**Step 3: Resolve failures and rerun impacted suites**
If failures occur, patch minimally and re-run only impacted tests first, then re-run this task.

**Step 4: Final verification commit (if fixes were needed)**
```bash
git add <changed-files>
git commit -m "fix: resolve regression findings from gamification verification"
```

---

Plan complete and saved to `docs/plans/2026-04-16-dynamic-gamification-configuration-implementation-plan.md`. Two execution options:

1. Subagent-Driven (this session) - I dispatch a fresh subagent per task, review each result, then continue.
2. Parallel Session (separate) - open a new implementation session and execute this plan task-by-task with checkpoints.

Which approach?
