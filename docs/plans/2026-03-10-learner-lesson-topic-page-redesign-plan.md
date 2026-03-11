# Learner Lesson Topic Page — Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign the learner lesson topic viewing page to use the fullscreen layout, TailAdmin-aligned card styling, SVG-icon gamification chips, content-type icons in the sidebar, muted prerequisite label, swapped text-topic content order (gallery first, rich text below), and remove the "About this Lesson" block.

**Architecture:** Three files are touched — `learner-fullscreen.blade.php` (emoji → SVG), `show.blade.php` (layout swap + new sidebar), `topic-page.blade.php` (header gradient update + text type order swap). No controller, route, model, or database changes.

**Tech Stack:** Blade, Alpine.js, Tailwind CSS v3, brand gradient `#A30EB2 → #730DB1 → #3B0CB1`

**Design doc:** `docs/plans/2026-03-10-learner-lesson-topic-page-redesign-design.md`

---

## Reference: Key Variables Passed by LessonController::show()

```
$lesson            — Lesson model
$module            — Module model (parent)
$lessonTopics      — Collection of LessonTopic (ordered)
$currentTopic      — LessonTopic currently being viewed
$currentTopicIndex — int (0-based)
$completedTopicIds — array of completed LessonTopic IDs
$lockedTopicIds    — array of locked LessonTopic IDs
$lessonQuiz        — Quiz|null (lesson-level quiz)
$quizAttempt       — QuizAttempt|null (last attempt by learner)
$previousLesson    — Lesson|null
```

---

## Task 1: Replace Emoji with SVG Icons in Learner Fullscreen Top Bar

**File:**
- Modify: `resources/views/layouts/learner-fullscreen.blade.php`

This is the smallest, most isolated change. The fullscreen layout is shared across all fullscreen learner pages so verify nothing else breaks.

**Step 1: Locate the three emoji chips**

Open `resources/views/layouts/learner-fullscreen.blade.php`.  
Find the `{{-- Streak --}}`, `{{-- Shields --}}`, and `{{-- Points --}}` comment blocks inside the right section of the top bar. They currently use `<span class="text-sm leading-none">🔥</span>` etc.

**Step 2: Replace emoji spans with SVG icons**

Replace the three emoji `<span>` elements with inline SVGs. Use these exact SVGs (matching the visual style of modules/show gamification strip):

**Streak (flame, orange):**
```html
<svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
    <path d="M12 23c-4.97 0-9-3.582-9-8 0-3.5 2-6.5 5-8-.5 1.5 0 3 1 4 .5-2 2-4 4-5-.5 2 1 4 2 5 .5-1 .5-2.5 0-3.5 2 1.5 3 4 3 7.5 1-1 1.5-2.5 1.5-4 1.5 1.5 2.5 3.5 2.5 6 0 4.418-4.03 8-9 8z"/>
</svg>
```

**Shields (shield, purple):**
```html
<svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
</svg>
```

**Points (star, amber):**
```html
<svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
</svg>
```

**Step 3: Write a smoke test**

```php
// tests/Feature/Learner/LessonPageTest.php  (create new file)

<?php

namespace Tests\Feature\Learner;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPageTest extends TestCase
{
    use RefreshDatabase;

    private function enrolledLearnerWithLesson(): array
    {
        $learner = User::factory()->create();
        $learner->assignRole('learner');
        $learner->gamification()->create([
            'level' => 1, 'xp' => 0, 'score' => 0,
            'current_streak' => 0, 'longest_streak' => 0,
        ]);

        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
            'order' => 1,
            'is_prerequisite' => false,
        ]);
        ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => 'approved',
        ]);

        return compact('learner', 'module', 'lesson', 'topic');
    }

    public function test_learner_can_view_lesson_show_page(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee($lesson->title);
    }

    public function test_lesson_page_does_not_contain_about_this_lesson_block(): void
    {
        ['learner' => $learner, 'lesson' => $lesson] = $this->enrolledLearnerWithLesson();

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertDontSee('About this lesson');
    }

    public function test_lesson_page_contains_back_url_for_module(): void
    {
        ['learner' => $learner, 'lesson' => $lesson, 'module' => $module] = $this->enrolledLearnerWithLesson();

        $this->actingAs($learner)
            ->get(route('learner.lessons.show', $lesson))
            ->assertOk()
            ->assertSee(route('learner.modules.show', $module), false);
    }
}
```

**Step 4: Run test to confirm baseline**

```
php artisan test --filter=LessonPageTest
```

Some tests may fail at this stage (the page isn't redesigned yet) — that's expected. Just confirm the test file runs without syntax errors.

**Step 5: Commit**

```
git add resources/views/layouts/learner-fullscreen.blade.php tests/Feature/Learner/LessonPageTest.php
git commit -m "feat: replace emoji with SVG icons in fullscreen top bar; add LessonPageTest"
```

---

## Task 2: Rewrite `show.blade.php` — Layout + Sidebar

**File:**
- Modify: `resources/views/learner/lessons/show.blade.php`

This is the largest change. Swap the layout and rebuild the sidebar panel from scratch. The main content area stays as an include (handled in Task 3).

**Step 1: Read the current file top-to-bottom**

Read `resources/views/learner/lessons/show.blade.php` fully before touching anything.

**Step 2: Replace the entire file**

The new file extends `layouts.learner-fullscreen` and produces a two-column flex layout inside `@section('content')`.

```blade
@extends('layouts.learner-fullscreen')

@section('title', $lesson->title)
@section('back-url', route('learner.modules.show', $module))
@section('module-title', $module->title)
@section('lesson-title', $lesson->title)

@section('progress-bar')
    @php
        $topicCount   = $lessonTopics->count();
        $doneCount    = count($completedTopicIds);
        $progressPct  = $topicCount > 0 ? round(($doneCount / $topicCount) * 100) : 0;
    @endphp
    <div class="flex items-center gap-2 w-full">
        <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                 style="width: {{ $progressPct }}%; background: linear-gradient(to right, #A30EB2, #3B0CB1);"></div>
        </div>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
            {{ $doneCount }}/{{ $topicCount }}
        </span>
    </div>
@endsection

@section('content')
<div class="flex h-full overflow-hidden">

    {{-- ═══════════════════════════════════════════
         LEFT SIDEBAR — Lesson Content List
    ═══════════════════════════════════════════ --}}
    <aside class="w-[300px] flex-shrink-0 h-full flex flex-col border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">

        {{-- Panel Header --}}
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex-shrink-0">
            <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Lesson Content</p>
            <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2">{{ $lesson->title }}</p>
        </div>

        {{-- Scrollable Topic List --}}
        <div class="flex-1 overflow-y-auto py-2 px-3 space-y-1">

            @foreach($lessonTopics as $index => $topic)
                @php
                    $isCompleted = in_array($topic->id, $completedTopicIds);
                    $isLocked    = in_array($topic->id, $lockedTopicIds);
                    $isCurrent   = $currentTopicIndex === $index && !request()->has('quiz');
                @endphp

                @php
                    // Type icon config
                    $typeConfig = match($topic->type) {
                        'video'     => ['bg' => 'bg-blue-50 dark:bg-blue-900/20',   'text' => 'text-blue-500',   'icon' => 'video'],
                        'text'      => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/20', 'text' => 'text-indigo-500', 'icon' => 'text'],
                        'worksheet' => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'text' => 'text-green-600',  'icon' => 'worksheet'],
                        'quiz'      => ['bg' => 'bg-purple-50 dark:bg-purple-900/20','text' => 'text-purple-500', 'icon' => 'quiz'],
                        default     => ['bg' => 'bg-gray-100 dark:bg-gray-800',     'text' => 'text-gray-500',   'icon' => 'default'],
                    };
                @endphp

                @if($isLocked)
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl opacity-50 cursor-not-allowed select-none">
                        {{-- Type icon --}}
                        <div class="flex-shrink-0 w-7 h-7 rounded-lg {{ $typeConfig['bg'] }} {{ $typeConfig['text'] }} flex items-center justify-center">
                            @include('learner.lessons.partials.topic-type-icon', ['iconType' => $typeConfig['icon']])
                        </div>
                        {{-- Lock state icon --}}
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        {{-- Text --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ $topic->title }}</p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ $topic->duration }} min{{ $topic->is_prerequisite ? ' · Required' : '' }}
                            </p>
                        </div>
                    </div>

                @else
                    <a href="{{ route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => $index]) }}"
                       class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-colors group
                              {{ $isCurrent
                                  ? 'bg-violet-50 dark:bg-violet-900/10 border-l-2 border-violet-600'
                                  : 'hover:bg-gray-50 dark:hover:bg-gray-800/60 border-l-2 border-transparent' }}">
                        {{-- Type icon --}}
                        <div class="flex-shrink-0 w-7 h-7 rounded-lg {{ $typeConfig['bg'] }} {{ $typeConfig['text'] }} flex items-center justify-center">
                            @include('learner.lessons.partials.topic-type-icon', ['iconType' => $typeConfig['icon']])
                        </div>
                        {{-- Completion state --}}
                        <div class="flex-shrink-0">
                            @if($isCompleted)
                                <svg class="w-4 h-4 text-violet-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($isCurrent)
                                <div class="w-4 h-4 rounded-full border-2 border-violet-500 flex items-center justify-center">
                                    <div class="w-1.5 h-1.5 rounded-full bg-violet-500"></div>
                                </div>
                            @else
                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600"></div>
                            @endif
                        </div>
                        {{-- Text --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium truncate
                                      {{ $isCurrent ? 'text-violet-700 dark:text-violet-300' : 'text-gray-800 dark:text-gray-200' }}">
                                {{ $topic->title }}
                            </p>
                            <p class="text-[11px] mt-0.5
                                      {{ $isCurrent ? 'text-violet-500' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $topic->duration }} min{{ $topic->is_prerequisite ? ' · Required' : '' }}
                            </p>
                        </div>
                    </a>
                @endif
            @endforeach

            {{-- Lesson Quiz Row (only when all topics are done) --}}
            @if($lessonQuiz && count($completedTopicIds) === $lessonTopics->count())
                @php $isQuizActive = request()->has('quiz'); @endphp
                <a href="{{ route('learner.lessons.show', ['lesson' => $lesson->id, 'quiz' => 1]) }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-colors border-l-2
                          {{ $isQuizActive
                              ? 'bg-violet-50 dark:bg-violet-900/10 border-violet-600'
                              : 'hover:bg-gray-50 dark:hover:bg-gray-800/60 border-transparent' }}">
                    {{-- Quiz icon --}}
                    <div class="flex-shrink-0 w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-500 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                        </svg>
                    </div>
                    {{-- Completion --}}
                    <div class="flex-shrink-0">
                        @if($quizAttempt && $quizAttempt->passed)
                            <svg class="w-4 h-4 text-violet-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <div class="w-4 h-4 rounded-full border-2 {{ $isQuizActive ? 'border-violet-500' : 'border-gray-300 dark:border-gray-600' }}"></div>
                        @endif
                    </div>
                    {{-- Text --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium truncate {{ $isQuizActive ? 'text-violet-700 dark:text-violet-300' : 'text-gray-800 dark:text-gray-200' }}">
                            {{ $lessonQuiz->title }}
                        </p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ $lessonQuiz->questions->count() }} questions
                        </p>
                    </div>
                </a>
            @endif

        </div>

        {{-- Panel Footer: Back to Module --}}
        <div class="flex-shrink-0 px-5 py-3 border-t border-gray-100 dark:border-gray-800">
            <a href="{{ route('learner.modules.show', $module) }}"
               class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Module
            </a>
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════
         RIGHT — Main Content
    ═══════════════════════════════════════════ --}}
    <main class="flex-1 h-full overflow-y-auto bg-gray-50 dark:bg-gray-950">
        <div class="max-w-4xl mx-auto px-4 py-6">

            @if(request()->has('quiz') && $lessonQuiz)
                @include('learner.lessons.partials.quiz-page')
            @elseif($currentTopic)
                @include('learner.lessons.partials.topic-page')
            @else
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-white/[0.03] p-12 text-center">
                    <svg class="mx-auto h-14 w-14 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No content available for this lesson.</p>
                </div>
            @endif

            {{-- NOTE: "About this Lesson" block intentionally removed per design --}}

        </div>
    </main>

</div>
@endsection
```

**Step 3: Create the topic-type-icon partial**

Create `resources/views/learner/lessons/partials/topic-type-icon.blade.php`:

```blade
{{-- Props: $iconType — 'video' | 'text' | 'worksheet' | 'quiz' | 'default' --}}

@if($iconType === 'video')
    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
        <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
    </svg>
@elseif($iconType === 'text')
    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
    </svg>
@elseif($iconType === 'worksheet')
    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
    </svg>
@elseif($iconType === 'quiz')
    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
    </svg>
@else
    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
    </svg>
@endif
```

**Step 4: Run the tests**

```
php artisan test --filter=LessonPageTest
```

Expected: all three tests pass.

**Step 5: Commit**

```
git add resources/views/learner/lessons/show.blade.php resources/views/learner/lessons/partials/topic-type-icon.blade.php
git commit -m "feat: migrate lesson show page to fullscreen layout with redesigned sidebar"
```

---

## Task 3: Update topic-page Partial — Header Gradient + Text Type Order Swap

**File:**
- Modify: `resources/views/learner/lessons/partials/topic-page.blade.php`

Two independent changes in this file:
1. Header gradient: flat blue → brand gradient
2. Text type: swap order so gallery/images appear first, rich text content below

**Step 1: Read the full partial**

Read `resources/views/learner/lessons/partials/topic-page.blade.php` fully before editing.

**Step 2: Update the topic header gradient**

Find:
```blade
<div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
```

Replace with:
```blade
<div class="p-6" style="background: linear-gradient(to right, #A30EB2, #730DB1, #3B0CB1);">
```

**Step 3: Update the content card outer wrapper to TailAdmin style**

Find the opening `<div class="bg-white rounded-lg shadow-md overflow-hidden">` and replace with:
```blade
<div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-white/[0.03] overflow-hidden">
```

Also find the inner `<div class="p-6">` that wraps the content body and add dark mode background:
```blade
<div class="p-6 bg-white dark:bg-transparent">
```

**Step 4: Swap text type order — images first, rich text below**

Locate the `@elseif($currentTopic->type === 'text')` block. Currently the structure is:
```
rich text prose
↓
image gallery (if images exist)
```

Reorder it to:
```
image gallery (if images exist)   ← moved to top
↓
rich text prose                   ← moved below
```

The new structure for the text type block:

```blade
@elseif($currentTopic->type === 'text')
    <!-- Text Content -->
    <div class="space-y-6" x-data="{
        displayMode: 'slideshow',
        currentImageIndex: 0,
        images: {{ json_encode($currentTopic->image_attachments ?? []) }},
        showZoomModal: false,
        zoomedImageIndex: 0,
        openZoom(index) {
            this.zoomedImageIndex = index;
            this.showZoomModal = true;
            document.body.style.overflow = 'hidden';
        },
        closeZoom() {
            this.showZoomModal = false;
            document.body.style.overflow = 'auto';
        },
        nextZoomImage() {
            this.zoomedImageIndex = this.zoomedImageIndex < this.images.length - 1 ? this.zoomedImageIndex + 1 : 0;
        },
        prevZoomImage() {
            this.zoomedImageIndex = this.zoomedImageIndex > 0 ? this.zoomedImageIndex - 1 : this.images.length - 1;
        }
    }"
    @keydown.escape.window="closeZoom()"
    @keydown.arrow-left.window="showZoomModal && prevZoomImage()"
    @keydown.arrow-right.window="showZoomModal && nextZoomImage()">

        {{-- IMAGES FIRST (if any) — same position as video player in video type --}}
        @if($currentTopic->image_attachments && count($currentTopic->image_attachments) > 0)
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Images
                    </h4>
                    <div class="flex gap-1.5">
                        <button
                            @click="displayMode = 'slideshow'"
                            :class="displayMode === 'slideshow' ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                            class="px-3 py-1 rounded-lg text-xs font-medium transition">
                            Slideshow
                        </button>
                        <button
                            @click="displayMode = 'gallery'"
                            :class="displayMode === 'gallery' ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                            class="px-3 py-1 rounded-lg text-xs font-medium transition">
                            Gallery
                        </button>
                    </div>
                </div>

                {{-- [Keep the full existing slideshow + gallery + zoom modal markup here unchanged] --}}
                {{-- Just move the entire image section block from below the prose to here --}}

            </div>
        @endif

        {{-- RICH TEXT BELOW (same position as text_content in video type) --}}
        @if($currentTopic->text_content)
            <div class="prose dark:prose-invert max-w-none">
                {!! $currentTopic->text_content !!}
            </div>
        @endif

    </div>
```

> **Important:** The existing slideshow markup (Slideshow/Gallery toggle, image grid, zoom modal with keyboard nav) is **kept intact** — only its position relative to the prose block changes. Don't delete any Alpine.js zoom modal code.

**Step 5: Write a test for the text type order**

Add to `tests/Feature/Learner/LessonPageTest.php`:

```php
public function test_text_topic_page_renders_without_error(): void
{
    $learner = User::factory()->create();
    $learner->assignRole('learner');
    $learner->gamification()->create([
        'level' => 1, 'xp' => 0, 'score' => 0,
        'current_streak' => 0, 'longest_streak' => 0,
    ]);

    $module = Module::factory()->create(['is_published' => true]);
    $lesson = Lesson::factory()->create([
        'module_id' => $module->id,
        'is_published' => true,
        'order' => 1,
    ]);
    LessonTopic::factory()->create([
        'lesson_id' => $lesson->id,
        'type' => 'text',
        'order' => 1,
        'text_content' => '<p>Hello world</p>',
        'image_attachments' => null,
    ]);
    ModuleEnrollment::factory()->create([
        'user_id' => $learner->id,
        'module_id' => $module->id,
        'status' => 'approved',
    ]);

    $this->actingAs($learner)
        ->get(route('learner.lessons.show', $lesson))
        ->assertOk()
        ->assertSee('Hello world', false);
}
```

**Step 6: Run all lesson tests**

```
php artisan test --filter=LessonPageTest
```

Expected: all tests pass (4 total).

**Step 7: Commit**

```
git add resources/views/learner/lessons/partials/topic-page.blade.php tests/Feature/Learner/LessonPageTest.php
git commit -m "feat: update topic-page partial — brand gradient header, TailAdmin card style, text type content order swapped"
```

---

## Task 4: Full Test Suite + Visual Smoke Check

**Step 1: Run full test suite**

```
php artisan test
```

Expected: all existing tests still pass. The lesson page tests are new additions, nothing regresses.

**Step 2: Start dev server and visually verify**

```
php artisan serve
npm run dev
```

Open a lesson in the browser as a learner and check:
- [ ] Top bar shows SVG icons (not emojis) for streak, shields, points
- [ ] X button top-left exits to module page
- [ ] Lesson title and module breadcrumb show in top bar
- [ ] Progress bar in center shows correct fraction
- [ ] Left sidebar: each topic has a type icon box + completion circle
- [ ] Active topic has violet left border accent
- [ ] Locked topics are greyed out with a lock icon
- [ ] Prerequisite topics show `"· Required"` in muted text (no red badge)
- [ ] "About this Lesson" section is gone
- [ ] For text topics: image gallery (if present) appears above the prose content
- [ ] Dark mode toggle works correctly

**Step 3: Commit any final polish**

```
git add -A
git commit -m "feat: learner lesson topic page redesign complete"
```

---

## Factories Needed

Verify these factories exist before running tests:
- `Module::factory()` — check `database/factories/ModuleFactory.php`
- `Lesson::factory()` — check `database/factories/LessonFactory.php`
- `LessonTopic::factory()` — check `database/factories/LessonTopicFactory.php`
- `ModuleEnrollment::factory()` — check `database/factories/ModuleEnrollmentFactory.php`

If any factory is missing, create a minimal one using `php artisan make:factory`. Required fields are only those with `NOT NULL` constraints without defaults — check each migration.

---

## What Does NOT Change

- `app/Http/Controllers/Learner/LessonController.php` — no changes
- `resources/views/learner/lessons/partials/quiz-page.blade.php` — no changes
- No routes, models, migrations, or services touched
- The fullscreen layout's overall structure, Alpine stores, toast system — no changes (only the emoji swap in the three chips)
