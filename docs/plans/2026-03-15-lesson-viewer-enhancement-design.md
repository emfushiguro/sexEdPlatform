# Lesson Viewer Enhancement — Design Document
**Date:** 2026-03-15
**Branch:** `feat/admin-panel-integration`
**Author:** Claude (brainstorm session)

---

## Overview

Six targeted enhancements to the learner lesson viewer (`learner/lessons/show.blade.php` and its partials). All features follow existing design tokens (brand gradient `#A30EB2 → #730DB1 → #3B0CB1`, `rounded-2xl` cards, Alpine.js state) and the no-emoji rule (SVG icons only).

---

## Feature 1 — Bottom Navigation Bar Cleanup

### Current State
`show.blade.php` bottom action bar has correct business logic but the layout is visually flat. "Mark as Incomplete" and the primary CTA compete for attention. No visual cue shows topic position within the lesson.

### Design
**Topic progress dot strip** — rendered above the button row. One dot per topic in the lesson. States:
- Completed: brand gradient fill
- Current (active): brand gradient fill + white ring (ring-2 ring-white ring-offset-1)
- Upcoming: `bg-gray-200 dark:bg-gray-600`
- Locked: `bg-gray-100 dark:bg-gray-700` with lock icon overlay (12px)

**Primary CTA button** — brand gradient (`linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1)`), `rounded-xl`, `px-6 py-3 text-sm font-semibold`, hover `opacity-90 scale-[1.02]`.

**Secondary actions** — "Mark as Incomplete" demoted to a plain text link (`text-gray-400 hover:text-red-400 text-xs`) to reduce visual noise. Previous button stays as outlined link.

**Layout:** `flex items-center justify-between gap-4` with dots centered above the button row.

### Files to modify
- `resources/views/learner/lessons/show.blade.php` — bottom action bar section only

---

## Feature 2 — Fullscreen / Sidebar Collapse Toggle

### Current State
Left sidebar is always visible. No way to expand the content reading area.

### Design
**Alpine state** — `sidebarOpen: true` added to a wrapper `x-data` on the outermost flex container in `@section('content')`.

**Sidebar** — wrapped in `x-show="sidebarOpen"` + CSS transitions (`transition-all duration-300 ease-in-out`). Using `style` + `x-bind` to animate `width` (280px → 0) rather than `x-collapse` (plugin not installed).

**Toggle button** — positioned in the top-left corner of the main content header area (above the topic content card, below the gamification bar). Uses a chevron/panel SVG icon that flips direction based on `sidebarOpen`. Styled as a small ghost button (`rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700`).

**Content area reflow** — main content wrapper uses `flex-1` already; sidebar collapsing naturally expands it.

**Persistence** — `sidebarOpen` stored in `localStorage` via Alpine `$persist` (or a simple Alpine init that reads/writes localStorage) so preference persists across topic navigation.

### Files to modify
- `resources/views/learner/lessons/show.blade.php` — sidebar wrapper + toggle button

---

## Feature 3 — Text Topic Carousel Upgrade

### Current State
`topic-page.blade.php` text type renders `image_attachments` with an Alpine image switcher (manual index tracking) and a separate gallery thumbnail strip. No arrows, no dot indicators.

### Design
Replace the existing Alpine image switcher with a proper carousel component:

**Structure:**
```
[carousel wrapper - relative, overflow-hidden, rounded-2xl]
  [slides — absolute positioned, x-show="activeSlide === i" + fade transition]
  [prev arrow button — left-2, absolute, brand SVG]
  [next arrow button — right-2, absolute, brand SVG]
  [dot indicators — bottom-2, centered row]
    [each dot — w-2 h-2 rounded-full, brand gradient when active]
```

**Alpine data:**
- `activeSlide: 0`
- `totalSlides: {{ count($currentTopic->image_attachments ?? []) }}`
- `prevSlide()` / `nextSlide()` methods with wrap-around
- Auto-play off by default

**Transition:** `x-transition:enter="transition ease-out duration-300"` fade (not slide, since images may differ in size).

**Thumbnail strip** (gallery) remains unchanged below the carousel — clicking a thumbnail sets `activeSlide`.

**Text content** prose block remains unchanged below gallery.

**Guard:** Only renders carousel if `count($currentTopic->image_attachments) > 1`. Single image renders as a plain image card.

### Files to modify
- `resources/views/learner/lessons/partials/topic-page.blade.php` — text type image block only

---

## Feature 4 — Video Topic: Plyr.js Player

### Current State
`topic-page.blade.php` video type renders a plain `<video>` tag for uploaded files. No playback speed, no captions, no custom UI.

### Design

**NPM package:** `npm install plyr`
- Import in `resources/js/app.js`: `import Plyr from 'plyr'; import 'plyr/dist/plyr.css';`
- Or a dedicated `resources/js/plyr-init.js` imported by app.js

**HTML structure:**
```html
<div class="rounded-2xl overflow-hidden bg-black" style="aspect-ratio: 16/9;">
  <video
    id="topic-video-{{ $currentTopic->id }}"
    class="plyr-video"
    playsinline
    controls
  >
    <source src="{{ Storage::url($currentTopic->video_file_path) }}" type="video/mp4">
    @if($currentTopic->caption_file_path)
      <track
        kind="subtitles"
        label="Subtitles"
        srclang="en"
        src="{{ Storage::url($currentTopic->caption_file_path) }}"
        default
      />
    @endif
  </video>
</div>
```

**Plyr config (in app.js or inline `@push('scripts')`):**
```js
const player = new Plyr('#topic-video-{{ $currentTopic->id }}', {
  speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
  captions: { active: true, language: 'en', update: true },
  controls: ['play-large', 'play', 'progress', 'current-time', 'mute',
             'volume', 'captions', 'settings', 'fullscreen'],
  settings: ['captions', 'speed'],
});
```

**Caption uploader note:** `caption_file_path` is a nullable field added via migration. Instructor panel will handle upload in a follow-up. For now, the field is available — if null, no `<track>` element is rendered and captions control is hidden by Plyr automatically.

**Quality switching:** Not implemented (single uploaded file per topic).

**`text_content`** block below video remains unchanged.

### DB Changes
New migration: `2026_03_15_000001_add_caption_file_path_to_lesson_topics_table.php`
```php
Schema::table('lesson_topics', function (Blueprint $table) {
    $table->string('caption_file_path')->nullable()->after('video_file_path');
});
```

Add `caption_file_path` to `LessonTopic::$fillable`.

### Files to modify
- `resources/views/learner/lessons/partials/topic-page.blade.php` — video type block
- `resources/js/app.js` — Plyr import + init
- `app/Models/LessonTopic.php` — add to fillable
- New migration file

---

## Feature 5 — Worksheet Topic: PDF.js Inline Viewer

### Current State
`topic-page.blade.php` worksheet type renders file download cards only. No preview.

### Design

**NPM package:** `npm install pdfjs-dist`
- Import in `resources/js/app.js` (or dedicated `resources/js/pdf-viewer.js`):
  ```js
  import * as pdfjsLib from 'pdfjs-dist';
  pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
    'pdfjs-dist/build/pdf.worker.mjs', import.meta.url
  ).toString();
  window.pdfjsLib = pdfjsLib;
  ```

**Alpine component** (`pdfViewer(url)`) defined in `@push('scripts')`:
```js
function pdfViewer(url) {
  return {
    pdf: null, page: 1, totalPages: 0, scale: 1.2, rendering: false,
    async init() {
      this.pdf = await pdfjsLib.getDocument(url).promise;
      this.totalPages = this.pdf.numPages;
      this.$nextTick(() => this.render());
    },
    async render() {
      if (!this.pdf || this.rendering) return;
      this.rendering = true;
      const page = await this.pdf.getPage(this.page);
      const viewport = page.getViewport({ scale: this.scale });
      const canvas = this.$refs.canvas;
      canvas.width = viewport.width;
      canvas.height = viewport.height;
      await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
      this.rendering = false;
    },
    async prev() { if (this.page > 1) { this.page--; await this.render(); } },
    async next() { if (this.page < this.totalPages) { this.page++; await this.render(); } },
    zoomIn() { this.scale = Math.min(this.scale + 0.2, 3); this.render(); },
    zoomOut() { this.scale = Math.max(this.scale - 0.2, 0.5); this.render(); },
  }
}
```

**UI structure (per PDF file):**
```
[card rounded-2xl bg-white shadow-sm border]
  [header: file icon + filename + page count chip]
  [canvas wrapper: bg-gray-50 rounded-xl overflow-auto p-2]
    [<canvas x-ref="canvas">]
  [controls bar: prev | "Page X / Y" | next | zoom- | zoom+ | download]
```

**Non-PDF files** (Word, Excel, generic) keep existing download card UI unchanged.

**Multiple worksheets:** If `worksheet_files` has multiple files, each PDF gets its own `pdfViewer` instance (each `x-data="pdfViewer('...')"` div, `x-init="init()"`).

### Files to modify
- `resources/views/learner/lessons/partials/topic-page.blade.php` — worksheet type block
- `resources/js/app.js` — pdfjs-dist import + worker setup

---

## Feature 6 — Quiz Landing Page Enhancements

### Current State
`quiz-page.blade.php` landing state shows: quiz title/description block, 3-stat grid (Questions / Time / Pass Score), previous attempt card (or first-time instructions), shield notice, Start/Retake button. Information is correct but visually sparse.

### Design

**Stat cards** — upgrade from flat text to elevated micro-cards:
```
[grid grid-cols-3 gap-3]
  [each card: rounded-xl bg-purple-50/40 border border-purple-100/60 p-3 text-center]
    [SVG icon (brand gradient) — 20px]
    [value: text-lg font-bold text-gray-800]
    [label: text-xs text-gray-400 tracking-wide]
```
Icons: question-mark circle for Questions, clock for Time, checkmark-circle for Pass Score.

**Question type breakdown chips** — rendered below the stat grid:
```
[flex flex-wrap gap-2 mt-2]
  [chip: rounded-full bg-gray-100 text-xs px-3 py-1 text-gray-600]
    "X Multiple Choice" / "X Fill in the Blank" / "X True/False" etc.
```
Counts derived from `$lessonQuiz->questions->groupBy('question_type')` — passed from `LessonController`.

**First-time state (no previous attempt):** Replace blank space with an encouraging card:
```
[rounded-2xl bg-purple-50/40 border border-purple-100/60 p-5]
  [SVG star icon (brand gradient, 32px)]
  [heading: "Ready to test your knowledge?"]
  [subtext: "Complete the quiz to unlock your progress. Take your time — you can retake it."]
```

**Attempt history strip** — shown when `count($quizAttempts) > 0` (new variable, populated from `QuizAttempt` for this user+quiz, ordered by created_at desc, limit 5). Layout:
```
[section: "Your Attempts" header with left brand border]
  [list of attempt rows: score%, date, pass/fail badge]
    [pass badge: rounded-full bg-green-100 text-green-700 text-xs]
    [fail badge: rounded-full bg-red-100 text-red-600 text-xs]
```

**Shield notice** — promote to a distinct card:
```
[rounded-xl border border-amber-200 bg-amber-50/60 p-4]
  [flex: shield SVG icon + text block]
  [text: "1 shield required — X/3 remaining" + "Pass ≥Y% and your shield is refunded"]
```

### Controller Change
`LessonController::show()` — add `$quizAttempts` variable:
```php
$quizAttempts = $lessonQuiz
    ? QuizAttempt::where('user_id', auth()->id())
                 ->where('quiz_id', $lessonQuiz->id)
                 ->orderByDesc('created_at')
                 ->limit(5)
                 ->get()
    : collect();
```
Pass `$quizAttempts` and `$questionTypeCounts` to view.

```php
$questionTypeCounts = $lessonQuiz
    ? $lessonQuiz->questions->groupBy('question_type')->map->count()
    : collect();
```

### Files to modify
- `resources/views/learner/lessons/partials/quiz-page.blade.php` — landing state only
- `app/Http/Controllers/Learner/LessonController.php` — add `$quizAttempts` + `$questionTypeCounts`

---

## Summary of All Changes

### New NPM Dependencies
```bash
npm install plyr pdfjs-dist
```

### New Migration
- `add_caption_file_path_to_lesson_topics_table` — nullable string

### Files Modified
| File | Changes |
|---|---|
| `show.blade.php` | Bottom nav dots strip + CTA polish + sidebar collapse toggle |
| `topic-page.blade.php` | Text carousel, video Plyr.js, worksheet PDF.js |
| `quiz-page.blade.php` | Landing state enhancements (stats, type chips, history, shield card) |
| `LessonController.php` | Add `$quizAttempts`, `$questionTypeCounts` |
| `LessonTopic.php` | Add `caption_file_path` to fillable |
| `resources/js/app.js` | Plyr + pdfjs-dist imports |

### New Files
| File | Purpose |
|---|---|
| `database/migrations/2026_03_15_000001_add_caption_file_path_to_lesson_topics_table.php` | DB schema |

---

## Implementation Order

1. NPM install + app.js imports (unblocks all JS-dependent features)
2. DB migration (`caption_file_path`)
3. Bottom nav bar + sidebar toggle (`show.blade.php`)
4. Text carousel (`topic-page.blade.php`)
5. Video Plyr.js player (`topic-page.blade.php`)
6. Worksheet PDF.js viewer (`topic-page.blade.php`)
7. Quiz landing page enhancements (`quiz-page.blade.php` + `LessonController.php`)
