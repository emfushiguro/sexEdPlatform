# Instructor Panel Front-End Redesign — Design Document

**Date:** 2026-03-14
**Branch:** `feat/admin-panel-integration`
**Status:** Approved — ready for implementation

---

## 1. Scope

Redesign all instructor-facing management pages to align with the established learner-side design language (brand gradient, rounded-2xl cards, Poppins typography, SVG icons, no emojis). Migrate creation forms from dedicated pages into modal/slide-over panels. Improve UX throughout: card grids instead of tables where appropriate, grouped accordions for high-volume content, drag-and-drop reordering, inline approval flows, and enriched datatables.

---

## 2. Design System Alignment

All instructor pages adopt the learner-side visual tokens:

| Token | Value |
|---|---|
| Brand gradient | `linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1)` |
| Card style | `rounded-2xl bg-white shadow-sm border border-gray-100 dark:border-gray-700` |
| Section bg | `bg-purple-50/40 rounded-2xl p-5 border border-purple-100/60 dark:border-purple-800/30` |
| Stat chips | `bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3` |
| CTA button | brand gradient + `hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]` |
| Typography | Poppins via `font-sans`. Semibold hierarchy, `tracking-widest` chip labels |
| Icons | SVG only — no emojis. Reuse icons from learner gamification bar (fire/shield/star paths) |

### Icon Reference (from `layouts/learner-fullscreen.blade.php`)

```html
<!-- Streak / Fire -->
<svg fill="currentColor" viewBox="0 0 24 24">
  <path d="M12 23c-4.97 0-9-3.582-9-8 0-3.5 2-6.5 5-8-.5 1.5 0 3 1 4 .5-2 2-4 4-5-.5 2 1 4 2 5 .5-1 .5-2.5 0-3.5 2 1.5 3 4 3 7.5 1-1 1.5-2.5 1.5-4 1.5 1.5 2.5 3.5 2.5 6 0 4.418-4.03 8-9 8z"/>
</svg>

<!-- Shield -->
<svg fill="currentColor" viewBox="0 0 24 24">
  <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
</svg>

<!-- Star / XP Points -->
<svg fill="currentColor" viewBox="0 0 24 24">
  <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
</svg>

<!-- Eye (view) -->
<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
</svg>

<!-- Pencil (edit) -->
<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
</svg>

<!-- Trash (delete) -->
<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
</svg>

<!-- Toggle / Power (activate-deactivate) -->
<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/>
</svg>

<!-- Drag handle -->
<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"/>
</svg>

<!-- Plus (add/create) -->
<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
</svg>

<!-- Check (approve) -->
<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
</svg>

<!-- X (reject / close) -->
<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
</svg>
```

---

## 3. Page Designs

### 3.1 Manage Modules — `instructor/modules/index`

**Replace:** Legacy table
**With:** Hybrid card grid

#### Layout

```
┌─ Page header ─────────────────────────────────────────────────────┐
│  "Manage Modules"                         [+ Create Module]       │
│  [🔍 Search input]      [All] [Published] [Draft] [Archived] tabs │
└───────────────────────────────────────────────────────────────────┘
┌─ grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 ────────────────┐
│  Module card × N                                                   │
│  Empty state CTA (no modules yet)                                 │
└───────────────────────────────────────────────────────────────────┘
```

#### Module Card Anatomy

```
┌─────────────────────────────────────────────┐
│  [brand gradient bg + optional thumbnail]   │  ← h-36, relative
│  [STATUS BADGE]                    top-right │
├─────────────────────────────────────────────┤
│  Module Title (font-semibold)               │  ← card body
│  Short description (2-line clamp, text-sm)  │
├─────────────────────────────────────────────┤
│  [5 lessons] · [2 quizzes] · [12 enrolled]  │  ← stat chips row
├─────────────────────────────────────────────┤
│  [eye] [pencil] [power] [trash]             │  ← footer icon bar
└─────────────────────────────────────────────┘
```

- Status badge: `Published` (green), `Draft` (gray), `Deactivated` (amber), `Archived` (red/muted)
- Footer icons are `hover:text-purple-600 transition-colors` icon buttons with tooltips
- Statistics use `text-xs text-gray-500 font-medium` inline with `·` separators

#### Activation / Delete Logic

| Condition | Activate | Deactivate | Delete (soft) |
|---|---|---|---|
| Published + has learners | — | ✓ (shows power icon) | ✗ (icon locked, tooltip explains) |
| Draft + no learners | ✓ | — | ✓ (trash icon active) |
| Deactivated | ✓ | — | ✓ |
| Archived (soft-deleted) | — | — | ✓ Restore button shown |

- Soft delete: sets `deleted_at` timestamp, module moves to "Archived" tab
- Archived tab shows a "Restore" action instead of the standard footer buttons
- Deactivation hides module from learner browse; enrolled learners retain access

#### Search

- Alpine.js `x-model="query"` on the search input
- `x-show` filter on each card using `query === '' || title.includes(query) || description.includes(query)`
- Debounced 300ms with `@input.debounce.300ms`
- Tab filter (All / Published / Draft / Archived) is a separate `activeTab` state

---

### 3.2 Create Module — Modal

**Replaces:** `instructor/modules/create` (dedicated page — kept as fallback, not linked from UI)
**Trigger:** "Create Module" button → `window.dispatchEvent(new CustomEvent('open-module-modal'))`

#### Modal Specs

- Type: Centered overlay modal, `max-w-2xl`, backdrop blur
- Alpine.js: `x-data="moduleModal()"` component or via `$store`
- Pattern matches existing quiz modal (`instructor/quizzes/partials/quiz-modal.blade.php`)

#### Fields

| Field | Type | Notes |
|---|---|---|
| Title | text input | required |
| Description | textarea (3 rows) | required |
| Thumbnail | file input (dropzone style) | optional, image preview |
| Age Group | select | Kids / Teens / Adults / All Ages |
| Status | toggle switch | Draft (default) / Published |

> **Duration field removed** (per design decision — auto-calculated from lesson durations).

#### Post-Create Flow

1. Form submits to `instructor.modules.store`
2. Controller creates module, redirects to `instructor.modules.show($module)`
3. Module show page is where lessons are added — the natural "next step" after creation
4. Toast notification: "Module created. Add your first lesson below."

---

### 3.3 Module Show — `instructor/modules/show`

**Redesign all three sections.**

#### 3.3a — Module Info Card

- Horizontal layout: thumbnail left (rounded-xl, aspect-video, `w-48`), metadata right
- Metadata: title (text-xl font-bold), description (text-sm text-gray-500), 4 stat chips
- Stat chips: Duration · Lessons · Enrolled · Status badge
- Edit button (pencil icon) top-right, opens edit modal or links to edit page

#### 3.3b — Enrolled Learners Section

```
┌─ Enrolled Learners ─────────────────────── [View All Enrollments →] ─┐
│  [All] [Pending (3)] [Approved] [Rejected]  ← Alpine tab state       │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │ Initials  Name          Email           Enrolled    Status  Act  │ │
│  │ [JD]      Jane Doe      jane@...        Mar 1, 26   Pending ✓ ✗  │ │
│  │ [AB]      Alex Brown    alex@...        Feb 28, 26  Approved   ✗ │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│  Showing 5 of 12. View All →                                          │
└───────────────────────────────────────────────────────────────────────┘
```

- Filter tabs: `All` / `Pending` (with count badge) / `Approved` / `Rejected`
- Pending rows: approve (check icon, green hover) + reject (x icon, red hover) inline buttons
- Approved rows: remove/unenroll button only (trash icon), with confirmation toast
- Max 5 rows preview; "View All" links to `instructor.enrollments.index?module_id=X`
- Initials avatar: `bg-purple-100 text-purple-700 rounded-full w-8 h-8 flex items-center justify-center text-xs font-bold`
- Approve/reject actions submit to `instructor.enrollments.{approve|reject}` via `fetch` (no page reload), row updates status badge inline

#### 3.3c — Lessons List

- Drag-and-drop reorder via SortableJS (loaded via CDN, `data-sortable="true"`)
- Each row: drag handle (≡ svg) | order badge | title | type badge | duration | View / Edit / Delete icons
- "Add Lesson" button (brand gradient, top-right of section) → opens lesson slide-over modal
- Lesson progress: `text-xs text-gray-400` "X learners completed" per row
- Delete: confirmation inline (shift row to red bg + confirm/cancel inline, no full modal needed)

---

### 3.4 Create Lesson — Slide-Over Modal

**Replaces:** `instructor/lessons/create` (dedicated page — kept as fallback)
**Trigger:** "Add Lesson" button on module show page
**Pattern:** Right slide-over panel (`translate-x` transition)

#### Specs

- Width: `max-w-lg` (wider than a standard modal, narrower than full-page)
- Backdrop: semi-transparent, click-outside closes
- Alpine: `x-data="lessonSlideOver()"` or `$store`

#### Fields

| Field | Type | Notes |
|---|---|---|
| Module | select (disabled/pre-filled) | pre-selected from context |
| Title | text input | required |
| Description | textarea (3 rows) | optional |
| Order | number | auto-calculated (count + 1), editable |

#### Post-Create Flow

1. Submit to `instructor.lessons.store`
2. Redirect to `instructor.lessons.show($lesson)`
3. Lesson show page is where topics and quizzes are added
4. Toast: "Lesson created. Add topics and a quiz below."

---

### 3.5 Manage Lessons — `instructor/lessons/index`

**Replace:** Legacy table with module filter
**With:** Accordion grouped by module

#### Layout

```
┌─ Page header ──────────────────────────────────────────────────────┐
│  "Manage Lessons"                     [Module ▼ filter] [🔍 Search]│
└────────────────────────────────────────────────────────────────────┘
┌─ Accordion section per module ─────────────────────────────────────┐
│  ▼  Sex Ed Basics  (5 lessons)                         [+ Add]     │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  #1  Introduction to Anatomy   [Video]  10min   ✏  🗑        │  │
│  │  #2  Understanding Consent      [Text]   8min   ✏  🗑        │  │
│  └──────────────────────────────────────────────────────────────┘  │
│  ▶  Advanced Topics   (3 lessons)                      [+ Add]     │
│  ▶  Teen Health       (7 lessons)                      [+ Add]     │
└────────────────────────────────────────────────────────────────────┘
```

- Each accordion group = one module, collapsible via Alpine `open` bool
- Lesson rows: order number, title, type badge (colored: Video=blue, Text=gray, Worksheet=amber, Interactive=purple), duration, Edit link, Delete (soft)
- Search: filters across all modules simultaneously, auto-expands modules with matches
- "Add Lesson" per module row → opens lesson slide-over with that module pre-selected
- Module filter dropdown: narrows view to selected module's group only
- Empty module group: shows "No lessons yet — Add one" placeholder row

---

### 3.6 Manage Quizzes — `instructor/quizzes/index`

**Replace:** Legacy table
**With:** TailAdmin `basic-tables-three` style (Alpine.js client-side pagination + search)

#### Table Columns

| Column | Detail |
|---|---|
| Title + description (truncated) | Semibold title, muted description below |
| Belongs To | "Module: Sex Ed Basics" or "Lesson: Session 2" with pill badge |
| Questions | Count chip |
| Passing Score | `%` value |
| Status | Active (green badge) / Inactive (gray badge) |
| Actions | `x-common.table-dropdown` → View, Edit, Toggle Active, Delete |

#### Filters

- Search input (client-side, Alpine filter)
- Module dropdown filter
- Type filter: All / Module Quiz / Lesson Quiz

#### Pagination

- Follow `basic-tables-three` Alpine pattern: 10 per page, Previous/Next + page numbers

---

### 3.7 Manage Users/Learners — `instructor/users/index`

**Replace:** Legacy table
**With:** Enriched TailAdmin-style table with expandable rows

#### Table Columns

| Column | Detail |
|---|---|
| Checkbox | Select-all pattern (basic-tables-two style) |
| Avatar + Name/Email | Initials avatar chip + stacked name/email |
| Role | Badge (`x-ui.badge` color=primary/warning/info) |
| Status | Badge (Active=success, Suspended=error, Inactive=warning) |
| Enrolled Modules | Count chip, `text-xs font-medium` |
| Last Active | Relative date (e.g., "2 days ago") |
| Actions | `x-common.table-dropdown` → View, Edit, Delete |

#### Row Expand

- Click row → `expandedRow` Alpine state toggles inline detail panel below the row
- Detail panel: list of enrolled modules with status chips (Active / Completed / Pending)
- Smooth `x-transition` slide-down

#### Filters

- Search input (name or email)
- Role dropdown (All / Learner / Counselor / Clinic / Organization / Admin)
- Status dropdown (All / Active / Inactive / Suspended)

#### Soft Delete

- Delete action opens centered confirmation modal
- Modal copy: "This will deactivate the user account and remove them from all active views. You can restore this account later."
- Buttons: Cancel (gray) + "Delete User" (red gradient)
- Sets `deleted_at` via `SoftDeletes` trait
- No hard delete from instructor UI

---

### 3.8 Lesson Show — `instructor/lessons/show`

**Redesign + feature additions:**

#### Changes

1. **Design refresh:** Brand gradient section headers, stat chips, `rounded-2xl` card wrappers throughout
2. **Remove duplicate "Create Quiz" button:** Keep only the top toolbar button. Remove the bottom "Add Quiz" link inside the quizzes list card
3. **Drag-and-drop topic reorder:** Replace move-up/move-down `<form>` buttons with SortableJS drag handles. Auto-saves order on `end` event via `fetch(PATCH route, { order: [...ids] })`
4. **Learner progress overview:** Stat card at top of Topics section — "X / Y learners have completed this lesson" with a completion rate mini progress bar
5. **Topic table cleanup:** SVG type icon before title (matching type badge color), cleaner action button alignment using icon buttons instead of text buttons

#### Section Headers (brand gradient style)

```html
<div class="flex items-center justify-between mb-4">
    <div class="border-l-4 pl-3" style="border-color: #730DB1;">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Topics</h2>
        <p class="text-xs text-gray-400">Drag to reorder</p>
    </div>
    <button ...>+ Add Topic</button>
</div>
```

#### Lesson Creation Form (`instructor/lessons/create`)

Enhanced but kept as fallback page (not primary entry point). Fields redesigned with brand gradient submit button. No multi-step wizard.

---

## 4. Navigation / Modal Architecture

### Modal Store Pattern

All modals follow the existing quiz modal pattern. A lightweight Alpine store or event-based system:

```js
// Open module modal
window.dispatchEvent(new CustomEvent('open-module-modal'))

// Open lesson slide-over (with module context)
window.dispatchEvent(new CustomEvent('open-lesson-modal', { detail: { moduleId: X } }))
```

Each modal is an `@include` partial at the bottom of its parent page's `@section('content')`.

### Post-Creation Redirects

| Action | Redirect To | Toast |
|---|---|---|
| Create Module | `instructor.modules.show($module)` | "Module created. Add your first lesson below." |
| Create Lesson | `instructor.lessons.show($lesson)` | "Lesson created. Add topics and a quiz below." |

---

## 5. Files to Create / Modify

### New Files (partials/modals)
- `resources/views/instructor/modules/partials/module-modal.blade.php`
- `resources/views/instructor/lessons/partials/lesson-slideout.blade.php`
- `resources/views/instructor/users/partials/delete-confirm-modal.blade.php`

### Modified Files
- `resources/views/instructor/modules/index.blade.php` — full redesign (card grid)
- `resources/views/instructor/modules/show.blade.php` — redesign all 3 sections
- `resources/views/instructor/modules/create.blade.php` — kept as fallback, button removed from nav
- `resources/views/instructor/lessons/index.blade.php` — full redesign (accordion groups)
- `resources/views/instructor/lessons/create.blade.php` — kept as fallback, enhanced form
- `resources/views/instructor/lessons/show.blade.php` — design refresh + drag-drop + progress + fix duplicate button
- `resources/views/instructor/users/index.blade.php` — full redesign (enriched table + expandable rows + soft delete)
- `resources/views/instructor/quizzes/index.blade.php` — full redesign (TailAdmin basic-tables-three style)

### Controller Touches (minimal)
- `ModuleController@store` — redirect to `modules.show` instead of `modules.index`
- `LessonController@store` — redirect to `lessons.show` instead of `lessons.index`
- `ModuleController@destroy` — soft delete support (ensure `SoftDeletes` on Module model)
- `UserController@destroy` — soft delete (ensure `SoftDeletes` on User model)
- New route/action: `instructor.modules.deactivate` / `instructor.modules.activate` (toggle `is_published`)
- New PATCH route: `instructor.lessons.reorder` (accepts `{ order: [id1, id2, ...] }`)
- New route: `instructor.enrollments.approve` / `instructor.enrollments.reject` (already likely exists)

---

## 6. Dependencies

| Dependency | Source | Use |
|---|---|---|
| SortableJS | CDN (`@push('scripts')`) | Drag-and-drop topic reorder, lessons list reorder |
| Alpine.js | Already loaded | All reactive state (modals, tabs, search, pagination, expand rows) |
| TailAdmin `x-common.table-dropdown` | `!tail-admin/resources/views/components/common/table-dropdown.blade.php` | Per-row action menus in all tables |
| TailAdmin `x-ui.badge` | `!tail-admin/resources/views/components/ui/badge.blade.php` | Status + role badges |
| TailAdmin `x-ui.modal` | `!tail-admin/resources/views/components/ui/modal.blade.php` | Confirmation dialogs |

---

## 7. Out of Scope (this phase)

- Admin-level user management (password reset, role promotion)
- Bulk actions (select-all delete / bulk enroll)
- Analytics charts on module/lesson show pages
- Email notifications for enrollment approve/reject
