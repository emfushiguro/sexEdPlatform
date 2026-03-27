# Learner Lesson Topic Page — Redesign Design Doc

**Date:** 2026-03-10  
**Status:** Approved  
**Scope:** `resources/views/learner/lessons/show.blade.php` + `resources/views/learner/lessons/partials/topic-page.blade.php`

---

## Problem Statement

The learner lesson topic viewing page is the only learner-facing page still using the old `<x-app-layout>` shell. It is visually inconsistent with the rest of the learner platform (dashboard, modules, modules/show) and lacks the immersive, distraction-free reading experience that a learning platform requires. The layout, sidebar treatment, gamification display, and content ordering all need to be aligned with the established learner design system.

---

## Goals

1. Migrate to the existing `layouts.learner-fullscreen` layout (fullscreen, overflow-hidden, fixed top bar)
2. Replace emoji gamification chips with SVG icons in the top bar
3. Move the only exit to an X button in the top-left corner
4. Redesign the left sidebar panel using TailAdmin card styling aligned with the learner dashboard
5. Add content-type SVG icons to each topic row in the sidebar
6. Replace the red PREREQUISITE badge with a quiet muted text label
7. Remove the "About this Lesson" description block
8. For **text** topics: swap content order so images/gallery appear first, rich text below
9. Align the overall card and panel aesthetic with TailAdmin `component-card` patterns and the existing learner dashboard brand

---

## Architecture

### Layout

**Before:** `<x-app-layout>` — generic app shell with a plain `<x-slot name="header">` and a white content container. Not fullscreen. Has the sidebar nav visible.

**After:** `layouts.learner-fullscreen` — fullscreen shell (overflow hidden, `h-screen`). Provides:
- Fixed 56px top bar with left X button + breadcrumb, center progress bar slot, right gamification chips
- `@yield('content')` fills the remaining height as a flex container
- Includes `<x-learner.out-of-shields-modal>` and flash toasts already

The lesson show page provides these slots:
```
@section('title', $lesson->title)
@section('back-url', route('learner.modules.show', $module))
@section('module-title', $module->title)
@section('lesson-title', $lesson->title)
@section('progress-bar')   {{-- topic completion progress bar --}}
```

### Content Layout

Two-column flex inside `@section('content')`:
- **Left panel** (fixed width ~300px, full height, scrollable independently): lesson topic list
- **Right panel** (flex-1, full height, scrollable independently): topic/quiz content

Both panels scroll independently. The top bar stays fixed. No page-level scroll.

---

## Component Details

### 1. Top Bar (learner-fullscreen — already built, minor update)

The existing `learner-fullscreen.blade.php` top bar has gamification chips that use emoji (🔥, 🛡️, ⭐). These are replaced with inline SVGs:

- **Streak chip** — flame SVG icon (orange), same icon style used on the modules/show page gamification strip
- **Shields chip** — shield SVG icon (purple), consistent with `<x-icons.shield>` already used in the codebase
- **Points chip** — star SVG icon (amber)

Chip styling stays unchanged: `px-2.5 py-1.5 rounded-lg bg-{color}-50 border border-{color}-100` with `text-xs font-bold`.

A center progress bar slot shows topic completion: `X of Y topics completed` with a thin gradient progress bar (`#A30EB2 → #3B0CB1`).

### 2. Left Sidebar Panel

**Card style:** TailAdmin-aligned — `rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]`. Full height, overflow-y-auto, no external shadow.

**Panel header:** `px-6 py-4` section with the lesson title in `text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider` and topic count chip.

**Topic rows:** Each row is a clickable link (or a non-clickable `div` if locked) with:

```
[type-icon box] [completion circle] [title + metadata]
```

**Type icon box** — 28×28px rounded-lg with soft tinted background:
| Topic Type | Icon | Background |
|---|---|---|
| `video` | Play/camera SVG | `bg-blue-50 text-blue-500` |
| `text` | Document lines SVG | `bg-indigo-50 text-indigo-500` |
| `worksheet` | Download-file SVG | `bg-green-50 text-green-600` |
| `quiz` | Pencil/quiz SVG | `bg-purple-50 text-purple-500` |
| (other) | Eye/default SVG | `bg-gray-100 text-gray-500` |

**Completion circle** — 18×18px to the right of the type icon:
- Completed: filled checkmark circle in brand gradient (or `text-violet-500`)
- Current: ring with filled center dot in brand purple
- Locked: lock SVG, `text-gray-300`
- Incomplete: empty ring, `border-gray-300`

**Active row** — brand gradient text on a soft `bg-violet-50 dark:bg-violet-900/10` background, left border accent `border-l-2 border-violet-600`.

**Locked row** — `opacity-50 cursor-not-allowed`, no hover effect.

**Title & metadata line:**
```
Topic Title (truncated)
2 min  ·  Required        ← if is_prerequisite
2 min                     ← if not prerequisite
```
"Required" label: `text-xs text-gray-400 dark:text-gray-500` — no badge, no color, same visual weight as the duration.

**Quiz row** (lesson quiz, shown only when all topics complete):
- Type icon: purple pencil icon box
- Title: quiz title
- Metadata: `X questions`
- Active: purple accent instead of brand gradient

**Panel footer:** "Back to Module" link at the very bottom of the sidebar, styled as a muted text link with a left-arrow icon — not a button. No "Previous Lesson" button (navigation outside this page is handled by the X button).

### 3. Main Content Area (topic-page partial)

**Content card style:** `rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]` — matching TailAdmin component-card.

**Topic header strip:** Stays as a gradient header inside the card top, but uses a subtler treatment aligned with the dashboard hero — brand gradient (`from-[#A30EB2] via-[#730DB1] to-[#3B0CB1]`) instead of flat blue. The type icon box inside the header uses `bg-white/20` backdrop.

**Video type** (unchanged structure):
1. Video player (uploaded file or embed iframe), aspect-video, black rounded container
2. Optional `text_content` prose below (TinyMCE HTML output)

**Text type** (ORDER SWAPPED):
1. **Image gallery / slideshow first** (top) — same visual position as the video player in the video type
   - Slideshow / Gallery toggle buttons stay
   - Full-screen zoom modal stays (Alpine.js)
   - Only shown if `image_attachments` is not empty
2. **Rich text content below** (TinyMCE prose output) — `prose dark:prose-invert max-w-none`

**Worksheet type** (unchanged):
1. `text_content` instructions prose
2. PDF download links

**Remove:** The "About this Lesson" description block (`$lesson->description` blue-bordered box) that currently appears below the content card.

### 4. Complete / Next Topic Button

Remains at the bottom of the content area. If the current topic is not yet completed, show the primary gradient CTA "Mark as Complete & Continue". If already completed, show a ghost "Next Topic →" link. These already exist in the current view — just restyled to match the brand button style from the dashboard.

---

## Data Flow

No controller changes required. All data variables passed by `LessonController::show()` remain identical:
- `$lesson`, `$module`, `$lessonTopics`, `$currentTopic`, `$currentTopicIndex`
- `$completedTopicIds`, `$lockedTopicIds`
- `$lessonQuiz`, `$quizAttempt`
- `$previousLesson`

The `learner-fullscreen` layout fetches gamification data itself via `@php` in its top bar (already does this: `$fsGami`, `$fsShields`).

---

## Dark Mode

All new elements follow the existing dark mode pattern:
- Card backgrounds: `dark:bg-white/[0.03]`
- Borders: `dark:border-gray-800`
- Text: `dark:text-white/90`, `dark:text-gray-400`
- Icon boxes: `dark:bg-blue-900/20`, `dark:bg-indigo-900/20`, etc.

---

## Out of Scope

- No changes to `LessonController.php`
- No changes to `learner-fullscreen.blade.php` logic — only the emoji-to-SVG swap in the chips
- No changes to the quiz page partial (`quiz-page.blade.php`)
- No database migrations or model changes
- No new Blade components — all work is inline in the two view files

---

## Files Changed

| File | Change |
|---|---|
| `resources/views/learner/lessons/show.blade.php` | Full rewrite: swap layout, new sidebar panel, remove description block |
| `resources/views/learner/lessons/partials/topic-page.blade.php` | Text type content order swap + header gradient update |
| `resources/views/layouts/learner-fullscreen.blade.php` | Emoji → SVG in gamification chips only |
