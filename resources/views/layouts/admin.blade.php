<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin') | {{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <!-- Poppins font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>

    <!-- Alpine stores -->
    <script>
        document.addEventListener('alpine:init', () => {
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

    @yield('styles')
</head>

<body class="font-sans antialiased bg-gray-50 text-gray-900"
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
        <aside class="fixed top-0 left-0 flex flex-col h-screen bg-white border-r border-gray-200 transition-all duration-300 ease-in-out overflow-hidden"
               style="z-index: 99999;"
               :class="{
                   'w-[290px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
                   'w-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen,
                   'translate-x-0': $store.sidebar.isMobileOpen,
                   '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
               }"
               @mouseenter="$store.sidebar.setHovered(true)"
               @mouseleave="$store.sidebar.setHovered(false)">

            {{-- Logo + Branding --}}
            <div class="flex items-center px-5 py-5 border-b border-gray-100 flex-shrink-0 overflow-hidden"
                 data-testid="admin-sidebar-branding"
                 :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : 'justify-start'">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 min-w-0">
                    {{-- Brand logo + text (expanded) --}}
                    <img src="/media/Logo.png"
                         alt="Conscious Connections"
                         x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                         x-transition:enter="transition-opacity duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="h-10 w-10 object-contain"
                         x-cloak>

                    <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" x-cloak class="leading-tight">
                        <span class="text-lg font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-500 whitespace-nowrap block">
                            Conscious <br>
                            Connections
                        </span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600 mt-1 block">Admin Panel</span>
                    </div>

                    {{-- Icon-only logo (collapsed) --}}
                    <img src="/media/Logo.png"
                         alt="Logo"
                         x-show="!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen"
                         class="h-9 w-9 object-contain">
                </a>
            </div>

            {{-- Navigation --}}
            <div class="flex flex-col flex-1 overflow-y-auto no-scrollbar px-3 pb-6">
                <nav class="flex flex-col gap-4">

                    {{-- MAIN --}}
                    <div>
                        <h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                            x-cloak
                            class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
                            Main
                        </h2>
                        <ul class="flex flex-col gap-1">
                            <li>
                                <a href="{{ route('admin.dashboard') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.dashboard') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.dashboard'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('chat.page') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('chat.*') || request()->routeIs('admin.messages.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('chat.*') || request()->routeIs('admin.messages.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('chat.*') || request()->routeIs('admin.messages.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 10h8m-8 4h5m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Chat</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- MODERATION --}}
                    <div>
                        <h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                            x-cloak
                            class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
                            Moderation
                        </h2>
                        <ul class="flex flex-col gap-1">
                            <li>
                                <a href="{{ route('admin.instructor-applications.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.instructor-applications.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.instructor-applications.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.instructor-applications.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17 20h5v-1a4 4 0 00-4-4h-1m-4 5H4v-1a4 4 0 014-4h5m0 5v-1a4 4 0 00-4-4H8m5 5h1a4 4 0 004-4v-1m-5-5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Instructor Applications</span>
                                    @if(($adminModerationCounts['pending_instructor_applications'] ?? 0) > 0)
                                        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                              x-cloak
                                              data-testid="admin-nav-badge-instructor-applications"
                                              class="ml-auto inline-flex min-w-6 items-center justify-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">
                                            {{ $adminModerationCounts['pending_instructor_applications'] }}
                                        </span>
                                    @endif
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('admin.content-reviews.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.content-reviews.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.content-reviews.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.content-reviews.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Module Published Review</span>
                                    @if(($adminModerationCounts['pending_module_reviews'] ?? 0) > 0)
                                        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                              x-cloak
                                              data-testid="admin-nav-badge-module-reviews"
                                              class="ml-auto inline-flex min-w-6 items-center justify-center rounded-full bg-sky-100 px-2 py-0.5 text-xs font-bold text-sky-700">
                                            {{ $adminModerationCounts['pending_module_reviews'] }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>

                    {{-- MANAGEMENT --}}
                    <div>
                        <h2 x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                            x-cloak
                            class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
                            Management
                        </h2>
                        <ul class="flex flex-col gap-1">
                            {{-- Users Management --}}
                            <li>
                                <a href="{{ route('admin.users.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.users.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.users.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.users.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17 20h5v-1a4 4 0 00-4-4h-1m-4 5H4v-1a4 4 0 014-4h5m0 5v-1a4 4 0 00-4-4H8m5 5h1a4 4 0 004-4v-1m-5-5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Users</span>
                                </a>
                            </li>

                            {{-- Subscribers --}}
                            <li>
                                <a href="{{ route('admin.subscribers.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.subscribers.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.subscribers.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.subscribers.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Subscribers</span>
                                </a>
                            </li>

                            {{-- Subscription Plans --}}
                            <li>
                                <a href="{{ route('admin.subscription-plans.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.subscription-plans.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.subscription-plans.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.subscription-plans.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Plans</span>
                                </a>
                            </li>

                            {{-- Payments --}}
                            <li>
                                <a href="{{ route('admin.payments.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.payments.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.payments.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.payments.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Payments</span>
                                </a>
                            </li>

                            {{-- Module Revenue --}}
                            <li>
                                <a href="{{ route('admin.monetization.module-revenue.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.monetization.module-revenue.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.monetization.module-revenue.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.monetization.module-revenue.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 3v18m-5-5h10M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Module Revenue</span>
                                </a>
                            </li>

                            {{-- Commission Settings --}}
                            <li>
                                <a href="{{ route('admin.monetization.commission-settings.index') }}"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group overflow-hidden whitespace-nowrap
                                    {{ request()->routeIs('admin.monetization.commission-settings.*') ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                                @if(request()->routeIs('admin.monetization.commission-settings.*'))
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                @endif
                                   :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'xl:justify-center' : ''">
                                 <span class="flex-shrink-0 transition-transform duration-200 group-hover:scale-110 {{ request()->routeIs('admin.monetization.commission-settings.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1m8-5a8 8 0 11-16 0 8 8 0 0116 0z"/>
                                        </svg>
                                    </span>
                                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen"
                                          x-cloak class="truncate">Commission Settings</span>
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
            <header class="sticky top-0 flex w-full bg-white border-b border-gray-200"
                    style="z-index: 99998;">
                <div class="flex items-center justify-between w-full px-4 py-3 xl:px-6">

                    {{-- Left side --}}
                    <div class="flex items-center gap-3">

                        {{-- Desktop sidebar toggle --}}
                        <button class="hidden xl:flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-100 transition-colors"
                                @click="$store.sidebar.toggleExpanded()"
                                aria-label="Toggle Sidebar">
                            <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
                                      fill="currentColor"/>
                            </svg>
                        </button>

                        {{-- Mobile menu toggle --}}
                        <button class="flex xl:hidden items-center justify-center w-10 h-10 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors"
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
                        <span class="text-sm font-semibold text-gray-800 hidden sm:block">
                            @yield('page-title', 'Admin Panel')
                        </span>

                    </div>

                    {{-- Right side --}}
                    <div class="flex items-center gap-2">

                        @php
                            $adminPayloadNormalizer = app(\App\Support\NotificationPayloadNormalizer::class);
                        @endphp

                        <div class="relative" x-data="{ open: false, syncReadState() { window.axios.post('{{ route('admin.notifications.dropdown-open') }}').catch(() => {}); } }">
                            <button @click="open = !open; if (open) { syncReadState(); }"
                                    class="relative flex items-center justify-center w-10 h-10 rounded-full border border-gray-200 bg-white text-gray-500 hover:bg-gray-100 transition-colors"
                                    aria-label="Open notifications">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9a6 6 0 10-12 0v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.562 1.08 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                @if(($adminNotifications['unread_count'] ?? 0) > 0)
                                    <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                        {{ $adminNotifications['unread_count'] > 9 ? '9+' : $adminNotifications['unread_count'] }}
                                    </span>
                                @endif
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
                                 class="absolute right-0 mt-2 w-80 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-md"
                                 style="z-index: 100;">
                                <div class="border-b border-gray-100 bg-gradient-to-r from-sky-50 via-white to-rose-50 px-4 py-3 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Notifications</p>
                                        <p class="mt-0.5 text-xs text-gray-500">Admin-side updates and system alerts.</p>
                                    </div>
                                    @if(($adminNotifications['unread_count'] ?? 0) > 0)
                                        <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-brand-700 hover:text-brand-900 transition-colors">
                                                Mark all read
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    @forelse(($adminNotifications['items'] ?? []) as $notification)
                                        @php
                                            $normalized = $adminPayloadNormalizer->normalize((array) $notification->data);
                                            $isUnread = is_null($notification->read_at);

                                            $toneClass = match($normalized['severity']) {
                                                'success' => 'border-l-4 border-emerald-500',
                                                'error' => 'border-l-4 border-rose-500',
                                                default => 'border-l-4 border-slate-300',
                                            };
                                        @endphp
                                        <a href="{{ route('admin.notifications.read', $notification->id) }}"
                                           class="flex gap-3 border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50 {{ $toneClass }} {{ $isUnread ? 'bg-rose-50/40' : '' }}">
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-semibold text-gray-900">{{ $normalized['title'] }}</span>
                                                <span class="mt-0.5 block text-xs leading-5 text-gray-500">{{ $normalized['message'] }}</span>
                                                <span class="mt-1 block text-[11px] text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                                            </span>
                                            @if($isUnread)
                                                <span class="mt-1 inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                                            @endif
                                        </a>
                                    @empty
                                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                                            No notifications right now.
                                        </div>
                                    @endforelse

                                    @if(!empty($adminOperationalSignals['items'] ?? []))
                                        <div class="border-t border-gray-100 bg-gray-50/70 px-4 py-2">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Operational Signals</p>
                                        </div>
                                        @foreach(($adminOperationalSignals['items'] ?? []) as $item)
                                            <a href="{{ $item['href'] }}" class="flex gap-3 border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50">
                                                <span class="mt-0.5 inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl {{ $item['tone'] === 'amber' ? 'bg-amber-50 text-amber-600' : ($item['tone'] === 'blue' ? 'bg-sky-50 text-sky-600' : 'bg-rose-50 text-rose-600') }}">
                                                    <span class="text-xs font-bold">{{ $item['value'] }}</span>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-gray-900">{{ $item['label'] }}</span>
                                                    <span class="mt-0.5 block text-xs leading-5 text-gray-500">{{ $item['message'] }}</span>
                                                </span>
                                            </a>
                                        @endforeach
                                    @endif
                                </div>
                                <div class="border-t border-gray-100 px-4 py-2">
                                    <a href="{{ route('admin.notifications.index') }}" class="block text-center text-xs font-medium text-brand-700 hover:text-brand-900 transition-colors py-1">
                                        View all notifications ->
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- User dropdown --}}
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition-colors">
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
                                 class="absolute right-0 mt-2 w-56 rounded-xl bg-white border border-gray-200 shadow-theme-md overflow-hidden"
                                 style="z-index: 100;">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-xs text-gray-500 truncate mt-0.5">{{ Auth::user()->email }}</p>
                                </div>
                                <div class="py-1">
                                                <a href="{{ route('admin.profile.show') }}"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        My Profile
                                    </a>
                                </div>
                                @include('partials.chat-status-selector')
                                <div class="py-1 border-t border-gray-100">
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-error-600 hover:bg-error-50 transition-colors">
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

            {{-- Page content --}}
            <main class="p-4 md:p-6 max-w-[1536px] mx-auto">
                @yield('content')
            </main>

        </div>
        {{-- END MAIN CONTENT --}}

    </div>

    @stack('scripts')

    {{-- Flash toast notifications --}}
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
