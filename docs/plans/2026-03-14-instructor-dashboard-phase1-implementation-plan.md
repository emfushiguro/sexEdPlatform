# Instructor Dashboard Phase 1 Enhancement Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver the approved Phase 1 instructor dashboard refresh with reusable components, six TailAdmin-style stat cards, score-scope toggle, relocated quick actions, upgraded hero, and autoplay carousel behavior with accessibility support.

**Architecture:** Keep current route/controller entrypoints, extend dashboard data contract in `Instructor\DashboardController`, and refactor `resources/views/instructor/dashboard.blade.php` to compose reusable Blade components under `resources/views/components/instructor/`. Use Alpine.js for client-side interactions (carousel + score toggle) and keep all business logic in backend/controller scope.

**Tech Stack:** Laravel 12, Blade components, Alpine.js, Tailwind CSS v3, PHPUnit.

---

## Task 1: Lock New Dashboard Contract With Failing Tests

**Files:**
- Modify: `tests/Feature/Instructor/DashboardTest.php`

**Step 1: Write the failing test**

Add tests that assert:
1. Dashboard view contains the new contract keys (for example `statCards`, `avgQuizScoreScopes`, `dashboardHero`).
2. Dashboard returns six stat card items including `Average Quiz Score`.
3. Right-column quick actions render before the modules block by checking ordered markers in rendered HTML.
4. Score-scope UI markers exist (`data-score-scope="all_time"`, `data-score-scope="last_30_days"`).

**Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: FAIL on missing view keys/markers.

**Step 3: Write minimal implementation**

Do not change test yet; proceed to controller/view tasks.

**Step 4: Re-run focused test after implementation**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: PASS.

**Step 5: Commit**

```bash
git add tests/Feature/Instructor/DashboardTest.php
git commit -m "test(instructor): define dashboard phase 1 contract and ui markers"
```

---

## Task 2: Extend Dashboard Metrics and Trend Payloads

**Files:**
- Modify: `app/Http/Controllers/Instructor/DashboardController.php`

**Step 1: Write/adjust failing test for metrics shape**

In `DashboardTest`, add assertions for each stat card payload key:
1. `label`
2. `value`
3. `route`
4. `icon`
5. `trend.direction`
6. `trend.percent`
7. `secondaryAction`

Add assertions for dual-scope average score values and default scope (`all_time`).

**Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: FAIL because payload shape is not yet provided.

**Step 3: Write minimal implementation**

In `DashboardController@index`:
1. Keep existing scoped query behavior (`created_by = Auth::id()`).
2. Build `statCards` array for six cards, including `Average Quiz Score`.
3. Compute all-time and last-30-days average quiz scores.
4. Compute trend payloads with safe fallbacks for no-data/zero baselines.
5. Keep legacy variables still used by current sections until view refactor is complete.

**Step 4: Run tests**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: PASS for data contract assertions.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Instructor/DashboardController.php tests/Feature/Instructor/DashboardTest.php
git commit -m "feat(instructor): add phase 1 dashboard stat card and score-scope payloads"
```

---

## Task 3: Build Reusable Instructor Dashboard Components

**Files:**
- Create: `resources/views/components/instructor/hero-banner.blade.php`
- Create: `resources/views/components/instructor/stat-card.blade.php`
- Create: `resources/views/components/instructor/section-shell.blade.php`
- Create: `resources/views/components/instructor/quick-actions.blade.php`
- Create: `resources/views/components/instructor/module-carousel.blade.php`
- Create: `resources/views/components/instructor/mini-calendar-shell.blade.php`

**Step 1: Write failing render assertions**

Add view assertions in `DashboardTest` for:
1. Hero marker text.
2. Six stat cards container marker.
3. Carousel control markers (arrows + indicators) when more than one module exists.
4. Quick actions marker above modules marker.

**Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: FAIL because components/markers are missing.

**Step 3: Implement components**

Implement each component with approved styling/behavior:
1. Hero aligns with learner-dashboard visual language.
2. Stat card follows TailAdmin icon-card concept with trend badge.
3. Section shell provides consistent border/accent/title layout.
4. Quick actions contains four action items in top-right placement variant.
5. Carousel supports autoplay, arrows, dots, pause-on-hover, and one-slide density.
6. Calendar shell wraps current calendar in consistent section styling.

**Step 4: Run focused tests**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: PASS for new render markers.

**Step 5: Commit**

```bash
git add resources/views/components/instructor/*.blade.php tests/Feature/Instructor/DashboardTest.php
git commit -m "feat(instructor): add reusable dashboard components for phase 1"
```

---

## Task 4: Refactor Instructor Dashboard View to Component Composition

**Files:**
- Modify: `resources/views/instructor/dashboard.blade.php`

**Step 1: Write failing integration assertions**

Extend `DashboardTest` and assert:
1. Six-card grid responsive class targets exist.
2. `Average Quiz Score` toggle shows both scope controls.
3. Quick actions block appears before modules carousel block.
4. Calendar remains below modules.

**Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: FAIL until blade composition is updated.

**Step 3: Implement minimal blade refactor**

Refactor `resources/views/instructor/dashboard.blade.php` to:
1. Use `x-instructor.hero-banner`.
2. Render `statCards` via `x-instructor.stat-card` in a 3x/2x responsive grid.
3. Keep left-column data sections but normalize wrappers via `x-instructor.section-shell`.
4. Move `x-instructor.quick-actions` above `x-instructor.module-carousel`.
5. Keep calendar in right column under modules using `x-instructor.mini-calendar-shell`.

**Step 4: Run test**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/instructor/dashboard.blade.php tests/Feature/Instructor/DashboardTest.php
git commit -m "refactor(instructor): compose dashboard with phase 1 components"
```

---

## Task 5: Accessibility and Reduced-Motion Hardening

**Files:**
- Modify: `resources/views/components/instructor/stat-card.blade.php`
- Modify: `resources/views/components/instructor/module-carousel.blade.php`
- Modify: `resources/views/components/instructor/quick-actions.blade.php`
- Modify: `tests/Feature/Instructor/DashboardTest.php`

**Step 1: Write failing accessibility assertions**

Add assertions for:
1. `aria-label` on icon-only interactive controls.
2. `aria-pressed` state for score-scope toggle controls.
3. Keyboard-focus-visible classes on carousel controls.
4. Reduced-motion guard marker in carousel Alpine state.

**Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: FAIL on missing accessibility attributes.

**Step 3: Implement accessibility updates**

1. Add ARIA labels and pressed states.
2. Ensure keyboard navigation works for arrows and dots.
3. Add reduced-motion conditional to disable autoplay transitions where required.
4. Keep trend indication text/icon paired (not color-only).

**Step 4: Run focused tests**

Run:
```bash
php artisan test --filter=DashboardTest
```
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/components/instructor/stat-card.blade.php resources/views/components/instructor/module-carousel.blade.php resources/views/components/instructor/quick-actions.blade.php tests/Feature/Instructor/DashboardTest.php
git commit -m "feat(instructor): harden dashboard accessibility and motion behavior"
```

---

## Task 6: Full Verification and Regression Check

**Files:**
- Modify (if needed): `tests/Feature/Instructor/DashboardTest.php`
- Optional modify: `tests/Feature/Instructor/SearchControllerTest.php` (only if impacted)

**Step 1: Run instructor-focused suite**

Run:
```bash
php artisan test --filter=Instructor
```
Expected: PASS.

**Step 2: Run broader application suite**

Run:
```bash
php artisan test
```
Expected: PASS with no regressions.

**Step 3: Manual QA checklist**

1. Desktop/tablet/mobile layout for 6-card grid.
2. Hero looks aligned with current theme and learner dashboard reference.
3. Carousel autoplay/pause/controls/indicators.
4. Average score toggle All-time vs 30 days behavior.
5. Empty states and missing-thumbnail behavior.
6. Keyboard and ARIA behavior.
7. Reduced-motion behavior.

**Step 4: Commit final fixes (if any)**

```bash
git add -A
git commit -m "test(instructor): verify dashboard phase 1 behavior and regressions"
```

---

## Notes and Constraints

1. Keep controllers thin; no business logic in Blade templates.
2. Preserve all existing route names and URL structure.
3. Avoid changes to learner/admin dashboard behavior in this phase.
4. Follow DRY and YAGNI.
5. Use TDD for each task: failing test -> minimal implementation -> passing test.
6. Prefer frequent, focused commits.

---

## Skills Reference

1. `@superpowers:test-driven-development` for every task implementation cycle.
2. `@superpowers:verification-before-completion` before final completion.
3. `@superpowers:requesting-code-review` after implementation passes tests.
