# Instructor Dashboard Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a fully redesigned instructor panel with a dedicated TailAdmin-style layout (collapsible left sidebar, sticky header, search, notifications) and a rich dashboard page with stat cards, recent activities, pending enrollment quick actions, top modules, quiz performance, a module carousel, mini calendar, and quick actions — all styled with the platform's purple brand gradient.

**Architecture:** New `layouts/instructor-app.blade.php` shell with Alpine.js sidebar/notification stores, extending the TailAdmin structural pattern. All instructor views will extend this layout. The existing `layouts/app.blade.php`, `learner-app.blade.php`, and all learner files are untouched. Dashboard sections use the learner dashboard's tinted wrapper + left-border-accent visual pattern.

**Tech Stack:** Laravel 12, Blade, Alpine.js, Tailwind CSS v3, Heroicons (inline SVG), Spatie Laravel Permission. No Chart.js, no FullCalendar, no new npm packages.

**Design doc:** `docs/plans/2026-03-08-instructor-dashboard-design.md`

**Color tokens:**
- Brand gradient: `linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1)`
- Sidebar bg: `linear-gradient(180deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%)`
- Active nav: `bg-white/20 rounded-xl`
- Hover nav: `bg-white/10 rounded-xl`

---

## Task 1: Create the Instructor Layout Shell

**Files:**
- Create: `resources/views/layouts/instructor-app.blade.php`

This is the master shell. It contains the `<html>`, Alpine.js stores, sidebar, header, and `@yield('content')`.

### Step 1: Create the layout file

```blade
{{-- resources/views/layouts/instructor-app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Instructor Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>

    {{-- Alpine sidebar + notification stores --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('instructorSidebar', {
                isExpanded: window.innerWidth >= 1280,
                isMobileOpen: false,
                isHovered: false,
                toggleExpanded() { this.isExpanded = !this.isExpanded; this.isMobileOpen = false; },
                toggleMobileOpen() { this.isMobileOpen = !this.isMobileOpen; },
                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) this.isHovered = val;
                },
            });
        });
    </script>
</head>

<body class="font-[Poppins] antialiased bg-gray-50 h-full"
    x-data
    x-init="
        $store.instructorSidebar.isExpanded = window.innerWidth >= 1280;
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1280) {
                $store.instructorSidebar.isMobileOpen = false;
                $store.instructorSidebar.isExpanded = false;
            } else {
                $store.instructorSidebar.isMobileOpen = false;
                $store.instructorSidebar.isExpanded = true;
            }
        });
    ">

    <div class="xl:flex min-h-screen">

        {{-- ── Mobile backdrop ── --}}
        <div
            x-show="$store.instructorSidebar.isMobileOpen"
            x-cloak
            @click="$store.instructorSidebar.toggleMobileOpen()"
            class="fixed inset-0 bg-black/40 z-[9999] xl:hidden"
        ></div>

        {{-- ══════════════════════════════════════
             SIDEBAR
        ══════════════════════════════════════ --}}
        <aside
            class="fixed top-0 left-0 h-screen z-[99999] flex flex-col transition-all duration-300 ease-in-out border-r border-purple-900/30"
            style="background: linear-gradient(180deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);"
            :class="{
                'w-[280px]': $store.instructorSidebar.isExpanded || $store.instructorSidebar.isMobileOpen || $store.instructorSidebar.isHovered,
                'w-[72px]': !$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isMobileOpen && !$store.instructorSidebar.isHovered,
                'translate-x-0': $store.instructorSidebar.isMobileOpen,
                '-translate-x-full xl:translate-x-0': !$store.instructorSidebar.isMobileOpen,
            }"
            @mouseenter="$store.instructorSidebar.setHovered(true)"
            @mouseleave="$store.instructorSidebar.setHovered(false)"
        >
            {{-- Logo --}}
            <div class="flex items-center gap-3 px-4 py-6 border-b border-white/10"
                :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : 'justify-start'">
                <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-9 h-9 object-contain flex-shrink-0">
                <div x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>
                    <p class="text-white font-bold text-sm leading-tight">ConciousConnections</p>
                    <p class="text-purple-200 text-[10px] tracking-widest uppercase">Instructor Panel</p>
                </div>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6">

                {{-- MAIN --}}
                <div>
                    <p class="text-purple-300 text-[10px] font-semibold tracking-widest uppercase px-2 mb-2"
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>
                        Main
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('instructor.dashboard') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('instructor.dashboard') ? 'bg-white/20 text-white' : 'text-purple-100 hover:bg-white/10 hover:text-white' }}"
                               :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span class="text-sm font-medium" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- ASSESSMENTS --}}
                <div>
                    <p class="text-purple-300 text-[10px] font-semibold tracking-widest uppercase px-2 mb-2"
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>
                        Assessments
                    </p>
                    <ul class="space-y-1">
                        @php $pendingSidebarCount = \App\Models\ModuleEnrollment::pending()->whereHas('module', fn($q) => $q->where('created_by', auth()->id()))->count(); @endphp

                        {{-- Manage Learners --}}
                        <li>
                            <a href="{{ route('instructor.users.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('instructor.users.*') ? 'bg-white/20 text-white' : 'text-purple-100 hover:bg-white/10 hover:text-white' }}"
                               :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="text-sm font-medium flex-1" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>Manage Learners</span>
                            </a>
                        </li>

                        {{-- Manage Modules --}}
                        <li>
                            <a href="{{ route('instructor.modules.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('instructor.modules.*') ? 'bg-white/20 text-white' : 'text-purple-100 hover:bg-white/10 hover:text-white' }}"
                               :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <span class="text-sm font-medium" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>Manage Modules</span>
                            </a>
                        </li>

                        {{-- Manage Lessons --}}
                        <li>
                            <a href="{{ route('instructor.lessons.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('instructor.lessons.*') ? 'bg-white/20 text-white' : 'text-purple-100 hover:bg-white/10 hover:text-white' }}"
                               :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-sm font-medium" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>Manage Lessons</span>
                            </a>
                        </li>

                        {{-- Manage Quizzes --}}
                        <li>
                            <a href="{{ route('instructor.quizzes.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('instructor.quizzes.*') ? 'bg-white/20 text-white' : 'text-purple-100 hover:bg-white/10 hover:text-white' }}"
                               :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span class="text-sm font-medium" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>Manage Quizzes</span>
                            </a>
                        </li>

                        {{-- Assessments Logs (Enrollments) --}}
                        <li>
                            <a href="{{ route('instructor.enrollments.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('instructor.enrollments.*') ? 'bg-white/20 text-white' : 'text-purple-100 hover:bg-white/10 hover:text-white' }}"
                               :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span class="text-sm font-medium flex-1" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>Assessments Logs</span>
                                @if($pendingSidebarCount > 0)
                                    <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold bg-red-500 text-white rounded-full flex-shrink-0"
                                          x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>
                                        {{ $pendingSidebarCount }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- EXTRAS --}}
                <div>
                    <p class="text-purple-300 text-[10px] font-semibold tracking-widest uppercase px-2 mb-2"
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>
                        Extras
                    </p>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('instructor.image-library.index') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('instructor.image-library.*') ? 'bg-white/20 text-white' : 'text-purple-100 hover:bg-white/10 hover:text-white' }}"
                               :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-sm font-medium" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>Extra Features</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            {{-- Bottom — Instructor profile + logout --}}
            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3"
                     :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         style="background: rgba(255,255,255,0.2);">
                        {{ strtoupper(mb_substr(Auth::user()->first_name ?? Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>
                        <p class="text-white text-sm font-semibold truncate">{{ Auth::user()->first_name ?? Auth::user()->name }}</p>
                        <p class="text-purple-200 text-xs truncate">Instructor</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen" x-cloak>
                        @csrf
                        <button type="submit" class="text-purple-200 hover:text-white transition-colors" title="Log out">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- ══════════════════════════════════════
             MAIN WRAPPER
        ══════════════════════════════════════ --}}
        <div class="flex-1 flex flex-col transition-all duration-300 ease-in-out"
             :class="{
                 'xl:ml-[280px]': $store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered,
                 'xl:ml-[72px]': !$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered,
             }">

            {{-- ── HEADER ── --}}
            @include('layouts.instructor-header')

            {{-- ── PAGE CONTENT ── --}}
            <main class="flex-1 p-4 md:p-6 max-w-screen-2xl mx-auto w-full">
                @yield('content')
            </main>

        </div>
    </div>

    {{-- Toast notifications --}}
    @stack('scripts')
    <script>
        function waitForToast(callback, maxAttempts = 50) {
            let attempts = 0;
            const interval = setInterval(() => {
                attempts++;
                if (typeof window.toast !== 'undefined') { clearInterval(interval); callback(); }
                else if (attempts >= maxAttempts) { clearInterval(interval); }
            }, 100);
        }
        waitForToast(function() {
            @if(session('success')) window.toast.success("{{ addslashes(session('success')) }}"); @endif
            @if(session('error')) window.toast.error("{{ addslashes(session('error')) }}"); @endif
            @if($errors->any())
                @foreach($errors->all() as $error) window.toast.error("{{ addslashes($error) }}"); @endforeach
            @endif
        });
    </script>
</body>
</html>
```

### Step 2: Create the header partial — `resources/views/layouts/instructor-header.blade.php`

```blade
{{-- resources/views/layouts/instructor-header.blade.php --}}
<header class="sticky top-0 z-[9998] bg-white border-b border-gray-200 h-16 flex items-center px-4 md:px-6 gap-4"
        x-data="{ notifOpen: false, searchOpen: false }">

    {{-- Sidebar toggle --}}
    <button @click="$store.instructorSidebar.toggleExpanded()"
            class="hidden xl:flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    <button @click="$store.instructorSidebar.toggleMobileOpen()"
            class="flex xl:hidden items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Search bar --}}
    <div class="flex-1 max-w-lg relative" x-data="instructorSearch()">
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>
            <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="open = true" @click.away="open = false"
                   placeholder="Search modules, lessons, learners..."
                   class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-400 transition-all">
        </div>
        {{-- Dropdown results --}}
        <div x-show="open && (results.modules.length || results.lessons.length || results.learners.length)" x-cloak
             class="absolute top-full mt-1 left-0 right-0 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
            <template x-if="results.modules.length">
                <div class="p-2">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 mb-1">Modules</p>
                    <template x-for="item in results.modules" :key="item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 text-sm text-gray-700 hover:text-purple-700 transition-colors">
                            <span x-text="item.title"></span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="results.lessons.length">
                <div class="p-2 border-t border-gray-50">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 mb-1">Lessons</p>
                    <template x-for="item in results.lessons" :key="item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 text-sm text-gray-700 hover:text-purple-700 transition-colors">
                            <span x-text="item.title"></span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="results.learners.length">
                <div class="p-2 border-t border-gray-50">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 mb-1">Learners</p>
                    <template x-for="item in results.learners" :key="item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 text-sm text-gray-700 hover:text-purple-700 transition-colors">
                            <span x-text="item.name"></span>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <div class="flex items-center gap-3 ml-auto">
        {{-- Notification bell --}}
        @php $headerPendingCount = \App\Models\ModuleEnrollment::pending()->whereHas('module', fn($q) => $q->where('created_by', auth()->id()))->with(['user','module'])->latest()->limit(10)->get(); @endphp
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                    class="relative w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @if($headerPendingCount->count() > 0)
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
                        {{ $headerPendingCount->count() }}
                    </span>
                @endif
            </button>

            {{-- Notification dropdown --}}
            <div x-show="open" @click.away="open = false" x-cloak
                 class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-900">Pending Enrollments</h3>
                </div>
                @forelse($headerPendingCount as $enrollment)
                    <div class="px-4 py-3 border-b border-gray-50 last:border-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $enrollment->user->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $enrollment->module->title }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $enrollment->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex gap-1 flex-shrink-0">
                                <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs px-2 py-1 bg-green-100 text-green-700 hover:bg-green-600 hover:text-white rounded-lg transition-colors font-medium">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-xs px-2 py-1 bg-red-50 text-red-600 hover:bg-red-500 hover:text-white rounded-lg transition-colors font-medium">Reject</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-center text-sm text-gray-400">No pending requests</div>
                @endforelse
                <div class="px-4 py-2.5 border-t border-gray-100">
                    <a href="{{ route('instructor.enrollments.index') }}" class="text-xs text-purple-600 font-medium hover:text-purple-800">View all requests →</a>
                </div>
            </div>
        </div>

        {{-- Avatar dropdown --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                    style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                {{ strtoupper(mb_substr(Auth::user()->first_name ?? Auth::user()->name, 0, 1)) }}
            </button>
            <div x-show="open" @click.away="open = false" x-cloak
                 class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">Log Out</button>
                </form>
            </div>
        </div>
    </div>
</header>

@push('scripts')
<script>
function instructorSearch() {
    return {
        query: '',
        open: false,
        results: { modules: [], lessons: [], learners: [] },
        async search() {
            if (this.query.length < 2) { this.results = { modules: [], lessons: [], learners: [] }; return; }
            try {
                const res = await fetch(`/instructor/search?q=${encodeURIComponent(this.query)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.results = await res.json();
                this.open = true;
            } catch(e) {}
        }
    }
}
</script>
@endpush
```

### Step 3: Verify no test needed for pure layout (no business logic)

Pure Blade layout — no unit test required. Smoke-tested visually in Task 6.

### Step 4: Commit

```bash
git add resources/views/layouts/instructor-app.blade.php resources/views/layouts/instructor-header.blade.php
git commit -m "feat(instructor): add dedicated instructor layout shell with sidebar and header"
```

---

## Task 2: Add Search Route + Controller

**Files:**
- Create: `app/Http/Controllers/Instructor/SearchController.php`
- Modify: `routes/web.php`

### Step 1: Write the failing test

```php
// tests/Feature/Instructor/SearchControllerTest.php
public function test_instructor_can_search_modules(): void
{
    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');
    Module::factory()->create(['title' => 'Puberty Basics', 'created_by' => $instructor->id]);

    $this->actingAs($instructor)
         ->getJson(route('instructor.search', ['q' => 'Puberty']))
         ->assertOk()
         ->assertJsonStructure(['modules', 'lessons', 'learners']);
}
```

### Step 2: Run test — expect FAIL (route not found)

```bash
php artisan test --filter=test_instructor_can_search_modules
```

### Step 3: Add route inside instructor group in `routes/web.php`

```php
Route::get('search', [\App\Http\Controllers\Instructor\SearchController::class, 'index'])->name('search');
```

### Step 4: Create `SearchController`

```php
<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = substr(strip_tags($request->input('q', '')), 0, 100);

        if (strlen($q) < 2) {
            return response()->json(['modules' => [], 'lessons' => [], 'learners' => []]);
        }

        $instructorId = Auth::id();

        $modules = Module::where('created_by', $instructorId)
            ->where('title', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'title'])
            ->map(fn($m) => ['id' => $m->id, 'title' => $m->title, 'url' => route('instructor.modules.edit', $m)]);

        $lessons = Lesson::whereHas('module', fn($mq) => $mq->where('created_by', $instructorId))
            ->where('title', 'like', "%{$q}%")
            ->limit(5)
            ->get(['id', 'title'])
            ->map(fn($l) => ['id' => $l->id, 'title' => $l->title, 'url' => route('instructor.lessons.edit', $l)]);

        $learners = User::role('learner')
            ->whereHas('moduleEnrollments.module', fn($mq) => $mq->where('created_by', $instructorId))
            ->where(fn($uq) => $uq->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"))
            ->limit(5)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->first_name . ' ' . $u->last_name, 'url' => route('instructor.users.show', $u)]);

        return response()->json(compact('modules', 'lessons', 'learners'));
    }
}
```

### Step 5: Run test — expect PASS

```bash
php artisan test --filter=test_instructor_can_search_modules
```

### Step 6: Commit

```bash
git add app/Http/Controllers/Instructor/SearchController.php routes/web.php tests/Feature/Instructor/SearchControllerTest.php
git commit -m "feat(instructor): add instructor search endpoint"
```

---

## Task 3: Update `Instructor\DashboardController`

**Files:**
- Modify: `app/Http/Controllers/Instructor/DashboardController.php`

### Step 1: Write the failing test

```php
// tests/Feature/Instructor/DashboardTest.php
public function test_instructor_dashboard_returns_all_required_view_data(): void
{
    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');

    $this->actingAs($instructor)
         ->get(route('instructor.dashboard'))
         ->assertOk()
         ->assertViewHas(['stats', 'recentActivities', 'pendingEnrollments', 'moduleStats', 'quizStats', 'instructorModules', 'calendarDates']);
}
```

### Step 2: Run test — expect FAIL (view data missing)

```bash
php artisan test --filter=test_instructor_dashboard_returns_all_required_view_data
```

### Step 3: Rewrite the controller

```php
<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $instructorId = Auth::id();

        // Scoped stats — instructor's own modules only
        $myModuleIds = Module::where('created_by', $instructorId)->pluck('id');

        $stats = [
            'total_learners'      => ModuleEnrollment::whereIn('module_id', $myModuleIds)->distinct('user_id')->count('user_id'),
            'total_modules'       => Module::where('created_by', $instructorId)->count(),
            'published_modules'   => Module::where('created_by', $instructorId)->where('is_published', true)->count(),
            'total_quizzes'       => Quiz::whereIn('module_id', $myModuleIds)->count(),
            'pending_enrollments' => ModuleEnrollment::pending()->whereIn('module_id', $myModuleIds)->count(),
            'enrolled_learners'   => ModuleEnrollment::whereIn('module_id', $myModuleIds)->where('status', 'approved')->count(),
        ];

        // Recent activities — last 10 enrollments across instructor's modules
        $recentActivities = ModuleEnrollment::with(['user', 'module'])
            ->whereIn('module_id', $myModuleIds)
            ->latest()
            ->limit(10)
            ->get();

        // Pending enrollments for dashboard section (limit 5)
        $pendingEnrollments = ModuleEnrollment::pending()
            ->with(['user', 'module'])
            ->whereIn('module_id', $myModuleIds)
            ->latest()
            ->limit(5)
            ->get();

        // Top modules by enrollment (limit 5)
        $moduleStats = Module::withCount('enrollments')
            ->where('created_by', $instructorId)
            ->orderBy('enrollments_count', 'desc')
            ->limit(5)
            ->get();

        // Quiz performance summary (limit 5)
        $quizStats = Quiz::whereIn('module_id', $myModuleIds)
            ->with('module:id,title')
            ->withCount('attempts')
            ->withAvg('attempts', 'score')
            ->limit(5)
            ->get();

        // All instructor modules for carousel
        $instructorModules = Module::where('created_by', $instructorId)
            ->withCount(['enrollments', 'enrollments as completed_count' => fn($q) => $q->whereNotNull('completed_at')])
            ->latest()
            ->get();

        // Calendar activity dots — enrollment dates this month
        $calendarDates = ModuleEnrollment::whereIn('module_id', $myModuleIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->pluck('created_at')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->unique()
            ->values()
            ->toArray();

        return view('instructor.dashboard', compact(
            'stats', 'recentActivities', 'pendingEnrollments',
            'moduleStats', 'quizStats', 'instructorModules', 'calendarDates'
        ));
    }
}
```

### Step 4: Run test — expect PASS

```bash
php artisan test --filter=test_instructor_dashboard_returns_all_required_view_data
```

### Step 5: Commit

```bash
git add app/Http/Controllers/Instructor/DashboardController.php tests/Feature/Instructor/DashboardTest.php
git commit -m "feat(instructor): update dashboard controller with scoped stats and all view data"
```

---

## Task 4: Rewrite `instructor/dashboard.blade.php`

**Files:**
- Modify: `resources/views/instructor/dashboard.blade.php`

No test needed — pure Blade view. Smoke-tested in Task 6.

### Step 1: Rewrite the view

```blade
@extends('layouts.instructor-app')

@section('title', 'Dashboard')

@section('content')

{{-- Page heading --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Instructor Dashboard</h1>
    <p class="text-sm text-gray-400 mt-1">Welcome back, {{ Auth::user()->first_name ?? Auth::user()->name }}. Here's what's happening today.</p>
</div>

{{-- ─────────────────────────────────────────────
     STAT CARDS ROW
───────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">

    @php
    $statCards = [
        ['label' => 'Total Learners',      'value' => $stats['total_learners'],    'route' => route('instructor.users.index'),       'icon' => 'users'],
        ['label' => 'Published Modules',   'value' => $stats['published_modules'].'/'.$stats['total_modules'], 'route' => route('instructor.modules.index'), 'icon' => 'book'],
        ['label' => 'Total Quizzes',       'value' => $stats['total_quizzes'],     'route' => route('instructor.quizzes.index'),     'icon' => 'clipboard'],
        ['label' => 'Pending Requests',    'value' => $stats['pending_enrollments'], 'route' => route('instructor.enrollments.index'), 'icon' => 'clock', 'alert' => $stats['pending_enrollments'] > 0],
        ['label' => 'Enrolled Learners',   'value' => $stats['enrolled_learners'], 'route' => route('instructor.users.index'),       'icon' => 'check-circle'],
    ];
    @endphp

    @foreach($statCards as $card)
    <a href="{{ $card['route'] }}"
       class="relative rounded-2xl p-4 text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden {{ ($card['alert'] ?? false) ? 'ring-2 ring-red-400 ring-offset-1' : '' }}"
       style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <p class="text-2xl font-bold">{{ $card['value'] }}</p>
        <p class="text-xs text-purple-100 mt-1">{{ $card['label'] }}</p>
        {{-- Icon watermark --}}
        <div class="absolute -right-3 -bottom-3 opacity-20">
            @if($card['icon'] === 'users')
                <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @elseif($card['icon'] === 'book')
                <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            @elseif($card['icon'] === 'clipboard')
                <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            @elseif($card['icon'] === 'clock')
                <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @else
                <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @endif
        </div>
    </a>
    @endforeach
</div>

{{-- ─────────────────────────────────────────────
     TWO-COLUMN CONTENT AREA
───────────────────────────────────────────── --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- ══ LEFT COLUMN ══ --}}
    <div class="xl:col-span-2 space-y-6">

        {{-- A. RECENT ACTIVITIES --}}
        <section class="bg-purple-50/40 rounded-2xl p-5 border border-purple-100/60">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-purple-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900">Recent Activities</h2>
                    <p class="text-xs text-gray-400">Latest enrollments and completions in your modules</p>
                </div>
                <a href="{{ route('instructor.enrollments.index') }}"
                   class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-purple-100 text-purple-700 hover:bg-purple-600 hover:text-white transition-all duration-200">
                    View All
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>

            @if($recentActivities->isEmpty())
                <div class="bg-white rounded-xl border border-dashed border-gray-200 p-8 text-center">
                    <p class="text-sm text-gray-400">No recent activity yet. Share your modules so learners can enroll!</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($recentActivities as $activity)
                    <div class="bg-white rounded-xl px-4 py-3 flex items-center justify-between gap-3 border border-gray-100 hover:border-purple-200 transition-colors">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                {{ $activity->user->name }} enrolled in <span class="text-purple-700">{{ $activity->module->title }}</span>
                            </p>
                            @if($activity->module->age_bracket)
                                <span class="inline-block mt-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">
                                    Age {{ $activity->module->age_bracket }}
                                </span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0 bg-gray-100 px-2 py-1 rounded-lg">
                            {{ $activity->created_at->diffForHumans() }}
                        </span>
                    </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- B. PENDING ENROLLMENT QUICK ACTIONS --}}
        @if($pendingEnrollments->isNotEmpty())
        <section class="bg-amber-50/40 rounded-2xl p-5 border border-amber-100/60">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-amber-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900">Pending Enrollment Requests</h2>
                    <p class="text-xs text-gray-400">Review and take action on learner enrollment requests</p>
                </div>
                <a href="{{ route('instructor.enrollments.index') }}"
                   class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 hover:bg-amber-500 hover:text-white transition-all duration-200">
                    View All
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
            <div class="space-y-2">
                @foreach($pendingEnrollments as $enrollment)
                <div class="bg-white rounded-xl px-4 py-3 flex items-center gap-3 border border-amber-100 hover:border-amber-300 transition-colors">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                        {{ strtoupper(mb_substr($enrollment->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $enrollment->user->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $enrollment->module->title }}</p>
                    </div>
                    @if($enrollment->module->age_bracket)
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 flex-shrink-0">Age {{ $enrollment->module->age_bracket }}</span>
                    @endif
                    <span class="text-xs text-gray-400 flex-shrink-0">{{ $enrollment->created_at->diffForHumans() }}</span>
                    <div class="flex gap-2 flex-shrink-0">
                        <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-xs px-3 py-1.5 bg-green-100 text-green-700 hover:bg-green-600 hover:text-white rounded-lg transition-colors font-semibold">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-xs px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-500 hover:text-white rounded-lg transition-colors font-semibold">Reject</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- C. TOP MODULES BY ENROLLMENT --}}
        <section class="bg-indigo-50/30 rounded-2xl p-5 border border-indigo-100/50">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-indigo-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900">Top Modules by Enrollment</h2>
                    <p class="text-xs text-gray-400">Your most popular modules</p>
                </div>
                <a href="{{ route('instructor.modules.index') }}"
                   class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-all duration-200">
                    View All
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
            @if($moduleStats->isEmpty())
                <div class="bg-white rounded-xl border border-dashed border-gray-200 p-6 text-center">
                    <p class="text-sm text-gray-400">No modules yet.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($moduleStats as $i => $module)
                    <div class="bg-white rounded-xl px-4 py-3 flex items-center gap-3 border border-gray-100 hover:border-indigo-200 transition-colors">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-indigo-600 bg-indigo-100 flex-shrink-0">{{ $i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $module->title }}</p>
                            @if($module->age_bracket)
                                <p class="text-xs text-gray-400">Age {{ $module->age_bracket }}</p>
                            @endif
                        </div>
                        <span class="text-xs font-bold px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 flex-shrink-0">
                            {{ $module->enrollments_count }} enrolled
                        </span>
                    </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- D. QUIZ PERFORMANCE SUMMARY --}}
        <section class="bg-green-50/30 rounded-2xl p-5 border border-green-100/50">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-green-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900">Quiz Performance Summary</h2>
                    <p class="text-xs text-gray-400">Average scores and attempt rates across your quizzes</p>
                </div>
                <a href="{{ route('instructor.quizzes.index') }}"
                   class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-green-100 text-green-700 hover:bg-green-600 hover:text-white transition-all duration-200">
                    View All
                    <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </a>
            </div>
            @if($quizStats->isEmpty())
                <div class="bg-white rounded-xl border border-dashed border-gray-200 p-6 text-center">
                    <p class="text-sm text-gray-400">No quiz attempts yet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase tracking-wider">
                                <th class="text-left pb-2 font-semibold">Module</th>
                                <th class="text-left pb-2 font-semibold">Quiz</th>
                                <th class="text-right pb-2 font-semibold">Attempts</th>
                                <th class="text-right pb-2 font-semibold">Avg Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($quizStats as $quiz)
                            <tr class="hover:bg-white/60 transition-colors">
                                <td class="py-2.5 text-gray-500 text-xs truncate max-w-[120px]">{{ $quiz->module->title ?? '—' }}</td>
                                <td class="py-2.5 font-medium text-gray-800 truncate max-w-[140px]">{{ $quiz->title }}</td>
                                <td class="py-2.5 text-right text-gray-600">{{ $quiz->attempts_count }}</td>
                                <td class="py-2.5 text-right">
                                    <span class="font-semibold {{ ($quiz->attempts_avg_score ?? 0) >= 75 ? 'text-green-600' : 'text-amber-600' }}">
                                        {{ $quiz->attempts_avg_score ? round($quiz->attempts_avg_score, 1).'%' : '—' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    {{-- ══ RIGHT COLUMN ══ --}}
    <div class="space-y-4">

        {{-- E. YOUR MODULES CAROUSEL --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"
             x-data="{ current: 0, get max() { return Math.max(0, {{ $instructorModules->count() }} - 2); } }">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Your Modules</h2>
                <a href="{{ route('instructor.modules.index') }}" class="text-xs text-purple-600 font-semibold hover:text-purple-800">View all →</a>
            </div>

            @if($instructorModules->isEmpty())
                <div class="border border-dashed border-gray-200 rounded-xl p-8 text-center">
                    <p class="text-sm text-gray-400 mb-3">No modules yet.</p>
                    <a href="{{ route('instructor.modules.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold text-white"
                       style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                        + Create First Module
                    </a>
                </div>
            @else
                <div class="relative overflow-hidden">
                    <div class="flex gap-3 transition-transform duration-300"
                         :style="`transform: translateX(calc(-${current} * 50%))`">
                        @foreach($instructorModules as $mod)
                        <div class="w-1/2 flex-shrink-0 rounded-xl overflow-hidden border border-gray-100 shadow-sm group">
                            {{-- Thumbnail --}}
                            <div class="relative aspect-video overflow-hidden">
                                @if($mod->thumbnail)
                                    <img src="{{ asset('storage/'.$mod->thumbnail) }}" alt="{{ $mod->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center"
                                         style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                        <svg class="w-8 h-8 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            {{-- Info --}}
                            <div class="p-2.5">
                                <p class="text-xs font-bold text-gray-900 truncate">{{ $mod->title }}</p>
                                @if($mod->age_bracket)
                                    <p class="text-[10px] text-gray-400 mt-0.5">Age {{ $mod->age_bracket }}</p>
                                @endif
                                <p class="text-[10px] text-gray-400">{{ $mod->enrollments_count }} enrolled · {{ $mod->completed_count }} completed</p>
                                <p class="text-[10px] text-gray-400">Updated {{ $mod->updated_at->diffForHumans() }}</p>
                                <div class="flex gap-2 mt-2">
                                    <a href="{{ route('instructor.modules.edit', $mod) }}"
                                       class="flex-1 text-center text-[10px] font-semibold py-1 rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white transition-colors">Edit</a>
                                    <a href="{{ route('instructor.modules.show', $mod) }}"
                                       class="flex-1 text-center text-[10px] font-semibold py-1 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-200 transition-colors">View</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @if($instructorModules->count() > 2)
                <div class="flex justify-between mt-3">
                    <button @click="current = Math.max(0, current - 1)"
                            class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-purple-100 hover:text-purple-600 transition-colors disabled:opacity-30"
                            :disabled="current === 0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button @click="current = Math.min(max, current + 1)"
                            class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-purple-100 hover:text-purple-600 transition-colors disabled:opacity-30"
                            :disabled="current >= max">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                @endif
            @endif
        </div>

        {{-- F. MINI CALENDAR (reuse learner component, adapted) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4"
             x-data="{
                 today: new Date(),
                 current: new Date(),
                 activityDates: {{ json_encode($calendarDates) }},
                 get monthName() { return this.current.toLocaleString('default', { month: 'long' }); },
                 get year() { return this.current.getFullYear(); },
                 get daysInMonth() { return new Date(this.current.getFullYear(), this.current.getMonth()+1, 0).getDate(); },
                 get firstDayOfMonth() { return new Date(this.current.getFullYear(), this.current.getMonth(), 1).getDay(); },
                 get days() {
                     let d = [];
                     for (let i=0; i<this.firstDayOfMonth; i++) d.push(null);
                     for (let n=1; n<=this.daysInMonth; n++) d.push(n);
                     return d;
                 },
                 isToday(day) {
                     return day && day === this.today.getDate() && this.current.getMonth() === this.today.getMonth() && this.current.getFullYear() === this.today.getFullYear();
                 },
                 hasActivity(day) {
                     if (!day) return false;
                     const m = String(this.current.getMonth()+1).padStart(2,'0');
                     const d2 = String(day).padStart(2,'0');
                     return this.activityDates.includes(`${this.current.getFullYear()}-${m}-${d2}`);
                 },
                 prevMonth() { this.current = new Date(this.current.getFullYear(), this.current.getMonth()-1, 1); },
                 nextMonth() { this.current = new Date(this.current.getFullYear(), this.current.getMonth()+1, 1); },
             }">
            <div class="flex items-center justify-between mb-3">
                <button @click="prevMonth()" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                </button>
                <span class="text-sm font-semibold text-gray-800" x-text="monthName + ' ' + year"></span>
                <button @click="nextMonth()" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
            <div class="grid grid-cols-7 text-center mb-1">
                @foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $d)
                    <div class="text-[10px] font-semibold text-gray-400 py-1">{{ $d }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7 text-center gap-y-0.5">
                <template x-for="(day, index) in days" :key="index">
                    <div class="relative flex flex-col items-center">
                        <button
                            class="w-7 h-7 rounded-full text-xs transition-colors"
                            :class="{
                                'bg-purple-600 text-white font-bold': isToday(day),
                                'text-gray-700 hover:bg-gray-100': day && !isToday(day),
                                'invisible': !day,
                            }"
                            x-text="day || ''"
                        ></button>
                        <span x-show="hasActivity(day)" class="absolute bottom-0 w-1 h-1 rounded-full bg-purple-500"></span>
                    </div>
                </template>
            </div>
            <p class="text-[10px] text-gray-400 mt-2 text-center">Purple dots = enrollment activity</p>
        </div>

        {{-- G. QUICK ACTIONS --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('instructor.modules.create') }}"
                   class="flex flex-col items-center gap-1 p-3 rounded-xl bg-purple-50 hover:bg-purple-600 text-purple-700 hover:text-white transition-all duration-200 text-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span class="text-xs font-semibold">Create Module</span>
                </a>
                <a href="{{ route('instructor.lessons.create') }}"
                   class="flex flex-col items-center gap-1 p-3 rounded-xl bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white transition-all duration-200 text-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-xs font-semibold">Add Lesson</span>
                </a>
                <a href="{{ route('instructor.quizzes.create') }}"
                   class="flex flex-col items-center gap-1 p-3 rounded-xl bg-green-50 hover:bg-green-600 text-green-700 hover:text-white transition-all duration-200 text-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                    <span class="text-xs font-semibold">Create Quiz</span>
                </a>
                <a href="{{ route('instructor.enrollments.index') }}"
                   class="flex flex-col items-center gap-1 p-3 rounded-xl bg-amber-50 hover:bg-amber-500 text-amber-700 hover:text-white transition-all duration-200 text-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span class="text-xs font-semibold">Enrollments</span>
                </a>
            </div>
        </div>

    </div>
</div>

@endsection
```

### Step 2: Commit

```bash
git add resources/views/instructor/dashboard.blade.php
git commit -m "feat(instructor): rewrite dashboard view with new layout and all content sections"
```

---

## Task 5: Update All Existing Instructor Views to Extend New Layout

**Files:**
- Modify: all `resources/views/instructor/**/*.blade.php` files currently using `<x-app-layout>`

### Step 1: Find which views use `<x-app-layout>`

```bash
grep -rl "<x-app-layout>" resources/views/instructor/
```

### Step 2: For each file, replace the layout wrapper

**Before:**
```blade
<x-app-layout>
    <x-slot name="header">...</x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            ...content...
        </div>
    </div>
</x-app-layout>
```

**After:**
```blade
@extends('layouts.instructor-app')

@section('title', 'Page Title')

@section('content')
    ...content...
@endsection
```

Do this for every instructor view file found in step 1.

### Step 3: Run the full test suite to confirm nothing broke

```bash
php artisan test --testsuite=Feature
```

### Step 4: Commit

```bash
git add resources/views/instructor/
git commit -m "feat(instructor): migrate all instructor views to new instructor-app layout"
```

---

## Task 6: Smoke Test & Visual QA

### Step 1: Start dev server

```bash
npm run dev
php artisan serve
```

### Step 2: Log in as an instructor and visit:

- `/instructor/dashboard` — full dashboard renders, sidebar visible, all sections visible
- Resize window below 1280px — sidebar collapses, hamburger appears
- Hover sidebar when collapsed — expands on hover
- Click hamburger on mobile — mobile overlay opens
- Type in search bar — results dropdown appears
- Bell icon — notification dropdown shows pending requests with Approve/Reject buttons
- Approve a pending enrollment — page reloads, enrollment disappears from pending list
- Stat card "Pending Requests" — red ring visible if count > 0, absent if 0
- Calendar shows purple dots on days with enrollment activity
- Carousel arrows work when more than 2 modules exist
- All quick action buttons navigate correctly

### Step 3: Run full test suite

```bash
php artisan test
```

Expected: all tests pass (or at worst, pre-existing failures unrelated to this feature).

### Step 4: Final commit

```bash
git add .
git commit -m "feat(instructor): complete instructor dashboard redesign with new layout"
```

---

## Summary of All Files Created/Modified

| Action | File |
|---|---|
| CREATE | `resources/views/layouts/instructor-app.blade.php` |
| CREATE | `resources/views/layouts/instructor-header.blade.php` |
| CREATE | `app/Http/Controllers/Instructor/SearchController.php` |
| CREATE | `tests/Feature/Instructor/SearchControllerTest.php` |
| CREATE | `tests/Feature/Instructor/DashboardTest.php` |
| MODIFY | `app/Http/Controllers/Instructor/DashboardController.php` |
| MODIFY | `resources/views/instructor/dashboard.blade.php` |
| MODIFY | `routes/web.php` (add search route) |
| MODIFY | All `resources/views/instructor/**/*.blade.php` using `<x-app-layout>` |
