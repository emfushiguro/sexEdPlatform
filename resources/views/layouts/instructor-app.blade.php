<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme-lock="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Instructor Panel</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Instructor UI is locked to light mode. --}}
    <script>
        (function () {
            localStorage.setItem('theme', 'light');
            document.documentElement.classList.remove('dark');
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{--
        Register instructor sidebar store via alpine:init so it is available
        before Alpine scans the DOM — independent of the Vite build hash.
        (TailAdmin pattern: stores for a layout live in the layout head)
    --}}
    <script>
        window.instructorSidebar = {};

        document.addEventListener('alpine:init', () => {
            Alpine.store('instructorSidebar', {
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
                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },
                setHovered(val) {
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <style>[x-cloak] { display: none !important; }</style>
    @stack('head')
</head>

<body
    class="font-[Poppins] antialiased bg-gray-50 h-full instructor-theme-brand"
    x-data="instructorSidebar"
    x-init="
        $store.instructorSidebar.isExpanded = window.innerWidth >= 1280;
        const _checkInstructorMobile = () => {
            if (window.innerWidth < 1280) {
                $store.instructorSidebar.isMobileOpen = false;
                $store.instructorSidebar.isExpanded = false;
            } else {
                $store.instructorSidebar.isMobileOpen = false;
                $store.instructorSidebar.isExpanded = true;
            }
        };
        window.addEventListener('resize', _checkInstructorMobile);
    "
>

    <div class="xl:flex min-h-screen">

        {{-- ── Mobile backdrop ── --}}
        <div
            x-show="$store.instructorSidebar.isMobileOpen"
            x-cloak
            @click="$store.instructorSidebar.setMobileOpen(false)"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/40 z-[9999] xl:hidden"
        ></div>

        {{-- ══════════════════════════════════════
             SIDEBAR
        ══════════════════════════════════════ --}}
        <aside
            id="instructor-sidebar"
            class="fixed top-0 left-0 h-screen z-[99999] flex flex-col bg-white border-r border-gray-200 transition-all duration-300 ease-in-out"
            :class="{
                'w-[280px]': $store.instructorSidebar.isExpanded || $store.instructorSidebar.isMobileOpen || $store.instructorSidebar.isHovered,
                'w-[84px]': !$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isMobileOpen && !$store.instructorSidebar.isHovered,
                'translate-x-0': $store.instructorSidebar.isMobileOpen,
                '-translate-x-full xl:translate-x-0': !$store.instructorSidebar.isMobileOpen,
            }"
            @mouseenter="$store.instructorSidebar.setHovered(true)"
            @mouseleave="$store.instructorSidebar.setHovered(false)"
        >
            {{-- Logo --}}
            <div
                class="flex items-center px-5 py-5 border-b border-gray-100 overflow-hidden"
                :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : 'justify-start'"
            >
                <a href="{{ route('instructor.dashboard') }}" class="flex items-center gap-3 min-w-0">
                    <img
                        src="/media/Logo.png"
                        alt="Conscious Connections"
                        class="w-10 h-10 flex-shrink-0 rounded-lg object-contain"
                    >
                    <div
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                        x-cloak
                        class="overflow-hidden"
                    >
                        <p class="text-lg font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-brand-700 to-brand-500 leading-tight whitespace-nowrap">Conscious <br> Connections</p>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mt-1">Instructor Panel</p>
                    </div>
                </a>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6">

                @php
                $pendingCount = \App\Models\ModuleEnrollment::pending()
                    ->whereHas('module', fn($q) => $q->where('created_by', auth()->id()))
                    ->count();

                $navGroups = [
                    [
                        'label' => 'MAIN',
                        'items' => [
                            ['label' => 'Dashboard', 'route' => 'instructor.dashboard', 'active' => request()->routeIs('instructor.dashboard'), 'badge' => 0, 'icon' => 'grid'],
                            ['label' => 'Profile', 'route' => 'instructor.profile.show', 'active' => request()->routeIs('instructor.profile.*'), 'badge' => 0, 'icon' => 'users'],
                        ],
                    ],
                    [
                        'label' => 'ASSESSMENTS',
                        'items' => [
                            ['label' => 'Learners',     'route' => 'instructor.users.index',       'active' => request()->routeIs('instructor.users.*'),       'badge' => 0,            'icon' => 'users'],
                            ['label' => 'Modules',     'route' => 'instructor.modules.index',     'active' => request()->routeIs('instructor.modules.*'),     'badge' => 0,            'icon' => 'book'],
                            ['label' => 'Lessons',     'route' => 'instructor.lessons.index',     'active' => request()->routeIs('instructor.lessons.*'),     'badge' => 0,            'icon' => 'document'],
                            ['label' => 'Quizzes',     'route' => 'instructor.quizzes.index',     'active' => request()->routeIs('instructor.quizzes.*'),     'badge' => 0,            'icon' => 'clipboard'],
                            ['label' => 'Assessment Logs', 'route' => 'instructor.assessments.index', 'active' => request()->routeIs('instructor.assessments.*'), 'badge' => 0,            'icon' => 'chart'],
                            ['label' => 'Enrollments',  'route' => 'instructor.enrollments.index', 'active' => request()->routeIs('instructor.enrollments.*'), 'badge' => $pendingCount, 'icon' => 'enrollments'],
                        ],
                    ],
                    [
                        'label' => 'COMMUNITY TOOLS',
                        'items' => [
                            ['label' => 'Chat', 'route' => 'chat.page', 'active' => request()->routeIs('chat.*'), 'badge' => 0, 'icon' => 'chat'],
                            ['label' => 'Earnings', 'route' => 'instructor.earnings.index', 'active' => request()->routeIs('instructor.earnings.*'), 'badge' => 0, 'icon' => 'wallet'],
                        ],
                    ],
                ];
                @endphp

                @foreach($navGroups as $group)
                <div>
                    <p
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                        x-cloak
                        class="px-3 mb-2 text-[10px] font-semibold tracking-[0.16em] text-gray-400 uppercase"
                    >{{ $group['label'] }}</p>
                    <ul class="space-y-1">
                        @foreach($group['items'] as $item)
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 overflow-hidden whitespace-nowrap {{ $item['active'] ? 'text-white shadow-sm bg-gradient-to-r from-brand-500 via-brand-700 to-brand-900' : 'text-gray-600 hover:bg-brand-50 hover:text-brand-700' }}"
                                :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''"
                            >
                                {{-- Icon --}}
                                <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ $item['active'] ? 'text-white' : 'text-gray-500 group-hover:text-brand-600' }}">
                                    @if($item['icon'] === 'grid')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    @elseif($item['icon'] === 'users')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    @elseif($item['icon'] === 'book')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    @elseif($item['icon'] === 'document')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    @elseif($item['icon'] === 'clipboard')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    @elseif($item['icon'] === 'chart')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    @elseif($item['icon'] === 'sparkles')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    @elseif($item['icon'] === 'wallet')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m0-6h3m0 0v6m0-6a2 2 0 100-4h-3a2 2 0 000 4h3z" />
                                    </svg>
                                    @elseif($item['icon'] === 'chat')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    @elseif($item['icon'] === 'enrollments')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5V9a2 2 0 00-2-2h-4m1 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v15h5m10 0v-2a3 3 0 00-3-3H10a3 3 0 00-3 3v2m10 0H7m5-13a2 2 0 110 4 2 2 0 010-4z" />
                                    </svg>
                                    @endif
                                </span>

                                {{-- Label + badge --}}
                                <span
                                    x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                                    x-cloak
                                    class="flex items-center justify-between flex-1 min-w-0"
                                >
                                    <span class="truncate">{{ $item['label'] }}</span>
                                    @if($item['badge'] > 0)
                                    <span class="ml-2 flex-shrink-0 inline-flex items-center justify-center min-w-5 h-5 rounded-full bg-amber-100 text-amber-700 px-1.5 text-[10px] font-bold">{{ $item['badge'] }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endforeach

            </nav>

            {{-- Bottom — Instructor profile + logout --}}
            <div class="border-t border-gray-100 p-3 space-y-1">
                <a
                    href="{{ route('instructor.profile.show') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                    :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''"
                >
                    <span class="flex-shrink-0 text-gray-500">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 1118.88 17.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </span>
                    <span
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                        x-cloak
                        class="truncate"
                    >Edit Profile</span>
                </a>

                <div
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 transition-colors"
                    :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''"
                >
                    {{-- Avatar --}}
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 bg-gradient-to-r from-brand-500 to-brand-900">
                        {{ strtoupper(mb_substr(Auth::user()->first_name ?? Auth::user()->name, 0, 1)) }}
                    </div>

                    {{-- Name + logout --}}
                    <div
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                        x-cloak
                        class="flex-1 min-w-0"
                    >
                        <p class="text-xs font-semibold text-gray-900 truncate">{{ Auth::user()->first_name ?? Auth::user()->name }}</p>
                        <p class="text-[10px] text-brand-600 truncate uppercase tracking-wider">Instructor</p>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('instructor.logout') }}"
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                        x-cloak
                    >
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Logout">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
        <div
            class="flex-1 flex flex-col transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[280px]': $store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered,
                'xl:ml-[84px]': !$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered,
            }"
        >
            {{-- ── HEADER ── --}}
            @include('layouts.instructor-header')

            {{-- ── PAGE CONTENT ── --}}
            <main class="flex-1 p-4 md:p-6 max-w-screen-2xl mx-auto w-full">
                @yield('content')
            </main>

        </div>
    </div>

    @stack('scripts')

    {{-- ─── Flash toast notifications ─── --}}
    @if(session('success') || session('error') || session('info') || session('warning') || session('status') || $errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function fireToasts() {
                if (typeof window.toast === 'undefined') {
                    return setTimeout(fireToasts, 80);
                }
                @if(session('success'))
                    window.toast.success("{{ addslashes(session('success')) }}");
                @endif
                @if(session('error'))
                    window.toast.error("{{ addslashes(session('error')) }}");
                @endif
                @if(session('info'))
                    window.toast.info("{{ addslashes(session('info')) }}");
                @endif
                @if(session('warning'))
                    window.toast.warning("{{ addslashes(session('warning')) }}");
                @endif
                @if(session('status'))
                    window.toast.info("{{ addslashes(session('status')) }}");
                @endif
                @if($errors->any())
                    @foreach($errors->all() as $error)
                        window.toast.error("{{ addslashes($error) }}");
                    @endforeach
                @endif
            }
            fireToasts();
        });
    </script>
    @endif

    @include('chat.partials.global-popup')
</body>
</html>
