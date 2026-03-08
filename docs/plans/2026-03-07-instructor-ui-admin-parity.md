# Instructor UI — Admin Panel Design Parity

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Migrate all 31 Instructor Blade views from the legacy `<x-app-layout>` / top-navbar design to the Admin panel's sidebar-based layout with dark mode, semantic color tokens, and shared components — while keeping every existing Instructor route and controller untouched.

**Architecture:** Create a new `layouts/instructor.blade.php` layout that reuses the same Alpine.js sidebar/theme stores, dark-mode approach, and card/table/form patterns from `layouts/admin.blade.php`. Extract shared markup into Blade components so both Admin and Instructor layouts stay DRY. Instructor views `@extend('layouts.instructor')` instead of `<x-app-layout>`.

**Tech Stack:** Laravel 12 Blade, Tailwind CSS (custom `shadow-theme-*` / semantic color tokens already in `app.css`), Alpine.js 3 stores, Vite.

---

## File Inventory — What Already Exists

### Instructor Views (31 files to migrate)

| Directory | Files |
|-----------|-------|
| `instructor/` | `dashboard.blade.php` |
| `instructor/modules/` | `index`, `create`, `edit`, `show` |
| `instructor/lessons/` | `index`, `create`, `edit`, `show`, `partials/quiz-modal` |
| `instructor/quizzes/` | `index`, `create`, `edit`, `show`, `add-question`, `edit-question`, `import-preview`, `partials/quiz-modal` |
| `instructor/topics/` | `create`, `edit` |
| `instructor/enrollments/` | `index`, `show` |
| `instructor/users/` | `index`, `create`, `edit`, `show` |
| `instructor/image-library/` | `index` |

_Backup files (`*_old_backup`, `*_broken_backup`, `create-backup`) are ignored._

### Instructor Controllers (already wired — no changes needed)

| Controller | Views it returns |
|------------|-----------------|
| `DashboardController` | `instructor.dashboard` |
| `ModuleController` | `instructor.modules.*` |
| `LessonController` | `instructor.lessons.*` |
| `TopicController` | `instructor.topics.*` |
| `QuizManagementController` | `instructor.quizzes.*` |
| `EnrollmentController` | `instructor.enrollments.*` |
| `UserController` | `instructor.users.*` |
| `ImageLibraryController` | `instructor.image-library.index` |

### Routes (`routes/instructor.php`)

All routes already named `instructor.*` and prefixed `/instructor`. **No changes needed.**

---

## Shared Design Tokens (reference from Admin)

```
/* Cards */   rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs
/* Tables */  rounded-2xl overflow-hidden shadow-theme-xs — header bg-gray-50 dark:bg-white/[0.02]
/* Inputs */  px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500
/* Buttons */ px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors
/* Badges */  inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
/* Stats */   w-10 h-10 rounded-xl bg-{color}-50 dark:bg-{color}-500/10 + text-{color}-500
/* Links */   text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400
```

---

## Task 1: Create Shared Sidebar Partials

**Why:** Both Admin and Instructor will share the same sidebar chrome (logo area, expand/collapse, mobile backdrop). Only the nav links differ. Extract the structural parts so we avoid duplicating ~200 lines of Alpine.js sidebar markup.

### Files

- **Create:** `resources/views/components/admin/sidebar-shell.blade.php`
- **Create:** `resources/views/components/admin/sidebar-link.blade.php`

### Step 1.1 — Create the sidebar shell component

Create `resources/views/components/admin/sidebar-shell.blade.php`:

```blade
{{-- Shared sidebar chrome: mobile backdrop, aside element, logo, slot for nav, collapse toggle --}}
@props(['homeRoute', 'logoLabel' => config('app.name')])

{{-- Mobile backdrop --}}
<div x-show="$store.sidebar.isMobileOpen"
     x-cloak
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="$store.sidebar.setMobileOpen(false)"
     class="fixed inset-0 z-[99998] bg-gray-900/50 xl:hidden">
</div>

<aside class="fixed top-0 left-0 flex flex-col h-screen bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 transition-all duration-300 ease-in-out overflow-hidden"
       style="z-index: 99999;"
       :class="{
           'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
           'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen,
           'translate-x-0': $store.sidebar.isMobileOpen,
           '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
       }"
       @mouseenter="$store.sidebar.setHovered(true)"
       @mouseleave="$store.sidebar.setHovered(false)">

    {{-- Logo --}}
    <div class="flex items-center px-5 pt-8 pb-7"
         :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
        <a href="{{ $homeRoute }}" class="flex items-center gap-3 min-w-0">
            <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-brand-500 flex items-center justify-center shadow-theme-sm">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                  x-cloak
                  class="text-base font-bold text-gray-900 dark:text-white whitespace-nowrap overflow-hidden">
                {{ $logoLabel }}
            </span>
        </a>
    </div>

    {{-- Navigation (the caller fills this) --}}
    <div class="flex flex-col flex-1 overflow-y-auto no-scrollbar px-3 pb-6">
        {{ $slot }}
    </div>
</aside>
```

### Step 1.2 — Create the sidebar-link component

Create `resources/views/components/admin/sidebar-link.blade.php`:

```blade
@props(['href', 'active' => false, 'icon' => ''])

<li>
    <a href="{{ $href }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
              {{ $active ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
       :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
        <span class="flex-shrink-0 {{ $active ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
            {!! $icon !!}
        </span>
        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
              x-cloak class="whitespace-nowrap">{{ $slot }}</span>
    </a>
</li>
```

### Step 1.3 — Create a sidebar section-heading component

Create `resources/views/components/admin/sidebar-heading.blade.php`:

```blade
@props(['label'])

<h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
    x-cloak
    class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
    {{ $label }}
</h2>
```

### Step 1.4 — Run the app to verify components render

```bash
php artisan view:clear
```

Expected: No errors.

### Step 1.5 — Commit

```bash
git add resources/views/components/admin/
git commit -m "feat: extract shared sidebar-shell, sidebar-link, sidebar-heading components"
```

---

## Task 2: Create `layouts/instructor.blade.php`

**Why:** This is the single-biggest change. Every Instructor view will `@extends('layouts.instructor')` instead of `<x-app-layout>`. The layout mirrors `layouts/admin.blade.php` (same Alpine stores, dark mode, sidebar, header) but with Instructor-specific navigation links.

### Files

- **Create:** `resources/views/layouts/instructor.blade.php`

### Step 2.1 — Create the layout

Create `resources/views/layouts/instructor.blade.php`. This file mirrors `layouts/admin.blade.php` but uses the shared sidebar components and Instructor nav links:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Instructor') | {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const saved = localStorage.getItem('theme');
                    const sys = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    this.theme = saved || sys;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    if (this.theme === 'dark') {
                        document.documentElement.classList.add('dark');
                        document.body.classList.add('bg-gray-900');
                        document.body.classList.remove('bg-gray-50');
                    } else {
                        document.documentElement.classList.remove('dark');
                        document.body.classList.remove('bg-gray-900');
                        document.body.classList.add('bg-gray-50');
                    }
                }
            });

            Alpine.store('sidebar', {
                isExpanded: window.innerWidth >= 1280,
                isMobileOpen: false,
                isHovered: false,
                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    this.isMobileOpen = false;
                },
                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                },
                setMobileOpen(val) { this.isMobileOpen = val; },
                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <script>
        (function() {
            var t = localStorage.getItem('theme') ||
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (t === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @yield('styles')
</head>

<body class="font-outfit antialiased bg-gray-50 dark:bg-gray-900 transition-colors duration-200"
      x-data="{}"
      x-init="
        $store.sidebar.isExpanded = window.innerWidth >= 1280;
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1280) {
                $store.sidebar.isMobileOpen = false;
                $store.sidebar.isExpanded = false;
            } else {
                $store.sidebar.isMobileOpen = false;
                $store.sidebar.isExpanded = true;
            }
        });
      ">

    <div class="min-h-screen xl:flex">

        {{-- SIDEBAR --}}
        <x-admin.sidebar-shell :home-route="route('instructor.dashboard')">
            <nav class="flex flex-col gap-4">

                {{-- MAIN --}}
                <div>
                    <x-admin.sidebar-heading label="Main" />
                    <ul class="flex flex-col gap-1">
                        <x-admin.sidebar-link
                            :href="route('instructor.dashboard')"
                            :active="request()->routeIs('instructor.dashboard')"
                            icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>'>
                            Dashboard
                        </x-admin.sidebar-link>
                    </ul>
                </div>

                {{-- CONTENT --}}
                <div>
                    <x-admin.sidebar-heading label="Content" />
                    <ul class="flex flex-col gap-1">
                        <x-admin.sidebar-link
                            :href="route('instructor.modules.index')"
                            :active="request()->routeIs('instructor.modules.*')"
                            icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'>
                            Modules
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link
                            :href="route('instructor.lessons.index')"
                            :active="request()->routeIs('instructor.lessons.*')"
                            icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'>
                            Lessons
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link
                            :href="route('instructor.quizzes.index')"
                            :active="request()->routeIs('instructor.quizzes.*')"
                            icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'>
                            Quizzes
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link
                            :href="route('instructor.image-library.index')"
                            :active="request()->routeIs('instructor.image-library.*')"
                            icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'>
                            Image Library
                        </x-admin.sidebar-link>
                    </ul>
                </div>

                {{-- PEOPLE --}}
                <div>
                    <x-admin.sidebar-heading label="People" />
                    <ul class="flex flex-col gap-1">
                        <x-admin.sidebar-link
                            :href="route('instructor.users.index')"
                            :active="request()->routeIs('instructor.users.*')"
                            icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>'>
                            Learners
                        </x-admin.sidebar-link>
                        <x-admin.sidebar-link
                            :href="route('instructor.enrollments.index')"
                            :active="request()->routeIs('instructor.enrollments.*') || request()->routeIs('instructor.modules.enrollments')"
                            icon='<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>'>
                            Enrollments
                        </x-admin.sidebar-link>
                    </ul>
                </div>

            </nav>
        </x-admin.sidebar-shell>

        {{-- MAIN AREA --}}
        <div class="flex-1 transition-all duration-300"
             :class="{
                 'xl:ml-[290px]': $store.sidebar.isExpanded,
                 'xl:ml-[90px]': !$store.sidebar.isExpanded
             }">

            {{-- Sticky Header --}}
            <header class="sticky top-0 z-[99997] flex items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur px-6 py-4">
                <div class="flex items-center gap-4">
                    {{-- Mobile hamburger --}}
                    <button @click="$store.sidebar.toggleMobileOpen()"
                            class="xl:hidden rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- Desktop collapse toggle --}}
                    <button @click="$store.sidebar.toggleExpanded()"
                            class="hidden xl:flex rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">@yield('page-title', 'Instructor')</h1>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Dark mode toggle --}}
                    <button @click="$store.theme.toggle()"
                            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 transition">
                        <svg x-show="$store.theme.theme === 'light'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="$store.theme.theme === 'dark'" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    {{-- User dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5 transition">
                            <span class="w-8 h-8 rounded-full bg-brand-100 dark:bg-brand-500/20 flex items-center justify-center text-xs font-bold text-brand-700 dark:text-brand-300">
                                {{ substr(auth()->user()->name ?? 'I', 0, 1) }}
                            </span>
                            <span class="hidden sm:inline">{{ auth()->user()->name ?? 'Instructor' }}</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-48 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-theme-sm py-1 z-50">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5">Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Flash messages --}}
            @if(session('success') || session('error') || session('warning') || session('info'))
                <div class="px-6 pt-4">
                    @if(session('success'))
                        <div class="flex items-center gap-3 rounded-xl border border-success-200 dark:border-success-500/30 bg-success-50 dark:bg-success-500/10 px-4 py-3 text-sm text-success-700 dark:text-success-300">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="flex items-center gap-3 rounded-xl border border-error-200 dark:border-error-500/30 bg-error-50 dark:bg-error-500/10 px-4 py-3 text-sm text-error-700 dark:text-error-300">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            {{ session('error') }}
                        </div>
                    @endif
                    @if(session('warning'))
                        <div class="flex items-center gap-3 rounded-xl border border-warning-200 dark:border-warning-500/30 bg-warning-50 dark:bg-warning-500/10 px-4 py-3 text-sm text-warning-700 dark:text-warning-300">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            {{ session('warning') }}
                        </div>
                    @endif
                    @if(session('info'))
                        <div class="flex items-center gap-3 rounded-xl border border-blue-200 dark:border-blue-500/30 bg-blue-50 dark:bg-blue-500/10 px-4 py-3 text-sm text-blue-700 dark:text-blue-300">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ session('info') }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Page content --}}
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @yield('scripts')
</body>
</html>
```

### Step 2.2 — Clear views and verify no syntax errors

```bash
php artisan view:clear
```

### Step 2.3 — Commit

```bash
git add resources/views/layouts/instructor.blade.php
git commit -m "feat: create instructor layout with admin-style sidebar and dark mode"
```

---

## Task 3: Migrate the Instructor Dashboard

**Why:** Start with the highest-visibility page. Converts the dashboard from `<x-app-layout>` to `@extends('layouts.instructor')` and replaces all styling with admin design tokens.

### Files

- **Modify:** `resources/views/instructor/dashboard.blade.php`

### Step 3.1 — Read the current dashboard view fully

Read `resources/views/instructor/dashboard.blade.php` top to bottom so you know every stat card, table, and section it renders.

### Step 3.2 — Rewrite the dashboard

Replace the entire file. Key changes:

1. `<x-app-layout>` → `@extends('layouts.instructor')`
2. `@section('title', 'Dashboard')` + `@section('page-title', 'Dashboard')`
3. Replace every stat card with the Admin card pattern:
   ```blade
   <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs">
       <div class="flex items-center justify-between mb-4">
           <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ label }}</span>
           <div class="w-10 h-10 rounded-xl bg-{{ color }}-50 dark:bg-{{ color }}-500/10 flex items-center justify-center">
               {{-- icon --}}
           </div>
       </div>
       <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
       <a href="{{ url }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
           View all →
       </a>
   </div>
   ```
4. Use `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5` grid.
5. Replace all hardcoded colors (`text-blue-600`, `bg-blue-100`) with semantic tokens (`text-brand-500`, `bg-brand-50`).
6. Add dark mode classes to all elements.
7. If there are tables (recent certificates, top modules), apply admin table pattern.

### Step 3.3 — Verify in browser

Navigate to `/instructor/dashboard` and verify:
- Sidebar renders with correct navigation
- Dark mode toggle works
- All stat cards display properly
- No layout/JS errors in console

### Step 3.4 — Commit

```bash
git add resources/views/instructor/dashboard.blade.php
git commit -m "feat: migrate instructor dashboard to admin layout with dark mode"
```

---

## Task 4: Migrate Module Views (index, create, edit, show)

**Why:** Modules are the core instructor entity. These 4 views establish the pattern that all other CRUD views follow.

### Files

- **Modify:** `resources/views/instructor/modules/index.blade.php`
- **Modify:** `resources/views/instructor/modules/create.blade.php`
- **Modify:** `resources/views/instructor/modules/edit.blade.php`
- **Modify:** `resources/views/instructor/modules/show.blade.php`

### Step 4.1 — Read all four module views

Read each file fully to understand the data being rendered, form fields, and any Alpine.js interactions.

### Step 4.2 — Migrate `modules/index.blade.php`

Key changes:
1. `<x-app-layout>` → `@extends('layouts.instructor')` + `@section('content')` ... `@endsection`
2. Table wrapper: `rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden`
3. Header row: `flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4`
4. Search/filter section: `grid grid-cols-1 sm:grid-cols-4 gap-3` with admin input styling
5. Table header: `bg-gray-50 dark:bg-white/[0.02]` + `text-xs font-semibold uppercase tracking-wider`
6. Table rows: `hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors`
7. Status badges: use admin badge styling with dark mode
8. Action buttons: `rounded-lg px-3 py-1.5 text-sm font-medium transition-colors`
9. "Create Module" button: `bg-brand-500 hover:bg-brand-600 text-white rounded-lg px-4 py-2 shadow-theme-xs transition-colors`

### Step 4.3 — Migrate `modules/create.blade.php`

Key changes:
1. Switch to `@extends('layouts.instructor')`
2. Card wrapper: Admin card pattern
3. All inputs: `w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition`
4. Labels: `text-sm font-medium text-gray-700 dark:text-gray-300`
5. Select elements: Same input styling plus `appearance-none` if needed
6. Submit button: Admin primary button pattern
7. Error display: Red themed card with `border-error-200 dark:border-error-500/30`
8. Back link: `text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200`

### Step 4.4 — Migrate `modules/edit.blade.php`

Same transformation as create, preserving all form field names and values.

### Step 4.5 — Migrate `modules/show.blade.php`

Key changes:
1. Switch to `@extends('layouts.instructor')`
2. Detail sections: Admin card pattern
3. Key-value display: `grid grid-cols-1 sm:grid-cols-2 gap-4` with label+value pairs
4. Action buttons: Admin button styling
5. Related data tables (lessons, quizzes): Admin table pattern

### Step 4.6 — Verify all four views in browser

Navigate to each view:
- `/instructor/modules` — table renders, search works, pagination works
- `/instructor/modules/create` — form renders, validation errors show
- `/instructor/modules/{id}/edit` — form pre-fills, saves correctly
- `/instructor/modules/{id}` — detail page renders

### Step 4.7 — Commit

```bash
git add resources/views/instructor/modules/
git commit -m "feat: migrate instructor module views to admin design"
```

---

## Task 5: Migrate Lesson Views (index, create, edit, show)

### Files

- **Modify:** `resources/views/instructor/lessons/index.blade.php`
- **Modify:** `resources/views/instructor/lessons/create.blade.php`
- **Modify:** `resources/views/instructor/lessons/edit.blade.php`
- **Modify:** `resources/views/instructor/lessons/show.blade.php`
- **Preserve:** `resources/views/instructor/lessons/partials/quiz-modal.blade.php` (update styling only)

### Step 5.1 — Read all lesson views

Read each file. Pay attention to the lesson ordering UI (drag/drop or move buttons) — preserve that functionality.

### Step 5.2 — Migrate `lessons/index.blade.php`

Same pattern as modules/index. Preserve any "move" actions for lesson ordering.

### Step 5.3 — Migrate `lessons/create.blade.php`

Same pattern as modules/create. Preserve module selection dropdown and order field.

### Step 5.4 — Migrate `lessons/edit.blade.php`

Same pattern.

### Step 5.5 — Migrate `lessons/show.blade.php`

Same pattern. Preserve topic listing and any quiz-modal trigger.

### Step 5.6 — Update `lessons/partials/quiz-modal.blade.php`

Apply admin modal/card styling. Preserve Alpine.js toggle logic.

### Step 5.7 — Verify all lesson views in browser

### Step 5.8 — Commit

```bash
git add resources/views/instructor/lessons/
git commit -m "feat: migrate instructor lesson views to admin design"
```

---

## Task 6: Migrate Quiz Views (index, create, edit, show, add-question, edit-question, import-preview)

### Files

- **Modify:** `resources/views/instructor/quizzes/index.blade.php`
- **Modify:** `resources/views/instructor/quizzes/create.blade.php`
- **Modify:** `resources/views/instructor/quizzes/edit.blade.php`
- **Modify:** `resources/views/instructor/quizzes/show.blade.php`
- **Modify:** `resources/views/instructor/quizzes/add-question.blade.php`
- **Modify:** `resources/views/instructor/quizzes/edit-question.blade.php`
- **Modify:** `resources/views/instructor/quizzes/import-preview.blade.php`
- **Preserve:** `resources/views/instructor/quizzes/partials/quiz-modal.blade.php` (update styling only)

### Step 6.1 — Read all quiz views

Pay special attention to the question management flow and CSV import/preview logic.

### Step 6.2 — Migrate `quizzes/index.blade.php`

Admin table pattern. Preserve module filter dropdown.

### Step 6.3 — Migrate `quizzes/create.blade.php`

Admin form pattern. Preserve module/lesson selection, quiz settings.

### Step 6.4 — Migrate `quizzes/edit.blade.php`

Same pattern.

### Step 6.5 — Migrate `quizzes/show.blade.php`

Admin card + table pattern for question listing. Preserve action buttons for add/edit/delete questions.

### Step 6.6 — Migrate `quizzes/add-question.blade.php`

Admin form pattern. Preserve option rows and correct answer selection.

### Step 6.7 — Migrate `quizzes/edit-question.blade.php`

Same pattern as add-question.

### Step 6.8 — Migrate `quizzes/import-preview.blade.php`

Admin card + table pattern for CSV preview data. Preserve confirm/cancel actions.

### Step 6.9 — Update `quizzes/partials/quiz-modal.blade.php`

Admin modal styling.

### Step 6.10 — Verify all quiz views in browser

Test the full quiz lifecycle:
- List → Create → Show with questions → Add question → Edit question → Import preview

### Step 6.11 — Commit

```bash
git add resources/views/instructor/quizzes/
git commit -m "feat: migrate instructor quiz views to admin design"
```

---

## Task 7: Migrate Topic Views (create, edit)

### Files

- **Modify:** `resources/views/instructor/topics/create.blade.php`
- **Modify:** `resources/views/instructor/topics/edit.blade.php`

### Step 7.1 — Read both topic views

These handle 4 content types (video, text, worksheet, interactive). The forms are complex with conditional sections.

### Step 7.2 — Migrate `topics/create.blade.php`

Admin form pattern. Preserve:
- Content type selector (Alpine.js tabs or radio buttons)
- Video URL / file upload
- TinyMCE integration for text content
- File upload for worksheets
- All conditional show/hide logic

### Step 7.3 — Migrate `topics/edit.blade.php`

Same pattern, preserving pre-filled values.

### Step 7.4 — Verify topic creation/editing

Test each content type variant.

### Step 7.5 — Commit

```bash
git add resources/views/instructor/topics/
git commit -m "feat: migrate instructor topic views to admin design"
```

---

## Task 8: Migrate Enrollment Views (index, show)

### Files

- **Modify:** `resources/views/instructor/enrollments/index.blade.php`
- **Modify:** `resources/views/instructor/enrollments/show.blade.php`

### Step 8.1 — Read both enrollment views

### Step 8.2 — Migrate `enrollments/index.blade.php`

Admin table pattern. Preserve approve/reject action buttons.

### Step 8.3 — Migrate `enrollments/show.blade.php`

Admin card pattern for learner details. Preserve approval workflow.

### Step 8.4 — Verify enrollment views

### Step 8.5 — Commit

```bash
git add resources/views/instructor/enrollments/
git commit -m "feat: migrate instructor enrollment views to admin design"
```

---

## Task 9: Migrate User (Learner) Views (index, create, edit, show)

### Files

- **Modify:** `resources/views/instructor/users/index.blade.php`
- **Modify:** `resources/views/instructor/users/create.blade.php`
- **Modify:** `resources/views/instructor/users/edit.blade.php`
- **Modify:** `resources/views/instructor/users/show.blade.php`

### Step 9.1 — Read all user views

### Step 9.2 — Migrate all four views

Use admin `users/index.blade.php` as the exact reference. Mirror its:
- Table structure with role/status badges
- Search + filter bar
- Create/edit form layout
- Show page detail cards

### Step 9.3 — Verify user views

### Step 9.4 — Commit

```bash
git add resources/views/instructor/users/
git commit -m "feat: migrate instructor user views to admin design"
```

---

## Task 10: Migrate Image Library View

### Files

- **Modify:** `resources/views/instructor/image-library/index.blade.php`

### Step 10.1 — Read the image library view

### Step 10.2 — Migrate to admin design

Card grid for images. Upload form with admin styling. Delete buttons.

### Step 10.3 — Verify

### Step 10.4 — Commit

```bash
git add resources/views/instructor/image-library/
git commit -m "feat: migrate instructor image library to admin design"
```

---

## Task 11: Refactor Admin Layout to Use Shared Components

**Why:** Now that the shared sidebar components exist and both layouts are styled consistently, refactor `layouts/admin.blade.php` to use `<x-admin.sidebar-shell>` and `<x-admin.sidebar-link>` components. This removes duplicate sidebar markup from the admin layout.

### Files

- **Modify:** `resources/views/layouts/admin.blade.php`

### Step 11.1 — Read the admin layout

### Step 11.2 — Replace inline sidebar markup with components

Replace the sidebar `<aside>` and mobile backdrop with:
```blade
<x-admin.sidebar-shell :home-route="route('admin.dashboard')">
    <nav class="flex flex-col gap-4">
        {{-- MAIN --}}
        <div>
            <x-admin.sidebar-heading label="Main" />
            <ul class="flex flex-col gap-1">
                <x-admin.sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon='...'>
                    Dashboard
                </x-admin.sidebar-link>
            </ul>
        </div>
        {{-- ... remaining sections with sidebar-link components --}}
    </nav>
</x-admin.sidebar-shell>
```

### Step 11.3 — Verify admin views still work

Navigate to `/admin/dashboard`, `/admin/users`, etc.

### Step 11.4 — Commit

```bash
git add resources/views/layouts/admin.blade.php
git commit -m "refactor: admin layout uses shared sidebar components"
```

---

## Task 12: Clean Up Backup Files

### Files

- **Delete:** `resources/views/instructor/topics/create_broken_backup.blade.php`
- **Delete:** `resources/views/instructor/lessons/create_old_backup.blade.php`
- **Delete:** `resources/views/instructor/lessons/create-backup.blade.php`
- **Delete:** `resources/views/instructor/lessons/edit_old_backup.blade.php`

### Step 12.1 — Confirm backups are not referenced

```bash
grep -r "create_broken_backup\|create_old_backup\|create-backup\|edit_old_backup" resources/ app/ routes/
```

Expected: No references.

### Step 12.2 — Delete and commit

```bash
git rm resources/views/instructor/topics/create_broken_backup.blade.php
git rm resources/views/instructor/lessons/create_old_backup.blade.php
git rm resources/views/instructor/lessons/create-backup.blade.php
git rm resources/views/instructor/lessons/edit_old_backup.blade.php
git commit -m "chore: remove stale instructor view backups"
```

---

## Task 13: Full Smoke Test

### Step 13.1 — Run the test suite

```bash
php artisan test
```

Expected: All existing tests pass.

### Step 13.2 — Manual smoke test checklist

| Route | Expected |
|-------|----------|
| `/instructor/dashboard` | Stats cards, sidebar, dark mode toggle |
| `/instructor/modules` | Table with search, pagination |
| `/instructor/modules/create` | Form with validation |
| `/instructor/modules/{id}` | Detail card with lessons/quizzes |
| `/instructor/modules/{id}/edit` | Pre-filled form |
| `/instructor/lessons` | Table with module filter |
| `/instructor/lessons/create` | Form with module selector |
| `/instructor/quizzes` | Table with module filter |
| `/instructor/quizzes/create` | Form with module/lesson selector |
| `/instructor/quizzes/{id}` | Question list with add/edit/delete |
| `/instructor/quizzes/{id}/add-question` | Question form |
| `/instructor/topics/create?lesson_id=X` | Content type tabs |
| `/instructor/enrollments` | Pending enrollments table |
| `/instructor/enrollments/{id}` | Learner detail + approve/reject |
| `/instructor/users` | Learner table |
| `/instructor/image-library` | Image grid with upload |

### Step 13.3 — Verify sidebar navigation highlights correctly on each page

### Step 13.4 — Test mobile responsiveness (resize browser to <1280px)

- Sidebar should collapse
- Hamburger icon should toggle mobile sidebar
- Backdrop overlay on mobile

### Step 13.5 — Test dark mode on all pages

Toggle dark mode and verify:
- No white-on-white or dark-on-dark text
- All cards, tables, inputs have dark variants
- Sidebar navigation visible in both modes

### Step 13.6 — Final commit

```bash
git add -A
git commit -m "feat: complete instructor UI migration to admin panel design"
```

---

## Summary of New / Modified Files

| Action | Path | Purpose |
|--------|------|---------|
| **Create** | `components/admin/sidebar-shell.blade.php` | Shared sidebar chrome |
| **Create** | `components/admin/sidebar-link.blade.php` | Shared nav link component |
| **Create** | `components/admin/sidebar-heading.blade.php` | Shared section heading |
| **Create** | `layouts/instructor.blade.php` | Instructor layout with sidebar + dark mode |
| **Modify** | `instructor/dashboard.blade.php` | Admin design tokens |
| **Modify** | `instructor/modules/*.blade.php` (4 files) | Admin table/form/card patterns |
| **Modify** | `instructor/lessons/*.blade.php` (4 files + 1 partial) | Same |
| **Modify** | `instructor/quizzes/*.blade.php` (7 files + 1 partial) | Same |
| **Modify** | `instructor/topics/*.blade.php` (2 files) | Same |
| **Modify** | `instructor/enrollments/*.blade.php` (2 files) | Same |
| **Modify** | `instructor/users/*.blade.php` (4 files) | Same |
| **Modify** | `instructor/image-library/index.blade.php` | Same |
| **Modify** | `layouts/admin.blade.php` | Refactor to use shared components |
| **Delete** | 4 backup files | Remove stale copies |

**Total:** 4 new files, ~27 modified files, 4 deleted files. **Zero controller or route changes.**

---

## Architectural Decisions

1. **Why `@extends` instead of `<x-app-layout>`?** — The admin layout uses `@extends('layouts.admin')` with `@section/@yield` for page title, content, styles, and scripts. Instructor should follow the same convention for consistency.

2. **Why components in `components/admin/` not `components/shared/`?** — The sidebar pattern *is* the admin design system. Naming it `admin/` makes it clear these are admin-style components. Instructor views are being brought *to* the admin design, not the other way around.

3. **Why not change controllers?** — Controllers already return the correct view names (`instructor.modules.index`, etc.). Blade resolves `@extends('layouts.instructor')` correctly regardless of controller changes. Zero backend risk.

4. **Why dark mode from day one?** — The admin layout already implements dark mode. Skipping it in instructor views would re-create the styling gap we're trying to close.
