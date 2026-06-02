<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Connector') | {{ config('app.name', 'Conscious Connections') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('media/Logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased"
    x-data
    x-init="
        $store.sidebar.isExpanded = window.innerWidth >= 1280;
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1280) {
                $store.sidebar.setMobileOpen(false);
                $store.sidebar.isExpanded = false;
            } else {
                $store.sidebar.isMobileOpen = false;
                $store.sidebar.isExpanded = true;
            }
        });
    ">
    @php
        $connectorNavItems = [
            ['Dashboard', 'connector.dashboard', 'M3.75 5.75A2 2 0 0 1 5.75 3.75h3.5a2 2 0 0 1 2 2v3.5a2 2 0 0 1-2 2h-3.5a2 2 0 0 1-2-2v-3.5Zm9 0a2 2 0 0 1 2-2h3.5a2 2 0 0 1 2 2v3.5a2 2 0 0 1-2 2h-3.5a2 2 0 0 1-2-2v-3.5Zm-9 9a2 2 0 0 1 2-2h3.5a2 2 0 0 1 2 2v3.5a2 2 0 0 1-2 2h-3.5a2 2 0 0 1-2-2v-3.5Zm9 0a2 2 0 0 1 2-2h3.5a2 2 0 0 1 2 2v3.5a2 2 0 0 1-2 2h-3.5a2 2 0 0 1-2-2v-3.5Z'],
            ['Members', 'connector.members.index', 'M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0v1a4 4 0 0 0 4 4m-12-5v1a4 4 0 0 1-4 4m4-4h8m-8 0a4 4 0 0 0-4 4v1m12-5a4 4 0 0 1 4 4v1'],
            ['Roles & Permissions', 'connector.roles.index', 'M12 3.75 5.25 6.5v5.25c0 4.25 2.85 7.9 6.75 8.95 3.9-1.05 6.75-4.7 6.75-8.95V6.5L12 3.75Zm-2.25 8.5 1.75 1.75 3.25-4'],
            ['Seminars', 'connector.seminars.index', 'M7 3.75v2.5M17 3.75v2.5M4.75 8.75h14.5M6.25 5.25h11.5a2 2 0 0 1 2 2v10.5a2 2 0 0 1-2 2H6.25a2 2 0 0 1-2-2V7.25a2 2 0 0 1 2-2Z'],
            ['Modules', 'connector.modules', 'M5 4.75h14v14H5zM8 8.75h8M8 12h8M8 15.25h5'],
            ['Educators', 'connector.educators', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0'],
            ['Subscription', 'connector.subscription', 'M4.75 6.5h14.5M6 4.75h12A1.25 1.25 0 0 1 19.25 6v12A1.25 1.25 0 0 1 18 19.25H6A1.25 1.25 0 0 1 4.75 18V6A1.25 1.25 0 0 1 6 4.75Zm2 9.25h1m3 0h1'],
        ];
    @endphp

    <div class="min-h-screen xl:flex">
        <div x-show="$store.sidebar.isMobileOpen" x-cloak @click="$store.sidebar.setMobileOpen(false)" class="fixed inset-0 z-[9998] bg-gray-900/50 xl:hidden"></div>

        <aside class="fixed left-0 top-0 z-[9999] flex h-screen flex-col overflow-hidden border-r border-gray-200 bg-white transition-all duration-300 ease-in-out"
            :class="{
                'w-[270px]': $store.sidebar.isExpanded || $store.sidebar.isMobileOpen || $store.sidebar.isHovered,
                'w-[80px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen,
                'translate-x-0': $store.sidebar.isMobileOpen,
                '-translate-x-full xl:translate-x-0': !$store.sidebar.isMobileOpen
            }"
            @mouseenter="$store.sidebar.setHovered(true)"
            @mouseleave="$store.sidebar.setHovered(false)">
            <div class="flex items-center border-b border-gray-100 px-5 py-5" :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : 'justify-start'">
                <a href="{{ route('connector.dashboard', $connector) }}" class="flex min-w-0 items-center gap-3">
                    <img src="/media/Logo.png" alt="Conscious Connections" class="h-10 w-10 object-contain">
                    <div x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" x-cloak class="min-w-0 leading-tight">
                        <span class="block text-lg font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-pink-500">Connector</span>
                        <span class="mt-1 block truncate text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ $connector->name ?? 'Workspace' }}</span>
                    </div>
                </a>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-4">
                @foreach($connectorNavItems as [$label, $route, $path])
                    @php $active = request()->routeIs($route); @endphp
                    <a href="{{ route($route, $connector) }}"
                        class="group flex items-center gap-3 overflow-hidden whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 {{ $active ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-purple-50 hover:text-purple-700' }}"
                        @if($active) style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);" @endif
                        :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : ''">
                        <svg class="h-5 w-5 flex-shrink-0 {{ $active ? 'text-white' : 'text-gray-500 group-hover:text-purple-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $path }}"/>
                        </svg>
                        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" x-cloak class="truncate">{{ $label }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="space-y-1 border-t border-gray-100 p-3">
                <a href="{{ route('learner.dashboard') }}" class="flex items-center gap-3 overflow-hidden whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900" :class="(!$store.sidebar.isExpanded && !$store.sidebar.isHovered && !$store.sidebar.isMobileOpen) ? 'justify-center' : ''">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12 12 4l9 8M5.5 10.5v8h13v-8"/></svg>
                    <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" x-cloak>Learner Dashboard</span>
                </a>
            </div>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[270px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[80px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered
            }">
            <header class="sticky top-0 z-[9997] border-b border-gray-200 bg-white px-4 py-3 md:px-6">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" class="hidden h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-100 xl:flex" @click="$store.sidebar.toggleExpanded()" aria-label="Toggle sidebar">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 7h16M4 12h10M4 17h16"/></svg>
                        </button>
                        <button type="button" class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 xl:hidden" @click="$store.sidebar.toggleMobileOpen()" aria-label="Open menu">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
                        </button>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold uppercase tracking-[0.18em] text-purple-700">{{ str_replace('_', ' ', $connector->category ?? 'Connector') }}</p>
                            <h1 class="truncate text-xl font-bold">@yield('page-title', $connector->name ?? 'Connector')</h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('connector.status', $connector) }}" class="hidden rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-50 hover:text-purple-700 sm:inline-flex">Status</a>
                        <a href="{{ route('learner.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-purple-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-purple-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12 12 4l9 8M5.5 10.5v8h13v-8"/></svg>
                            <span class="hidden sm:inline">Learner</span>
                        </a>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
