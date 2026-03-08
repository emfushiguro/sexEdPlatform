# Instructor Dashboard — Design Document

**Date:** 2026-03-08
**Status:** Approved
**Author:** Brainstorming session

---

## 1. Overview

Rebuild the instructor panel with a dedicated layout and a fully redesigned dashboard page. The current `instructor/dashboard.blade.php` uses the legacy `<x-app-layout>` shell (top-nav, no sidebar). The new design adopts the TailAdmin layout pattern — collapsible left sidebar, sticky top header — styled with the platform's purple brand gradient to match the learner dashboard aesthetic.

---

## 2. Color Scheme

All colors match the existing learner dashboard and platform brand identity.

| Token | Value | Usage |
|---|---|---|
| Brand gradient | `#A30EB2 → #730DB1 → #3B0CB1` | Sidebar background, stat card gradients, active nav pill, CTA buttons |
| White | `#FFFFFF` | Sidebar text/icons, card content |
| Gray-50 | `bg-gray-50` | Page content area background |
| Purple accent | `purple-100 / purple-600` | Section border-left accents, hover states |
| Navy text | `#0A205C` | Used only for headings on white backgrounds (consistent with login page) |

---

## 3. Layout Architecture — `layouts/instructor-app.blade.php`

A new dedicated Blade layout built from scratch. Nothing in the existing `layouts/app.blade.php`, `learner-app.blade.php`, or `navigation.blade.php` is modified.

### 3.1 Shell Structure

```
<html>
  <head>  (Vite assets, CSRF meta, Alpine.js stores, Poppins font) </head>
  <body x-data (Alpine sidebar + theme store)>
    <div class="xl:flex min-h-screen bg-gray-50">
      [A] Backdrop overlay (mobile)
      [B] Sidebar (fixed, left)
      [C] Main wrapper (flex-1, ml offset driven by sidebar state)
          [D] Header (sticky top)
          [E] Page content  @yield('content')
    </div>
    @stack('scripts')
  </body>
</html>
```

### 3.2 Alpine.js Stores

Two Alpine stores declared in `<head>`:

**`$store.sidebar`**
- `isExpanded: boolean` — true on desktop (≥1280px), false on mobile
- `isMobileOpen: boolean` — mobile drawer state
- `isHovered: boolean` — hover-expand when collapsed on desktop
- Methods: `toggleExpanded()`, `toggleMobileOpen()`, `setHovered(val)`

**`$store.instructorTheme`** (optional future dark mode toggle, stubbed for now)

### 3.3 [B] Sidebar

**Container**
- `position: fixed`, `top: 0`, `left: 0`, `height: 100vh`
- Width: `290px` when expanded or hovered; `72px` when collapsed
- Background: `linear-gradient(180deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%)`
- Transition: `transition-all duration-300`
- Right border: `border-r border-purple-900/30`
- `z-index: 99999`

**Top — Logo + Platform Name**
- Logo: `<img src="/media/Logo.png">` — always visible (icon size when collapsed)
- Platform name: `"ConciousConnections"` in white bold text — visible only when expanded/hovered (`x-show`)
- Below name: small uppercase muted label `"INSTRUCTOR PANEL"` in `text-purple-200 text-xs tracking-widest`

**Navigation Groups**
Rendered via a PHP array (no `MenuHelper` dependency — hardcoded in the layout for simplicity):

```
MAIN
  └─ Dashboard        (grid icon)

ASSESSMENTS
  ├─ Manage Learners  (users icon)      → instructor.users.index
  ├─ Manage Modules   (book icon)       → instructor.modules.index
  ├─ Manage Lessons   (document icon)   → instructor.lessons.index
  ├─ Manage Quizzes   (clipboard icon)  → instructor.quizzes.index
  └─ Assessments Logs (chart icon)      → instructor.enrollments.index

EXTRAS
  └─ Extra Features   (sparkles icon)   → instructor.image-library.index
```

Each nav item:
- Icon always visible (24×24, white/purple-200)
- Label visible only when expanded/hovered
- Active state: `bg-white/20 rounded-xl` left-pill highlight
- Hover state: `bg-white/10 rounded-xl`
- Pending badge on "Manage Learners" / "Assessments Logs" if `pending_enrollments > 0`

**Bottom — Instructor Profile**
- Avatar circle (initials fallback) + instructor name + "Instructor" role badge — visible when expanded
- Logout link with icon

### 3.4 [D] Header

**Container**
- `position: sticky`, `top: 0`, white background, `border-b border-gray-200`, `z-99999`
- Height: `64px`

**Left side**
- Hamburger button → `$store.sidebar.toggleExpanded()` (desktop) / `toggleMobileOpen()` (mobile)

**Center**
- Search input — full-width on desktop, hidden on mobile (appears via button tap)
- Placeholder: `"Search modules, lessons, learners..."`
- On input: debounced AJAX `GET /instructor/search?q=` returning grouped results (Modules / Lessons / Learners) in a dropdown beneath
- Results navigate to the matching record's edit/show page

**Right side**
- Notification bell icon — badge showing `pending_enrollments` count (red, hidden if 0)
- Bell click → dropdown panel: last 10 pending enrollment requests, each row showing learner name, module name, time ago, and inline "Approve" / "Reject" buttons (POST forms)
- Instructor avatar → dropdown: "Profile", "Log Out"

---

## 4. Dashboard Page — `instructor/dashboard.blade.php`

Extends `layouts.instructor-app`. Uses `@section('content')`.

### 4.1 Page Title Row

```
<h1>Instructor Dashboard</h1>
<p>Welcome back, {name}. Here's what's happening today.</p>
```

### 4.2 Stat Cards Row

Five gradient cards in a horizontal scroll row (`grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4`).

Each card:
- Background: `linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1)`
- White text, rounded-2xl, subtle shadow
- Icon (white, Heroicon outline) top-right
- Large number, small label below
- Clickable — navigates to relevant section

| # | Label | Data Source | Route |
|---|---|---|---|
| 1 | Total Learners | `User::role('learner')->count()` scoped to instructor's modules | `instructor.users.index` |
| 2 | Published Modules | `X / Y` published/total, scoped to instructor | `instructor.modules.index` |
| 3 | Total Quizzes | Quiz count across instructor's modules | `instructor.quizzes.index` |
| 4 | Pending Requests | `ModuleEnrollment::pending()` scoped to instructor | `instructor.enrollments.index` |
| 5 | Enrolled Learners | Approved enrollments across instructor's modules | `instructor.users.index` |

> **Pending Requests card** gets a pulsing ring / red tint when count > 0 to draw attention.

### 4.3 Two-Column Content Area

`grid-cols-1 xl:grid-cols-3 gap-6` — left column `xl:col-span-2`, right column `xl:col-span-1`.

---

#### LEFT COLUMN

All sections use the **learner dashboard tinted section wrapper** pattern:
- Tinted background (`bg-purple-50/40`)
- Rounded-2xl, border (`border border-purple-100/60`)
- Section header: left border accent (`border-l-4 border-purple-400 pl-3`) with title + subtitle
- "View All" pill link (top right)

**Section A — Recent Activities**
- Tinted wrapper: `bg-purple-50/40 border-purple-100/60`
- Border accent: `border-purple-400`
- Data: last 10 events across instructor's modules — new enrollments, learner lesson completions, quiz submissions
- Each row: event description text + age bracket tag (pill) + relative timestamp (`diffForHumans()`)
- Clicking a row navigates to the relevant record
- Empty state: friendly illustration + "No recent activity yet"
- "View All" → `instructor.enrollments.index`

**Section B — Pending Enrollment Quick Actions**
- Tinted wrapper: `bg-amber-50/40 border-amber-100/60`
- Border accent: `border-amber-400`
- Only rendered when `$pendingEnrollments->count() > 0`; otherwise hidden (no empty state clutter)
- Each row: learner avatar/initials + name + module title + age bracket pill + time ago + **Approve** (green) / **Reject** (red/outline) inline buttons
- Forms `POST instructor.enrollments.approve` / `instructor.enrollments.reject` with `@csrf`
- Max 5 rows shown, "View All Requests" link

**Section C — Top Modules by Enrollment**
- Tinted wrapper: `bg-indigo-50/30 border-indigo-100/50`
- Border accent: `border-indigo-400`
- Ranked list of instructor's own modules: rank number + module title + age bracket pill + enrollment count badge
- Max 5 rows
- "View All" → `instructor.modules.index`

**Section D — Quiz Performance Summary**
- Tinted wrapper: `bg-green-50/30 border-green-100/50`
- Border accent: `border-green-400`
- Table columns: Module, Quiz Name, Attempts, Avg Score, Pass Rate %
- Scoped to instructor's modules only
- Max 5 rows, "View All" → `instructor.quizzes.index`
- Empty state: "No quiz attempts yet"

---

#### RIGHT COLUMN

**Section E — Your Modules (Carousel)**
- White card, `rounded-2xl`, `shadow-sm border border-gray-100`
- Header: "Your Modules" + "View all →" link
- Horizontal carousel, 2 cards visible at a time, prev/next arrow buttons (Alpine.js `x-data` scroll)
- Each module card:
  - Thumbnail (aspect-video, object-cover) with `#A30EB2 → #3B0CB1` gradient placeholder
  - Module title (bold white text overlaid on bottom of thumbnail)
  - Age bracket (small pill)
  - Enrolled count + learners completed count
  - Last updated (relative time)
  - Edit icon button → `instructor.modules.edit`
  - Eye icon button → learner-facing module preview
- Arrows only shown if modules count > 2
- Empty state: "No modules yet — create your first one" + CTA button

**Section F — Mini Calendar**
- White card, `rounded-2xl`, `shadow-sm border border-gray-100`
- Read-only month calendar (Alpine.js driven, no FullCalendar dependency)
- Highlights dates that have recent enrollment activity (dots below the date number)
- Ability to navigate prev/next month
- No event creation (read-only MVP)
- Data: array of enrollment dates passed from controller

**Section G — Quick Actions**
- White card, `rounded-2xl`, `shadow-sm border border-gray-100`
- 2×2 grid of action buttons:
  - `+ Create Module` → `instructor.modules.create`
  - `+ Add Lesson` → `instructor.lessons.create`
  - `+ Create Quiz` → `instructor.quizzes.create`
  - `View Enrollments` → `instructor.enrollments.index`
- Each button: icon + label, pill-shaped, `bg-purple-50 hover:bg-purple-600 hover:text-white` transition

---

## 5. Controller Changes — `Instructor\DashboardController`

The existing controller is replaced/extended to pass all required data:

```php
// New data passed to view:
$recentActivities    // last 10 enrollment/completion events (scoped to instructor)
$pendingEnrollments  // pending ModuleEnrollment records with user + module (limit 5)
$moduleStats         // instructor's modules ordered by enrollment count (limit 5)
$quizStats           // quiz attempt summaries per module (limit 5)
$instructorModules   // instructor's modules for carousel (all, paginated)
$calendarDates       // array of Y-m-d strings with enrollment activity (current month)
$stats               // existing stat array (scoped to instructor's modules)
```

> **Scoping note:** All stats currently use global counts (all learners, all modules). In the redesign they are scoped to `Auth::user()->modules()` — i.e., only the logged-in instructor's own content.

---

## 6. Search Endpoint

New route: `GET /instructor/search?q=`
Controller: `Instructor\SearchController@index`
Returns JSON:
```json
{
  "modules": [ { "id": 1, "title": "...", "url": "..." } ],
  "lessons": [ { "id": 2, "title": "...", "url": "..." } ],
  "learners": [ { "id": 3, "name": "...", "url": "..." } ]
}
```
Results rendered in an Alpine.js dropdown beneath the header search bar. Max 5 results per group. Requires `auth` + `role:instructor` middleware.

---

## 7. Notification Dropdown

Reuses the same Alpine.js pattern as TailAdmin header. Populated server-side via a shared view composer or passed directly from the layout's `@include`.

Data: `ModuleEnrollment::pending()->with(['user', 'module'])->latest()->limit(10)->get()`

Each notification item:
- Learner name + module
- Time ago
- Approve / Reject buttons (mini POST forms)

On approve/reject: standard form POST (no AJAX needed for MVP), page redirects back.

---

## 8. Blade Component Files to Create

| File | Purpose |
|---|---|
| `layouts/instructor-app.blade.php` | Master layout shell |
| `components/instructor/stat-card.blade.php` | Reusable gradient stat card |
| `components/instructor/module-carousel.blade.php` | Your Modules horizontal carousel |
| `components/instructor/mini-calendar.blade.php` | Read-only calendar (reuse or adapt from learner version) |
| `components/instructor/pending-enrollment-row.blade.php` | Single pending request row with Approve/Reject |
| `components/instructor/activity-row.blade.php` | Single recent activity row |

---

## 9. What Is NOT in Scope

- Charts or graphs (no Chart.js — deferred to future)
- Dark mode toggle (layout is light-mode only for now)
- Real-time notifications (WebSockets / Pusher)
- Event creation in calendar
- Certificate issuance from dashboard UI

---

## 10. Files Modified

| File | Change |
|---|---|
| `resources/views/instructor/dashboard.blade.php` | Full rewrite to extend new layout |
| `app/Http/Controllers/Instructor/DashboardController.php` | Extend to pass all new view data |
| `routes/web.php` | Add `GET /instructor/search` route |

All new files are **additions** — no existing learner or admin files are touched.
