# Admin Learning Content Ownership, Filtering, and UX Design

Date: 2026-04-14
Status: Approved
Owner: Platform Engineering

## 1. Problem Statement

Admin Learning Content views currently expose instructor editing flows too deeply, which weakens ownership boundaries for instructor-created content. At the same time, the Admin modules and lesson management surfaces need better scanability and filtering efficiency for large datasets.

Observed gaps:

- Admin can mutate instructor-owned modules, lessons, topics, and quizzes.
- Admin All Modules card layout carries ownership data with more visual weight than needed.
- Module filtering is not real-time and can feel slow for large lists.
- Lesson management filter controls are incomplete for both Admin and Instructor views.
- Create/Edit Module modal has height and scroll ergonomics issues.

## 2. Goal

Implement a balanced shared-controller approach that:

- preserves Admin transparency and visibility
- enforces instructor content ownership protection for mutations
- keeps Admin management capability for platform-owned content
- upgrades module and lesson filtering UX
- improves module modal height and scroll behavior

## 3. Approved Decisions (Final Inputs)

1. Ownership boundary: Admin read-only for instructor-owned learning content; Admin can manage platform-owned content.
2. Read-only affordance: hide mutation actions for read-only records.
3. Admin platform content capability: keep create/edit/archive/delete enabled.
4. Module search model: debounced server-side real-time filtering.
5. Filter layout direction: align with existing Admin table filter pattern.
6. Publisher avatar source: instructor profile photo first, fallback initials.
7. Module card owner meta: avatar + owner name + owner type, minimal visual weight.
8. Lesson filters: module + lesson status + keyword.
9. Lesson status model: both lesson activity state and module governance context in UI.
10. Modal UX fix scope: height and scroll behavior only.
11. Mutation restriction depth: modules, lessons, topics, quizzes, quiz question flows, reorder/move/import mutation endpoints.
12. Verification expectation: focused feature tests plus targeted manual checks.

## 4. Architecture

### 4.1 Boundary Strategy

Retain shared Instructor content controllers for both panels, but add explicit mutation guard enforcement for Admin panel requests targeting instructor-owned content.

Read behavior:

- allowed for Admin on instructor-owned and platform-owned content

Mutation behavior:

- blocked for Admin when resource owner is instructor
- allowed for Admin when resource owner is platform/admin

### 4.2 Enforcement Layer

Introduce a reusable ownership guard service for centralized decisions across controllers. Do not depend on policy-only behavior for this boundary because global Admin gate shortcuts can bypass policy intent.

Expected service responsibilities:

- resolve ownership from Module/Lesson/Topic/Quiz entities
- determine whether current request is Admin panel context
- return allow/deny for mutation operations based on owner type

### 4.3 Shared Controller Compatibility

Preserve current route topology and shared controller reuse. Add guard checks only at mutation endpoints to minimize route churn and regression risk.

## 5. Ownership Rules and Resource Resolution

Canonical ownership mapping:

- Module owner: module.content_owner_type, fallback to module.creator role lineage
- Lesson owner: lesson.module owner
- Topic owner: topic.lesson.module owner
- Quiz owner: quiz.module owner; fallback quiz.lesson.module owner

Mutation coverage list:

- Modules: create, update, destroy, restore, forceDelete, activate, deactivate
- Lessons: store, update, destroy, reorder, move
- Topics: store, update, destroy, reorder
- Quizzes: store, update, destroy, storeQuestion, updateQuestion, deleteQuestion, import confirm

## 6. UI/UX Design

### 6.1 Admin All Modules Card Refresh

Target file:

- resources/views/admin/modules/index.blade.php

Design changes:

- add compact publisher avatar in owner row
- keep owner info present but lightweight
- remove heavyweight owner-information card appearance
- improve spacing and alignment on title, owner row, metadata row, and action row

Read-only action behavior:

- instructor-owned card in Admin: show view action only
- platform-owned card in Admin: preserve full mutation actions

### 6.2 Real-Time Module Filtering

Target files:

- resources/views/admin/modules/index.blade.php
- app/Http/Controllers/Instructor/ModuleController.php
- app/Services/Content/ContentAccessService.php

Behavior:

- debounced search input auto-submits filter form (GET)
- server-side filtering remains source of truth
- pagination resets when filter values change

Layout:

- align with existing Admin table filter visual pattern
- improve control sizing consistency

### 6.3 Lesson Management Filters (Instructor + Admin)

Target files:

- app/Http/Controllers/Instructor/LessonController.php
- resources/views/instructor/lessons/index.blade.php

Add filters:

- module association
- lesson status (active/inactive)
- keyword search

UI status transparency:

- keep module governance labels visible in lesson rows
- retain accordion grouping while applying server-side filtered dataset

### 6.4 Create/Edit Module Modal UX Fix

Target file:

- resources/views/instructor/modules/partials/module-modal.blade.php

Scope-constrained improvements:

- set viewport-constrained dialog max height
- convert to stable three-zone layout: sticky header, scrollable body, sticky footer
- eliminate awkward whole-dialog scroll behavior
- preserve existing field order and semantics

## 7. Data Flow

### 7.1 Modules Index Query Flow

- User updates search/status/owner/scope.
- Debounced submit sends GET query.
- Controller delegates to ContentAccessService for filtered pagination.
- Blade re-renders cards and pagination with active query state.

### 7.2 Lesson Index Query Flow

- User submits module/status/search filters.
- Controller composes filtered module groups and lessons.
- Blade renders filtered groups while preserving accordion interaction.

### 7.3 Mutation Guard Flow

- Mutation endpoint receives request.
- Ownership guard resolves target owner type.
- If Admin panel + instructor-owned target: abort 403.
- Else continue existing mutation logic.

## 8. Error Handling and Feedback

- Unauthorized mutation attempts return 403.
- Read-only UI removes hidden mutation affordances to reduce confusion.
- Empty states remain filter-aware and include reset pathways.
- Query persistence through pagination remains intact.

## 9. Testing Requirements

Feature test coverage targets:

1. Admin cannot mutate instructor-owned modules.
2. Admin cannot mutate instructor-owned lessons/topics/quizzes and quiz-question mutations.
3. Admin can mutate platform-owned content.
4. Admin can still view instructor-owned module and lesson/quiz/topic pages.
5. Module real-time filters return expected datasets.
6. Lesson filters work in both Instructor and Admin contexts.
7. Modal markup supports sticky header/footer and contained body scroll.

Manual verification targets:

- Admin All Modules owner row readability and avatar fallback rendering
- action visibility rules by ownership type
- module search responsiveness with debounce
- lesson filter usability on both panels
- modal behavior on desktop and mobile breakpoints

## 10. Non-Goals

- No SPA/API-first conversion
- No redesign of unrelated Admin dashboard surfaces
- No schema changes unless discovered necessary during implementation
- No change to module field semantics or lifecycle model

## 11. Risks and Mitigations

Risk: Existing shared routes hide ownership edge cases.
Mitigation: Centralized ownership guard with explicit controller-level enforcement.

Risk: UI-only hiding without backend guard can be bypassed.
Mitigation: Treat UI hiding as convenience; backend guard remains authoritative.

Risk: Filter query changes can affect pagination links.
Mitigation: Preserve query appends and explicit page reset on filter mutations.

## 12. Rollout Order

1. Ownership guard implementation and mutation endpoint enforcement.
2. Admin module card and action visibility update.
3. Module real-time filtering behavior.
4. Lesson filtering enhancements.
5. Modal scroll/height UX fix.
6. Focused feature test pass and manual UX verification.

## 13. Next Step

Create a task-level implementation plan using writing-plans workflow and execute incrementally with verification-first checkpoints.