@php
    $headerUser    = Auth::user();
    $headerProfile = $headerUser->learnerProfile;
    $headerName    = $headerProfile?->username ?? $headerUser->name;
    $headerAvatar  = $headerProfile?->avatar_path
        ? asset('storage/' . $headerProfile->avatar_path)
        : null;
@endphp

<header class="sticky top-0 z-[9998] w-full bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
    <div class="flex items-center justify-between h-16 px-4 md:px-6">

        {{-- ─── Left: Sidebar toggle + Search ─── --}}
        <div class="flex items-center gap-3">

            {{-- Desktop sidebar toggle --}}
            <button
                class="hidden xl:flex items-center justify-center w-10 h-10 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                @click="$store.sidebar.toggleExpanded()"
                aria-label="Toggle sidebar"
            >
                <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0.583 1C0.583 .586.919.25 1.333.25H14.667C15.081.25 15.417.586 15.417 1c0 .414-.336.75-.75.75H1.333A.75.75 0 0 1 .583 1Zm0 10c0-.414.336-.75.75-.75H14.667a.75.75 0 0 1 0 1.5H1.333A.75.75 0 0 1 .583 11ZM1.333 5.25A.75.75 0 0 0 .583 6c0 .414.336.75.75.75H8A.75.75 0 0 0 8 5.25H1.333Z" fill="currentColor"/>
                </svg>
            </button>

            {{-- Mobile sidebar toggle --}}
            <button
                class="flex xl:hidden items-center justify-center w-10 h-10 text-gray-500 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                @click="$store.sidebar.toggleMobileOpen()"
                aria-label="Open menu"
            >
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 6h18M3 12h18M3 18h18"/>
                </svg>
            </button>

            {{-- Search bar --}}
            <div class="relative hidden sm:block">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"/>
                    </svg>
                </span>
                <input
                    type="text"
                    placeholder="Search modules..."
                    class="h-10 w-64 xl:w-80 pl-9 pr-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-800 dark:text-gray-200 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-400 dark:focus:ring-purple-600 focus:border-transparent transition-all"
                >
            </div>
        </div>

        {{-- ─── Right: Dark mode + Notifications + User ─── --}}
        <div class="flex items-center gap-2 sm:gap-3">

            {{-- Dark mode toggle --}}
            <button
                class="flex items-center justify-center w-10 h-10 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                @click="$store.theme.toggle()"
                aria-label="Toggle dark mode"
            >
                {{-- Sun icon (shown in dark mode) --}}
                <svg x-show="$store.theme.mode === 'dark'" width="18" height="18" fill="none" viewBox="0 0 24 24" x-cloak>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364-.707.707M6.343 17.657l-.707.707m12.728 0-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>
                </svg>
                {{-- Moon icon (shown in light mode) --}}
                <svg x-show="$store.theme.mode === 'light'" width="18" height="18" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
                </svg>
            </button>

            {{-- Notifications --}}
            <button
                class="relative flex items-center justify-center w-10 h-10 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                aria-label="Notifications"
            >
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 0 0-5-5.917V4a1 1 0 1 0-2 0v1.083A6 6 0 0 0 6 11v3.159c0 .538-.214 1.055-.595 1.437L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                </svg>
                {{-- Notification dot (placeholder) --}}
                <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            {{-- User dropdown --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button
                    @click="open = !open"
                    class="flex items-center gap-2 px-2 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    aria-label="User menu"
                >
                    @if($headerAvatar)
                        <img src="{{ $headerAvatar }}" alt="{{ $headerName }}" class="w-8 h-8 rounded-full object-cover">
                    @else
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                            style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);"
                        >
                            {{ strtoupper(mb_substr($headerName, 0, 1)) }}
                        </div>
                    @endif
                    <span class="hidden sm:block text-sm font-medium text-gray-700 dark:text-gray-300 max-w-[100px] truncate">
                        {{ $headerName }}
                    </span>
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" class="text-gray-400"
                        :class="{ 'rotate-180': open }" style="transition: transform .2s">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                    </svg>
                </button>

                {{-- Dropdown --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                    x-cloak
                >
                    <a
                        href="{{ route('profile.learner.edit') }}"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4Z"/>
                        </svg>
                        Edit Profile
                    </a>
                    <a
                        href="{{ route('subscription.index') }}"
                        class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3Z"/>
                        </svg>
                        Subscription
                    </a>
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                        >
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>
                            </svg>
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
