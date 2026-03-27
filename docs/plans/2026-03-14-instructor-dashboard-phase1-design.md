# Instructor Dashboard Phase 1 Enhancement Design

**Date:** 2026-03-14  
**Status:** Approved  
**Feature Scope:** Instructor dashboard only (`/instructor/dashboard`)  
**Approach:** B - Component-driven refresh

---

## 1. Goal

Enhance the instructor dashboard UX and visual consistency by introducing reusable dashboard components, a 6-card stats system (including Average Quiz Score), improved right-column action flow, a stronger hero section, and a controlled modules carousel experience that mirrors the quality and consistency of the learner dashboard.

---

## 2. Approved Product Decisions

1. Scope: dashboard-only in Phase 1.
2. New 6th stat card: Average Quiz Score.
3. Average Quiz Score scope: in-card toggle with All-time and Last 30 Days.
4. Default toggle tab: All-time.
5. Trend badges: show on all 6 cards.
6. Card layout: desktop/tablet 3 per row, mobile 2 per row.
7. Card interaction: whole card clickable plus secondary action icon.
8. Carousel density: 1 module per slide.
9. Carousel behavior: autoplay every 5 seconds, infinite loop, arrows + indicators, pause on hover.
10. Quick Actions relocation: move to top of right column above Your Modules.
11. Calendar position: remain below modules.
12. Header enhancement: full hero banner, aligned with current theme and learner dashboard language.
13. Accessibility: keyboard + ARIA support required now.
14. Delivery depth: UI + backend metric polish in this phase.
15. Visual direction: aligned with current instructor theme and learner dashboard reference.

---

## 3. Selected Approach and Rationale

### Selected: Approach B - Component-driven refresh

Build reusable instructor dashboard components and refactor the current dashboard page to compose those components. Add backend metric normalization and trend payloads while preserving route/controller entry points.

### Why this approach

1. Balances speed and maintainability.
2. Reduces future redesign cost for instructor pages.
3. Keeps behavior compatible with existing routing and business logic.
4. Allows strict accessibility implementation without over-refactoring unrelated pages.

### Rejected alternatives

1. Visual-only refresh (too brittle for upcoming redesign waves).
2. Full cross-dashboard design-system refactor (too large for Phase 1 scope).

---

## 4. Architecture and Component Plan

### 4.1 View Components (Blade)

Create instructor dashboard components under `resources/views/components/instructor/`:

1. `hero-banner.blade.php`
2. `stat-card.blade.php`
3. `section-shell.blade.php`
4. `quick-actions.blade.php`
5. `module-carousel.blade.php`
6. `mini-calendar-shell.blade.php` (wrapper around existing calendar markup style)

### 4.2 Dashboard Composition

Refactor `resources/views/instructor/dashboard.blade.php` into component sections:

1. Hero banner (full-width top section).
2. 6 stat cards grid.
3. Left column sections:
1. Recent Activities
2. Pending Requests
3. Top Modules
4. Quiz Performance
4. Right column sections:
1. Quick Actions (moved up)
2. Your Modules carousel
3. Mini Calendar

### 4.3 Consistent Section Styling

Adopt learner-dashboard-like section wrappers:

1. Rounded section container.
2. Soft tinted background.
3. Subtle border.
4. Left accent line in section header.
5. Consistent title/subtitle/action link spacing.

Color families are selected by section semantics (not random):

1. Activity: purple
2. Pending: amber
3. Modules: indigo
4. Quiz performance: green

---

## 5. Data Contract and Backend Changes

Controller: `app/Http/Controllers/Instructor/DashboardController.php`

### 5.1 Required stat payload for each card

Each stat card receives:

1. `label`
2. `value`
3. `route`
4. `icon`
5. `trend.direction` (`up|down|flat`)
6. `trend.percent` (signed float, normalized)
7. `trend.text` (human-readable)
8. `secondary_action` (icon/link)

### 5.2 Metric definitions

1. Total Learners: distinct enrolled learners across instructor-owned modules.
2. Modules: published/total modules for instructor.
3. Quizzes: total quizzes tied to instructor modules.
4. Pending Requests: pending enrollments tied to instructor modules.
5. Enrolled Learners: approved enrollment records tied to instructor modules.
6. Average Quiz Score: mean attempt score across instructor quizzes.

### 5.3 Average Quiz Score dual-scope payload

Provide both values in one response:

1. `avg_quiz_score.all_time`
2. `avg_quiz_score.last_30_days`
3. `avg_quiz_score.default_scope = all_time`
4. Trend payload for both scopes

### 5.4 Trend computation

Compute current period versus previous equivalent period where applicable.

Fallback rules:

1. If previous baseline is zero or absent, return neutral trend.
2. If no attempts exist, display value as `--` with neutral `No data` badge.

All queries remain strictly scoped to `created_by = Auth::id()` modules.

---

## 6. Interaction and UX Behavior

### 6.1 Hero Banner

1. Full-width, visually aligned with learner dashboard style language.
2. Greeting + short subtitle + one CTA.
3. Uses brand gradient and subtle decorative layers without introducing a new theme.

### 6.2 Stat cards

1. 6 cards, 3 per row desktop/tablet, 2 per row mobile.
2. Whole card clickable.
3. Includes secondary action icon.
4. Trend badge visible for all cards.
5. Average Quiz Score includes in-card segmented control:
1. All-time (default)
2. Last 30 Days

### 6.3 Module carousel

1. 1 slide = 1 module card.
2. Autoplay every 5 seconds.
3. Infinite looping.
4. Pause on hover.
5. Arrow controls + indicator dots.
6. Hide controls and disable autoplay when module count <= 1.

### 6.4 Quick Actions and right column ordering

Final right column order:

1. Quick Actions
2. Your Modules
3. Mini Calendar

---

## 7. Accessibility and Motion Requirements

Required in Phase 1:

1. `aria-label` on icon-only controls.
2. Keyboard reachable carousel arrows/dots.
3. Dot buttons announced as slide navigation.
4. Scope toggle uses semantic button states (`aria-pressed`).
5. Trend state not color-only (text/sign/icon also present).
6. Respect reduced motion preference by disabling autoplay/animations.

---

## 8. Error and Empty-State Handling

1. No modules: show friendly empty state with create module CTA.
2. No quiz attempts: show neutral no-data treatment for averages.
3. Missing module thumbnail: branded placeholder.
4. No activities/pending: show informative empty cards with guidance.

---

## 9. Testing and Verification Design

### 9.1 Automated tests (PHPUnit)

1. Feature test for dashboard response contract and scoped metrics.
2. Assertions for 6-card presence including Average Quiz Score payload.
3. Assertions for quick-actions placement container render.
4. Assertions for carousel controls render conditions.

### 9.2 Manual QA matrix

1. Desktop/tablet/mobile visual checks.
2. Autoplay, pause-on-hover, arrows, dots behavior.
3. Toggle switch values and trend updates.
4. Empty-state rendering for module and quiz scenarios.
5. Keyboard-only flow and focus visibility.
6. Reduced-motion behavior.

---

## 10. Scope Boundaries for Phase 1

In scope:

1. Instructor dashboard visual and interaction enhancement.
2. Stats backend payload polish needed for dashboard UI.

Out of scope:

1. Manage Modules redesign.
2. Manage Learners redesign.
3. Manage Lessons redesign.
4. Manage Quizzes redesign.
5. Modal migration for create pages.

Those will be handled in later phases.

---

## 11. Delivery Sequence

1. Extend `DashboardController` with trend and dual-scope score payload.
2. Build reusable instructor dashboard components.
3. Refactor `instructor/dashboard.blade.php` to component composition.
4. Implement Alpine interactions for carousel and score scope toggle.
5. Add/adjust tests.
6. Run test suite and visual verification.

---

## 12. Next Step

Proceed to writing-plans to convert this approved design into a task-by-task implementation plan.
