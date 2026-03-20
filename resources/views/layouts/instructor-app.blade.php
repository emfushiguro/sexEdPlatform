<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Instructor Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Apply saved theme immediately to prevent flash. Default is light mode. --}}
    <script>
        (function () {
            var saved = localStorage.getItem('theme');
            if (!saved) {
                localStorage.setItem('theme', 'light');
                saved = 'light';
            }
            if (saved === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
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
    class="font-[Poppins] antialiased bg-gray-50 dark:bg-gray-900 h-full"
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
            @click="$store.instructorSidebar.toggleMobileOpen()"
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
            <div
                class="flex items-center gap-3 px-4 py-6 border-b border-white/10"
                :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : 'justify-start'"
            >
                <img src="/media/Logo.png" alt="Logo" class="w-9 h-9 flex-shrink-0 rounded-lg object-contain">
                <div
                    x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                    x-cloak
                    class="overflow-hidden"
                >
                    <p class="text-white font-bold text-sm leading-tight whitespace-nowrap">Conscious Connections</p>
                    <p class="text-purple-200 text-[10px] tracking-widest uppercase mt-0.5">Instructor Panel</p>
                </div>
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
                            ['label' => 'Enrollments',  'route' => 'instructor.enrollments.index', 'active' => request()->routeIs('instructor.enrollments.*'), 'badge' => $pendingCount, 'icon' => 'chart'],
                        ],
                    ],
                    [
                        'label' => 'EXTRAS',
                        'items' => [
                            ['label' => 'Image Library', 'route' => 'instructor.image-library.index', 'active' => request()->routeIs('instructor.image-library.*'), 'badge' => 0, 'icon' => 'sparkles'],
                        ],
                    ],
                ];
                @endphp

                @foreach($navGroups as $group)
                <div>
                    <p
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                        x-cloak
                        class="px-3 mb-2 text-[10px] font-semibold tracking-widest text-purple-200 uppercase"
                    >{{ $group['label'] }}</p>
                    <ul class="space-y-1">
                        @foreach($group['items'] as $item)
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 text-white {{ $item['active'] ? 'bg-white/20' : 'hover:bg-white/10' }}"
                                :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''"
                            >
                                {{-- Icon --}}
                                <span class="flex-shrink-0 instructor-icon-readable">
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
                                    @endif
                                </span>

                                {{-- Label + badge --}}
                                <span
                                    x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                                    x-cloak
                                    class="flex items-center justify-between flex-1 min-w-0"
                                >
                                    <span class="text-sm font-medium truncate">{{ $item['label'] }}</span>
                                    @if($item['badge'] > 0)
                                    <span class="ml-2 flex-shrink-0 inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold">{{ $item['badge'] }}</span>
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
            <div class="border-t border-white/10 p-4">
                <div
                    class="flex items-center gap-3"
                    :class="(!$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered && !$store.instructorSidebar.isMobileOpen) ? 'justify-center' : ''"
                >
                    {{-- Avatar --}}
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                        {{ strtoupper(mb_substr(Auth::user()->first_name ?? Auth::user()->name, 0, 1)) }}
                    </div>

                    {{-- Name + logout --}}
                    <div
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                        x-cloak
                        class="flex-1 min-w-0"
                    >
                        <p class="text-white text-xs font-semibold truncate">{{ Auth::user()->first_name ?? Auth::user()->name }}</p>
                        <p class="text-purple-200 text-[10px] truncate">Instructor</p>
                    </div>
                    <form
                        method="POST"
                        action="{{ route('instructor.logout') }}"
                        x-show="$store.instructorSidebar.isExpanded || $store.instructorSidebar.isHovered || $store.instructorSidebar.isMobileOpen"
                        x-cloak
                    >
                        @csrf
                        <button type="submit" class="text-purple-200 hover:text-white transition-colors" title="Logout">
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
                'xl:ml-[72px]': !$store.instructorSidebar.isExpanded && !$store.instructorSidebar.isHovered,
            }"
        >
            {{-- ── HEADER ── --}}
            @include('layouts.instructor-header')

            {{-- ── PAGE CONTENT ── --}}
            <main class="flex-1 p-4 md:p-6 max-w-screen-2xl mx-auto w-full dark:text-gray-100">
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

</body>
</html>
