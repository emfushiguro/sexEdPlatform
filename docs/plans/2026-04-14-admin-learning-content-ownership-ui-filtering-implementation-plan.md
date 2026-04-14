# Admin Learning Content Ownership and Filtering UX Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Enforce Admin read-only boundaries for instructor-owned learning content while improving Admin module card clarity, module real-time filtering, lesson filtering, and module modal scroll ergonomics.

**Architecture:** Keep shared content controllers and route topology, add a centralized ownership guard for mutation endpoints, and make Admin UI ownership-aware for action visibility. Implement debounced server-side filtering for module and lesson pages, then apply modal structural fixes with sticky header/footer and contained body scroll.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS, Spatie Permissions, PHPUnit.

---

## Task 1: Add Centralized Ownership Guard

**Files:**
- Create: `app/Services/Content/ContentOwnershipGuard.php`
- Test: `tests/Unit/Services/Content/ContentOwnershipGuardTest.php`

**Step 1: Write the failing test**

Create unit tests for owner resolution:

- resolves module owner type
- resolves lesson owner via module
- resolves topic owner via lesson->module
- resolves quiz owner via module fallback to lesson->module
- returns true for "admin mutation allowed" when owner is admin/platform
- returns false for "admin mutation allowed" when owner is instructor

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ContentOwnershipGuardTest`
Expected: FAIL with class not found.

**Step 3: Write minimal implementation**

Implement a service with methods such as:

- `ownerTypeForModule(Module $module): string`
- `ownerTypeForLesson(Lesson $lesson): string`
- `ownerTypeForTopic(LessonTopic $topic): string`
- `ownerTypeForQuiz(Quiz $quiz): string`
- `canAdminMutateOwnerType(string $ownerType): bool`

Normalize owner types to `admin` or `instructor` with safe fallbacks.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ContentOwnershipGuardTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/Content/ContentOwnershipGuard.php tests/Unit/Services/Content/ContentOwnershipGuardTest.php
git commit -m "feat: add centralized content ownership guard"
```

## Task 2: Enforce Module Mutation Boundaries in Shared Module Controller

**Files:**
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Test: `tests/Feature/Admin/AdminModuleOwnershipMutationBoundaryTest.php`

**Step 1: Write the failing test**

Add scenarios:

- admin cannot update instructor-owned module
- admin cannot archive/delete/restore/force-delete instructor-owned module
- admin cannot activate/deactivate instructor-owned module
- admin can still mutate platform-owned module
- admin can still view both module types

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminModuleOwnershipMutationBoundaryTest`
Expected: FAIL because current behavior allows mutation.

**Step 3: Write minimal implementation**

Inject and use `ContentOwnershipGuard` in module mutation methods.

Guard rule:

- if panel is admin and owner is instructor: `abort(403)`
- otherwise continue existing logic

Do not alter view/show behavior.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminModuleOwnershipMutationBoundaryTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Instructor/ModuleController.php tests/Feature/Admin/AdminModuleOwnershipMutationBoundaryTest.php
git commit -m "fix: enforce admin read-only mutation boundary for instructor modules"
```

## Task 3: Enforce Lesson, Topic, and Quiz Mutation Boundaries

**Files:**
- Modify: `app/Http/Controllers/Instructor/LessonController.php`
- Modify: `app/Http/Controllers/Instructor/TopicController.php`
- Modify: `app/Http/Controllers/Instructor/QuizManagementController.php`
- Test: `tests/Feature/Admin/AdminLearningContentOwnershipMutationBoundaryTest.php`

**Step 1: Write the failing test**

Add scenarios for Admin against instructor-owned resources:

- cannot create/update/delete lesson
- cannot reorder/move lesson
- cannot create/update/delete topic
- cannot reorder topic
- cannot create/update/delete quiz
- cannot mutate quiz questions/import confirm

Add control scenario where Admin can perform same mutations for platform-owned content.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminLearningContentOwnershipMutationBoundaryTest`
Expected: FAIL on unauthorized mutation expectations.

**Step 3: Write minimal implementation**

Apply ownership guard checks in each mutation action before business logic executes.

Keep existing authorization and validation, adding ownership boundary as an additional hard gate.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminLearningContentOwnershipMutationBoundaryTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Instructor/LessonController.php app/Http/Controllers/Instructor/TopicController.php app/Http/Controllers/Instructor/QuizManagementController.php tests/Feature/Admin/AdminLearningContentOwnershipMutationBoundaryTest.php
git commit -m "fix: enforce admin read-only boundaries for lesson topic quiz mutations"
```

## Task 4: Refresh Admin All Modules Card UI With Publisher Avatar and Minimal Ownership Row

**Files:**
- Modify: `resources/views/admin/modules/index.blade.php`
- Modify: `app/Services/Content/ContentAccessService.php`
- Test: `tests/Feature/Admin/AdminAllModulesOwnershipCardUiTest.php`

**Step 1: Write the failing test**

Assert modules index includes:

- publisher avatar/image or initials fallback element
- owner name and owner type rendered in compact row
- no extra heavyweight owner card block class/structure
- instructor-owned cards hide mutation actions for Admin

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminAllModulesOwnershipCardUiTest`
Expected: FAIL due missing avatar row and action gating.

**Step 3: Write minimal implementation**

In modules query, eager-load creator + instructor profile data needed for avatar.

In card markup:

- convert owner area to compact inline row
- add avatar rendering with fallback initials
- preserve existing theme tokens and spacing scale
- hide edit/archive/delete actions for instructor-owned modules

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminAllModulesOwnershipCardUiTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/admin/modules/index.blade.php app/Services/Content/ContentAccessService.php tests/Feature/Admin/AdminAllModulesOwnershipCardUiTest.php
git commit -m "feat: improve admin module cards with compact owner avatar row"
```

## Task 5: Add Debounced Server-Side Real-Time Module Filtering

**Files:**
- Modify: `resources/views/admin/modules/index.blade.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `app/Services/Content/ContentAccessService.php`
- Test: `tests/Feature/Admin/AdminModulesRealtimeFilterTest.php`

**Step 1: Write the failing test**

Add coverage for filter behavior:

- search query narrows result set
- status and owner filters compose correctly with search
- filter query persists across pagination links

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminModulesRealtimeFilterTest`
Expected: FAIL for missing or incomplete filter semantics.

**Step 3: Write minimal implementation**

Implement Alpine debounce (`300ms` to `400ms`) on search field to submit GET form automatically.

Align filter layout sizing with Admin table filter pattern.

Keep server-side filtering as source of truth.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminModulesRealtimeFilterTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/admin/modules/index.blade.php app/Http/Controllers/Instructor/ModuleController.php app/Services/Content/ContentAccessService.php tests/Feature/Admin/AdminModulesRealtimeFilterTest.php
git commit -m "feat: add debounced real-time server filtering for admin modules"
```

## Task 6: Add Lesson Filters for Instructor and Admin Views

**Files:**
- Modify: `app/Http/Controllers/Instructor/LessonController.php`
- Modify: `resources/views/instructor/lessons/index.blade.php`
- Test: `tests/Feature/Instructor/LessonManagementFiltersTest.php`
- Test: `tests/Feature/Admin/AdminLessonVisibilityFiltersTest.php`

**Step 1: Write the failing test**

Instructor test:

- module filter works
- lesson status filter (`active`/`inactive`) works
- keyword search works

Admin test:

- same filters work across full admin-visible lesson set
- result set remains read-only for instructor-owned content in Admin UI actions

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LessonManagementFiltersTest`
Run: `php artisan test --filter=AdminLessonVisibilityFiltersTest`
Expected: FAIL for missing filter request handling and UI behavior.

**Step 3: Write minimal implementation**

Controller:

- accept `module_id`, `lesson_status`, `search`
- apply scoped filters according to panel context

View:

- add filter bar with module/status/search
- preserve accordion grouping and existing styles
- keep module governance status labels visible

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LessonManagementFiltersTest`
Run: `php artisan test --filter=AdminLessonVisibilityFiltersTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Instructor/LessonController.php resources/views/instructor/lessons/index.blade.php tests/Feature/Instructor/LessonManagementFiltersTest.php tests/Feature/Admin/AdminLessonVisibilityFiltersTest.php
git commit -m "feat: add lesson management filters for instructor and admin views"
```

## Task 7: Fix Create/Edit Module Modal Height and Scroll Behavior

**Files:**
- Modify: `resources/views/instructor/modules/partials/module-modal.blade.php`
- Test: `tests/Feature/Admin/AdminModuleModalUxStructureTest.php`

**Step 1: Write the failing test**

Assert modal structure contains:

- viewport-constrained container classes
- sticky header classes
- scrollable body container classes
- sticky footer action row classes

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminModuleModalUxStructureTest`
Expected: FAIL with missing expected structure/classes.

**Step 3: Write minimal implementation**

Update modal layout to:

- lock dialog max height against viewport
- isolate body scroll
- pin header/footer
- retain existing field order and semantics

No redesign beyond spacing needed for scroll ergonomics.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminModuleModalUxStructureTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/instructor/modules/partials/module-modal.blade.php tests/Feature/Admin/AdminModuleModalUxStructureTest.php
git commit -m "fix: improve module modal height and contained scroll behavior"
```

## Task 8: Run Focused Regression Suite and Manual Verification

**Files:**
- Modify: `docs/changelogs/2026-04-14-admin-learning-content-ownership-ui-filtering.md`

**Step 1: Run focused automated tests**

Run:

- `php artisan test --filter=AdminModuleOwnershipMutationBoundaryTest`
- `php artisan test --filter=AdminLearningContentOwnershipMutationBoundaryTest`
- `php artisan test --filter=AdminAllModulesOwnershipCardUiTest`
- `php artisan test --filter=AdminModulesRealtimeFilterTest`
- `php artisan test --filter=LessonManagementFiltersTest`
- `php artisan test --filter=AdminLessonVisibilityFiltersTest`
- `php artisan test --filter=AdminModuleModalUxStructureTest`

Expected: PASS for all.

**Step 2: Run key existing regressions**

Run:

- `php artisan test --filter=AdminAllModulesPageTest`
- `php artisan test --filter=AdminModulesIndexSegmentationTest`
- `php artisan test --filter=AdminModuleAuthoringWorkflowTest`
- `php artisan test --filter=LessonManagementTest`

Expected: PASS for all.

**Step 3: Manual QA checklist**

- verify admin module cards show minimal owner row with avatar fallback
- verify instructor-owned cards in admin hide mutation actions
- verify platform-owned cards retain mutation actions
- verify module search updates results after debounce
- verify lesson filters work in admin and instructor views
- verify module modal header/footer remain visible while body scrolls on desktop and mobile

**Step 4: Document release notes**

Add changelog entry summarizing:

- boundary enforcement outcomes
- UI and filtering upgrades
- tests run and results

**Step 5: Commit**

```bash
git add docs/changelogs/2026-04-14-admin-learning-content-ownership-ui-filtering.md
git commit -m "docs: add changelog for admin ownership boundary and filtering improvements"
```

---

Plan complete and saved to `docs/plans/2026-04-14-admin-learning-content-ownership-ui-filtering-implementation-plan.md`. Two execution options:

1. Subagent-Driven (this session) - I dispatch fresh subagent per task, review between tasks, fast iteration

2. Parallel Session (separate) - Open new session with executing-plans, batch execution with checkpoints

Which approach?