<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin') | {{ config('app.name') }}</title>

    <!-- Outfit font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>

    <!-- Alpine stores (must run before Alpine.start() in app.js) -->
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

    <!-- Prevent dark mode flash -->
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

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 transition-colors duration-200"
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

        {{-- ============================================================
             MOBILE BACKDROP
        ============================================================ --}}
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

        {{-- ============================================================
             SIDEBAR
        ============================================================ --}}
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
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 min-w-0">
                    <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-brand-500 flex items-center justify-center shadow-theme-sm">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                          x-cloak
                          class="text-base font-bold text-gray-900 dark:text-white whitespace-nowrap overflow-hidden">
                        {{ config('app.name') }}
                    </span>
                </a>
            </div>

            {{-- Navigation --}}
            <div class="flex flex-col flex-1 overflow-y-auto no-scrollbar px-3 pb-6">
                <nav class="flex flex-col gap-4">

                    {{-- MAIN --}}
                    <div>
                        <h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                            x-cloak
                            class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Main
                        </h2>
                        <ul class="flex flex-col gap-1">
                            <li>
                                <a href="{{ route('admin.dashboard') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.dashboard') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Dashboard</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- MANAGEMENT --}}
                    <div>
                        <h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                            x-cloak
                            class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Management
                        </h2>
                        <ul class="flex flex-col gap-1">

                            {{-- Users --}}
                            <li>
                                <a href="{{ route('admin.users.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.users.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.users.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Users</span>
                                </a>
                            </li>

                            {{-- Subscribers --}}
                            <li>
                                <a href="{{ route('admin.subscribers.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.subscribers.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.subscribers.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Subscribers</span>
                                </a>
                            </li>

                            {{-- Subscription Plans --}}
                            <li>
                                <a href="{{ route('admin.subscription-plans.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.subscription-plans.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.subscription-plans.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Plans</span>
                                </a>
                            </li>

                            {{-- Payments --}}
                            <li>
                                <a href="{{ route('admin.payments.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.payments.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.payments.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Payments</span>
                                </a>
                            </li>

                        </ul>
                    </div>

                    {{-- PLATFORM --}}
                    <div>
                        <h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                            x-cloak
                            class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Platform
                        </h2>
                        <ul class="flex flex-col gap-1">
                            {{-- Calendar --}}
                            <li>
                                <a href="{{ route('admin.calendar.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.calendar.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.calendar.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Calendar</span>
                                </a>
                            </li>
                            {{-- Seminars --}}
                            <li>
                                <a href="{{ route('admin.seminars.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.seminars.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.seminars.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Seminars</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- COMMUNICATION --}}
                    <div>
                        <h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                            x-cloak
                            class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Communication
                        </h2>
                        <ul class="flex flex-col gap-1">
                            {{-- Messages --}}
                            <li>
                                <a href="{{ route('admin.messages.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.messages.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.messages.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Messages</span>
                                </a>
                            </li>
                            {{-- Emails --}}
                            <li>
                                <a href="{{ route('admin.emails.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.emails.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.emails.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Emails</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- PARTNERS --}}
                    <div>
                        <h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                            x-cloak
                            class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Partners
                        </h2>
                        <ul class="flex flex-col gap-1">
                            {{-- Organizations --}}
                            <li>
                                <a href="{{ route('admin.organizations.index') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group
                                          {{ request()->routeIs('admin.organizations.*') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}"
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                    <span class="flex-shrink-0 {{ request()->routeIs('admin.organizations.*') ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="whitespace-nowrap">Organizations</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                </nav>
            </div>

            {{-- Sidebar footer: Sign out removed to avoid duplication; now only in user dropdown --}}

        </aside>
        {{-- END SIDEBAR --}}

        {{-- ============================================================
             MAIN CONTENT
        ============================================================ --}}
        <div class="flex-1 transition-all duration-300 ease-in-out min-w-0"
             :class="{
                 'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                 'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered
             }">

            {{-- ========================================================
                 HEADER
            ======================================================== --}}
            <header class="sticky top-0 flex w-full bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800"
                    style="z-index: 99998;">
                <div class="flex items-center justify-between w-full px-4 py-3 xl:px-6">

                    {{-- Left side --}}
                    <div class="flex items-center gap-3">

                        {{-- Desktop sidebar toggle --}}
                        <button class="hidden xl:flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors"
                                @click="$store.sidebar.toggleExpanded()"
                                aria-label="Toggle Sidebar">
                            <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
                                      fill="currentColor"/>
                            </svg>
                        </button>

                        {{-- Mobile menu toggle --}}
                        <button class="flex xl:hidden items-center justify-center w-10 h-10 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors"
                                @click="$store.sidebar.toggleMobileOpen()"
                                aria-label="Toggle Mobile Menu">
                            <svg x-show="!$store.sidebar.isMobileOpen" width="16" height="12" viewBox="0 0 16 12" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
                                      fill="currentColor"/>
                            </svg>
                            <svg x-show="$store.sidebar.isMobileOpen" x-cloak class="fill-current" width="18" height="18" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"/>
                            </svg>
                        </button>

                        {{-- Page title --}}
                        <span class="text-sm font-semibold text-gray-800 dark:text-white hidden sm:block">
                            @yield('page-title', 'Admin Panel')
                        </span>

                    </div>

                    {{-- Right side --}}
                    <div class="flex items-center gap-2">

                        {{-- Dark mode toggle --}}
                        <button @click="$store.theme.toggle()"
                                class="flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors"
                                aria-label="Toggle dark mode">
                            {{-- Moon icon (light mode) --}}
                            <svg class="dark:hidden w-5 h-5" fill="none" viewBox="0 0 20 20">
                                <path d="M17.4547 11.97L18.1799 12.1611C18.265 11.8383 18.1265 11.4982 17.8401 11.3266C17.5538 11.1551 17.1885 11.1934 16.944 11.4207L17.4547 11.97ZM8.0306 2.5459L8.57989 3.05657C8.80718 2.81209 8.84554 2.44682 8.67398 2.16046C8.50243 1.8741 8.16227 1.73559 7.83948 1.82066L8.0306 2.5459ZM12.9154 13.0035C9.64678 13.0035 6.99707 10.3538 6.99707 7.08524H5.49707C5.49707 11.1823 8.81835 14.5035 12.9154 14.5035V13.0035ZM16.944 11.4207C15.8869 12.4035 14.4721 13.0035 12.9154 13.0035V14.5035C14.8657 14.5035 16.6418 13.7499 17.9654 12.5193L16.944 11.4207ZM16.7295 11.7789C15.9437 14.7607 13.2277 16.9586 10.0003 16.9586V18.4586C13.9257 18.4586 17.2249 15.7853 18.1799 12.1611L16.7295 11.7789ZM10.0003 16.9586C6.15734 16.9586 3.04199 13.8433 3.04199 10.0003H1.54199C1.54199 14.6717 5.32892 18.4586 10.0003 18.4586V16.9586ZM3.04199 10.0003C3.04199 6.77289 5.23988 4.05695 8.22173 3.27114L7.83948 1.82066C4.21532 2.77574 1.54199 6.07486 1.54199 10.0003H3.04199ZM6.99707 7.08524C6.99707 5.52854 7.5971 4.11366 8.57989 3.05657L7.48132 2.03522C6.25073 3.35885 5.49707 5.13487 5.49707 7.08524H6.99707Z" fill="currentColor"/>
                            </svg>
                            {{-- Sun icon (dark mode) --}}
                            <svg class="hidden dark:block w-5 h-5" fill="none" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M9.99998 1.5415C10.4142 1.5415 10.75 1.87729 10.75 2.2915V3.5415C10.75 3.95572 10.4142 4.2915 9.99998 4.2915C9.58577 4.2915 9.24998 3.95572 9.24998 3.5415V2.2915C9.24998 1.87729 9.58577 1.5415 9.99998 1.5415ZM10.0009 6.79327C8.22978 6.79327 6.79402 8.22904 6.79402 10.0001C6.79402 11.7712 8.22978 13.207 10.0009 13.207C11.772 13.207 13.2078 11.7712 13.2078 10.0001C13.2078 8.22904 11.772 6.79327 10.0009 6.79327ZM5.29402 10.0001C5.29402 7.40061 7.40135 5.29327 10.0009 5.29327C12.6004 5.29327 14.7078 7.40061 14.7078 10.0001C14.7078 12.5997 12.6004 14.707 10.0009 14.707C7.40135 14.707 5.29402 12.5997 5.29402 10.0001ZM15.9813 5.08035C16.2742 4.78746 16.2742 4.31258 15.9813 4.01969C15.6884 3.7268 15.2135 3.7268 14.9207 4.01969L14.0368 4.90357C13.7439 5.19647 13.7439 5.67134 14.0368 5.96423C14.3297 6.25713 14.8045 6.25713 15.0974 5.96423L15.9813 5.08035ZM18.4577 10.0001C18.4577 10.4143 18.1219 10.7501 17.7077 10.7501H16.4577C16.0435 10.7501 15.7077 10.4143 15.7077 10.0001C15.7077 9.58592 16.0435 9.25013 16.4577 9.25013H17.7077C18.1219 9.25013 18.4577 9.58592 18.4577 10.0001ZM14.9207 15.9806C15.2135 16.2735 15.6884 16.2735 15.9813 15.9806C16.2742 15.6877 16.2742 15.2128 15.9813 14.9199L15.0974 14.036C14.8045 13.7431 14.3297 13.7431 14.0368 14.036C13.7439 14.3289 13.7439 14.8038 14.0368 15.0967L14.9207 15.9806ZM9.99998 15.7088C10.4142 15.7088 10.75 16.0445 10.75 16.4588V17.7088C10.75 18.123 10.4142 18.4588 9.99998 18.4588C9.58577 18.4588 9.24998 18.123 9.24998 17.7088V16.4588C9.24998 16.0445 9.58577 15.7088 9.99998 15.7088ZM5.96356 15.0972C6.25646 14.8043 6.25646 14.3295 5.96356 14.0366C5.67067 13.7437 5.1958 13.7437 4.9029 14.0366L4.01902 14.9204C3.72613 15.2133 3.72613 15.6882 4.01902 15.9811C4.31191 16.274 4.78679 16.274 5.07968 15.9811L5.96356 15.0972ZM4.29224 10.0001C4.29224 10.4143 3.95645 10.7501 3.54224 10.7501H2.29224C1.87802 10.7501 1.54224 10.4143 1.54224 10.0001C1.54224 9.58592 1.87802 9.25013 2.29224 9.25013H3.54224C3.95645 9.25013 4.29224 9.58592 4.29224 10.0001ZM4.9029 5.9637C5.1958 6.25659 5.67067 6.25659 5.96356 5.9637C6.25646 5.6708 6.25646 5.19593 5.96356 4.90303L5.07968 4.01915C4.78679 3.72626 4.31191 3.72626 4.01902 4.01915C3.72613 4.31204 3.72613 4.78692 4.01902 5.07981L4.9029 5.9637Z" fill="currentColor"/>
                            </svg>
                        </button>

                        {{-- User dropdown --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition-colors">
                                <div class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-bold uppercase">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <span class="hidden sm:block max-w-[120px] truncate">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open"
                                 x-cloak
                                 @click.outside="open = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-theme-md overflow-hidden"
                                 style="z-index: 100;">
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ Auth::user()->email }}</p>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('profile.edit') }}"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        My Profile
                                    </a>
                                </div>
                                <div class="py-1 border-t border-gray-100 dark:border-gray-700">
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-error-600 dark:text-error-400 hover:bg-error-50 dark:hover:bg-error-500/10 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            Sign Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </header>
            {{-- END HEADER --}}

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="mx-4 mt-4 flex items-center gap-3 p-4 rounded-xl bg-success-50 dark:bg-success-500/10 border border-success-500/30 dark:border-success-500/20">
                    <svg class="w-5 h-5 flex-shrink-0 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-success-700 dark:text-success-400">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-4 mt-4 flex items-center gap-3 p-4 rounded-xl bg-error-50 dark:bg-error-500/10 border border-error-500/30 dark:border-error-500/20">
                    <svg class="w-5 h-5 flex-shrink-0 text-error-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-error-700 dark:text-error-400">{{ session('error') }}</p>
                </div>
            @endif

            @if(session('warning'))
                <div class="mx-4 mt-4 flex items-center gap-3 p-4 rounded-xl bg-warning-50 dark:bg-warning-500/10 border border-warning-500/30 dark:border-warning-500/20">
                    <svg class="w-5 h-5 flex-shrink-0 text-warning-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-sm font-medium text-warning-700 dark:text-warning-400">{{ session('warning') }}</p>
                </div>
            @endif

            {{-- Page content --}}
            <main class="p-4 md:p-6 max-w-[1536px] mx-auto">
                @yield('content')
            </main>

        </div>
        {{-- END MAIN CONTENT --}}

    </div>

    @stack('scripts')

</body>
</html>

