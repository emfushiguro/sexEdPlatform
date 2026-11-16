# Suspension Appeal Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make appeals accessible from suspension management and improve admin UI, with a Manage Suspension entry only in admin dashboard Quick Actions.
**Architecture:** Reuse Blade views and current routes. Count pending appeal rows, select contextual appeal links from eager-loaded collections, and default the existing queue to pending review. Preserve moderation policies and prior uncommitted fixes.
**Tech Stack:** Laravel, MySQL, Blade, Tailwind/Vite, PHPUnit, browser QA.
**Spec:** `docs/superpowers/specs/2026-11-16-suspension-appeal-navigation-design.md`, approved with Quick Actions amendment.

**Status:** implemented locally, uncommitted and not deployed. Focused suite: 110 tests / 513 assertions passed; JavaScript: 286 passed; browser: 21 checks passed. Fresh feature review found no correctness issue. [Verification and manual guide](../../qa/2026-11-16-suspension-appeal-navigation.md). Earlier complete project suite remains red; no fresh complete-suite success is claimed.

## Global Constraints

- Existing branch `feature/suspension-appeal-navigation`; no worktree, staging, commit, push or deployment.
- Preserve existing uncommitted work and stashes. No development DB reset, wipe or reseed. Use a newly verified isolated test schema.
- Main dashboard navigation appears only in Quick Actions, exact label Manage Suspension, target `admin.moderation-suspensions.index`. No sidebar edits.
- Suspension workspace retains Appeals button and clickable pending summary. Default queue is pending_review; explicit empty status means All statuses.
- Keep review policies, lifecycle, notes privacy, notifications and duration behavior unchanged. No dependencies or schema changes.

## Review Focus

- Multiple appeals: newest pending gets the row action; each history row uses its exact ID (Task 1).
- Stale suspension appeal flag must not distort actual pending counts (Task 1).
- Explicit All statuses and search/pagination survive pending default (Task 2).
- Reviewed/clarification appeals remain accessible and show state accurately (Tasks 1–3).
- Long content, deleted users, mobile widths and form validation stay usable (Task 3).

### Task 1: Quick Action and contextual appeal access

Files: `resources/views/admin/dashboard.blade.php`, `resources/views/admin/moderation/suspensions/{index,show}.blade.php`, `app/Services/Admin/ModerationSuspensionDashboardService.php`; new `tests/Feature/Admin/Moderation/SuspensionAppealNavigationTest.php`.

- [x] Add failing feature tests for Manage Suspension in Quick Actions, workspace queue link, count from pending appeal rows, deterministic pending-first row target, no-appeal state, every history row link and non-admin denial. Capture RED on guarded QA schema.
- [x] Add the Quick Action and labeled workspace links. Count `SuspensionAppeal::where('status','pending_review')`. Select from eager-loaded appeals by pending status then submitted_at/ID, without N+1 queries.
- [x] Use readable status badges and keep duration/details actions. Run new and existing dashboard tests, capture GREEN.

### Task 2: Pending-first queue and clear presentation

Files: `app/Http/Controllers/Admin/ModerationAppealController.php`, `resources/views/admin/moderation/appeals/index.blade.php`, navigation tests.

- [x] Add failing tests for missing-status pending default, explicit all statuses, clarification/reviewed states, matching counts, search and pagination links. Capture RED.
- [x] Default absent status only, preserve explicit empty/all filter. Improve queue header, filter bar, User labels, status colors/text, action labels and contextual empty states. Preserve chronological sort/pagination.
- [x] Run queue and existing moderation tests, capture GREEN.

### Task 3: Review UI, verification and handoff

Files: `resources/views/admin/moderation/appeals/show.blade.php`, suspension views, optional shared `resources/views/admin/moderation/partials/appeal-status.blade.php`, navigation tests and QA evidence fixture.

- [x] Add failing tests for back-to-suspension link, preserved decision/thread validation input and escaped long content. Capture RED.
- [x] Improve summary/reason/discussion hierarchy and responsive main/decision layout. Preserve form endpoints, request rules, review outcomes and thread behavior. Add field errors, visible focus, 44px targets and wrap/scroll containment.
- [x] Run bounded admin/user moderation and notification suite, PHP syntax, formatting, relevant JS tests and asset build. Preserve unrelated generated bundles when retaining compiled CSS/manifest.
- [x] Browser-check dashboard/queue/review snapshots at 320/390/1440 widths, long content, empty state, keyboard focus and exact links. Clearly distinguish captured-response display checks from live deployed QA.
- [x] Request one fresh reviewer of this task's incremental working-tree diff; fix important findings with RED/GREEN evidence. Report existing broader suite failures separately, using earlier full-suite evidence as historical rather than current-green proof.
- [x] Update design/plan and QA handoff with actual results, decisions and limitations. Leave all work uncommitted in the requested branch.
