# Learner Dashboard Visual Enhancement — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Enhance the learner dashboard with Heroicons, icon-pill containers, card hover effects, section backgrounds, and pill nav links — layout and wireframe stay unchanged.

**Architecture:** Pure Blade/Tailwind changes across 4 existing component files. No backend, no routes, no migrations, no new files. All Heroicons are inline SVG (no CDN). Dark mode variants included on every new class.

**Tech Stack:** Blade templates, Tailwind CSS v3, Alpine.js (no new Alpine usage), inline SVG Heroicons v2 (outline unless noted)

---

## Task 1: Gamification panel — stat chips

**Files:**
- Modify: `resources/views/components/learner/gamification-panel.blade.php`

### Step 1: Compile-check the current file

```bash
php artisan view:clear
php artisan view:cache 2>&1 | tail -5
```
Expected: "Blade templates cached successfully."

### Step 2: Replace the 2×2 flat stat grid with icon-led chips

Find and replace the entire `{{-- ─── Stats grid ─── --}}` block (from the `<div class="grid grid-cols-2 gap-3 mb-4">` to its closing `</div>`).

**Replace with:**

```blade
{{-- ─── Stats grid (icon chips) ─── --}}
<div class="grid grid-cols-2 gap-3 mb-4">

    {{-- Enrolled modules --}}
    <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
            </svg>
        </div>
        <div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $totalEnrolled }}</div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Enrolled Modules</div>
        </div>
    </div>

    {{-- Current level --}}
    <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>
        <div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $level }}</div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Current Level</div>
        </div>
    </div>

    {{-- Total points --}}
    <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
            </svg>
        </div>
        <div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPoints) }}</div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Total Points</div>
        </div>
    </div>

    {{-- Streak --}}
    <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
            </svg>
        </div>
        <div>
            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $streak }}</div>
            <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Day Streak</div>
        </div>
    </div>

</div>
```

### Step 3: Compile-check

```bash
php artisan view:clear ; php artisan view:cache 2>&1 | tail -5
```
Expected: "Blade templates cached successfully." — no errors.

### Step 4: Commit

```bash
git add resources/views/components/learner/gamification-panel.blade.php
git commit -m "feat(dashboard): gamification stat chips with Heroicon pill containers"
```

---

## Task 2: Gamification panel — XP bar, quiz row, achievements

**Files:**
- Modify: `resources/views/components/learner/gamification-panel.blade.php`

### Step 1: Upgrade the XP progress bar

Find:
```blade
                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        style="width: {{ $xpPercent }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);"
                    ></div>
                </div>
```

Replace with:
```blade
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 shadow-[0_0_8px_rgba(163,14,178,0.35)]"
                        style="width: {{ $xpPercent }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);"
                    ></div>
                </div>
```

### Step 2: Upgrade the quiz attempts row icon

Find:
```blade
    {{-- ─── Quiz attempts today ─── --}}
    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl mb-4">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/>
            </svg>
            <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Quiz Attempts Today</span>
        </div>
```

Replace with:
```blade
    {{-- ─── Quiz attempts today ─── --}}
    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl mb-4">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-100 dark:bg-purple-900/40">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-purple-600 dark:text-purple-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" />
                </svg>
            </div>
            <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Quiz Attempts Today</span>
        </div>
```

### Step 3: Upgrade achievement fallback icon

Find:
```blade
                        {{ $achievement->icon ?? '🏆' }}
```

Replace with:
```blade
                        @if($achievement->icon)
                            {{ $achievement->icon }}
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-600 dark:text-amber-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                            </svg>
                        @endif
```

### Step 4: Compile-check

```bash
php artisan view:clear ; php artisan view:cache 2>&1 | tail -5
```
Expected: No errors.

### Step 5: Commit

```bash
git add resources/views/components/learner/gamification-panel.blade.php
git commit -m "feat(dashboard): XP bar glow, quiz row icon container, trophy fallback icon"
```

---

## Task 3: Active module card — hover, icon upgrades, button

**Files:**
- Modify: `resources/views/components/learner/module-card-active.blade.php`

### Step 1: Add `group` and hover classes to card root

Find:
```blade
<div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow duration-200 flex flex-col">
```

Replace with:
```blade
<div class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:ring-2 hover:ring-purple-200 dark:hover:ring-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col">
```

### Step 2: Add thumbnail image scale on hover

Find:
```blade
        @if($thumbnail)
            <img src="{{ $thumbnail }}" alt="{{ $module->title }}"
                 class="w-full h-full object-cover">
        @else
```

Replace with:
```blade
        @if($thumbnail)
            <img src="{{ $thumbnail }}" alt="{{ $module->title }}"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
```

### Step 3: Replace thumbnail fallback placeholder with Heroicon book-open

Find:
```blade
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 text-purple-400" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
```
(This is inside the module-card-active fallback block)

Replace with:
```blade
            <div class="w-full h-full flex items-center justify-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-purple-500 dark:text-purple-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
            </div>
```

### Step 4: Replace "✓ Completed" text glyph with check-circle icon

Find:
```blade
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-500 text-white">
                    ✓ Completed
                </span>
```

Replace with:
```blade
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-500 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                    </svg>
                    Completed
                </span>
```

### Step 5: Add lessons-count icon and upgrade meta text

Find:
```blade
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $completedLessons }}/{{ $totalLessons }} lessons completed
            </p>
```

Replace with:
```blade
            <p class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 text-purple-400 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                {{ $completedLessons }}/{{ $totalLessons }} lessons completed
            </p>
```

### Step 6: Add micro-press to CTA button

Find:
```blade
        <a
            href="{{ $continueUrl }}"
            class="mt-auto block w-full text-center text-sm font-semibold text-white py-2 px-4 rounded-xl transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
        >
```

Replace with:
```blade
        <a
            href="{{ $continueUrl }}"
            class="mt-auto block w-full text-center text-sm font-semibold text-white py-2 px-4 rounded-xl transition-all duration-150 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
        >
```

### Step 7: Compile-check

```bash
php artisan view:clear ; php artisan view:cache 2>&1 | tail -5
```
Expected: No errors.

### Step 8: Commit

```bash
git add resources/views/components/learner/module-card-active.blade.php
git commit -m "feat(dashboard): active card hover ring, thumbnail scale, Heroicon upgrades"
```

---

## Task 4: Recommended module card — hover, icon upgrades, button

**Files:**
- Modify: `resources/views/components/learner/module-card-recommended.blade.php`

### Step 1: Add `group` and hover classes to card root

Find:
```blade
<div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow duration-200 flex flex-col">
```

Replace with:
```blade
<div class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:ring-2 hover:ring-purple-200 dark:hover:ring-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col">
```

### Step 2: Add thumbnail image scale on hover

Find:
```blade
        @if($thumbnail)
            <img src="{{ $thumbnail }}" alt="{{ $module->title }}"
                 class="w-full h-full object-cover">
        @else
```

Replace with:
```blade
        @if($thumbnail)
            <img src="{{ $thumbnail }}" alt="{{ $module->title }}"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
```

### Step 3: Replace thumbnail fallback placeholder with Heroicon book-open

Find (note: this is inside module-card-recommended):
```blade
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-12 h-12 text-purple-300" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
```

Replace with:
```blade
            <div class="w-full h-full flex items-center justify-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-purple-500 dark:text-purple-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
            </div>
```

### Step 4: Replace lesson count icon with Heroicon book-open

Find:
```blade
            <span class="flex items-center gap-1">
                <svg width="12" height="12" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                {{ $module->lessons_count }} {{ Str::plural('lesson', $module->lessons_count) }}
            </span>
```

Replace with:
```blade
            <span class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                {{ $module->lessons_count }} {{ Str::plural('lesson', $module->lessons_count) }}
            </span>
```

### Step 5: Replace duration icon with Heroicon clock

Find:
```blade
                <span class="flex items-center gap-1">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6l4 2M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2Z"/>
                    </svg>
                    {{ trim($durationStr) }}
                </span>
```

Replace with:
```blade
                <span class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ trim($durationStr) }}
                </span>
```

### Step 6: Add micro-press to CTA button

Find:
```blade
        <a
            href="{{ $enrollUrl }}"
            class="mt-auto block w-full text-center text-sm font-semibold text-white py-2 px-4 rounded-xl transition-opacity hover:opacity-90"
            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
        >
```

Replace with:
```blade
        <a
            href="{{ $enrollUrl }}"
            class="mt-auto block w-full text-center text-sm font-semibold text-white py-2 px-4 rounded-xl transition-all duration-150 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
        >
```

### Step 7: Compile-check

```bash
php artisan view:clear ; php artisan view:cache 2>&1 | tail -5
```
Expected: No errors.

### Step 8: Commit

```bash
git add resources/views/components/learner/module-card-recommended.blade.php
git commit -m "feat(dashboard): recommended card hover ring, thumbnail scale, Heroicon upgrades"
```

---

## Task 5: Dashboard — section wrappers, header accents, greeting, pill links

**Files:**
- Modify: `resources/views/learner/dashboard.blade.php`

### Step 1: Add `tracking-tight` to greeting and soften subtitle

Find:
```blade
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $greeting }}, {{ $learnerProfile->username ?? Auth::user()->first_name ?? 'Learner' }}! 
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Continue your learning journey.</p>
```

Replace with:
```blade
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ $greeting }}, {{ $learnerProfile->username ?? Auth::user()->first_name ?? 'Learner' }}!
            </h1>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Continue your learning journey.</p>
```

### Step 2: Wrap "Active Learning Modules" section with tinted background

Find:
```blade
        {{-- Active Learning Modules --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Active Learning Modules</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Continue your learning journey</p>
                </div>
                <a href="{{ route('learner.modules.index') }}"
                   class="text-sm font-semibold hover:underline"
                   style="color: #A30EB2;">View All &rarr;</a>
            </div>
```

Replace with:
```blade
        {{-- Active Learning Modules --}}
        <section class="bg-purple-50/40 dark:bg-purple-900/10 rounded-2xl p-5 border border-purple-100/60 dark:border-purple-800/30">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-purple-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Active Learning Modules</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Continue your learning journey</p>
                </div>
                <a href="{{ route('learner.modules.index') }}"
                   class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors duration-150 dark:bg-purple-900/40 dark:text-purple-300 dark:hover:bg-purple-800/50">
                    View All
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 transition-transform duration-150 group-hover:translate-x-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
```

### Step 3: Wrap "Recommended For You" section with tinted background

Find:
```blade
        {{-- Recommended For You --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recommended For You</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Age-appropriate modules picked for you</p>
                </div>
                <a href="{{ route('learner.modules.index') }}"
                   class="text-sm font-semibold hover:underline"
                   style="color: #A30EB2;">Browse All &rarr;</a>
            </div>
```

Replace with:
```blade
        {{-- Recommended For You --}}
        <section class="bg-indigo-50/30 dark:bg-indigo-900/10 rounded-2xl p-5 border border-indigo-100/50 dark:border-indigo-800/30">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-indigo-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Recommended For You</h2>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Age-appropriate modules picked for you</p>
                </div>
                <a href="{{ route('learner.modules.index') }}"
                   class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-colors duration-150 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-800/50">
                    Browse All
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 transition-transform duration-150 group-hover:translate-x-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
```

### Step 4: Compile-check

```bash
php artisan view:clear ; php artisan view:cache 2>&1 | tail -5
```
Expected: No errors.

### Step 5: Commit

```bash
git add resources/views/learner/dashboard.blade.php
git commit -m "feat(dashboard): section tinted wrappers, left-border accents, pill nav links"
```

---

## Final Verification

```bash
php artisan view:clear ; php artisan view:cache 2>&1 | tail -3
```
Expected: "Blade templates cached successfully."

Manual browser check:
1. Log in as a learner
2. Verify gamification panel shows 4 icon chips with colored containers
3. Verify XP bar is slightly thicker with glow
4. Verify quiz attempts row icon is inside a purple container
5. Verify module cards lift and glow purple ring on hover
6. Verify card thumbnails scale on hover
7. Verify "View All" and "Browse All" are purple/indigo pills with arrow icon
8. Verify section backgrounds show subtle tint with left-border header accent
9. Verify dark mode (toggle if available)
