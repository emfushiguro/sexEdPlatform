<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Lesson') | Concious Connections</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        html { height: 100%; overflow: hidden; }
        body { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
    </style>

    {{-- Apply dark mode immediately to prevent flash --}}
    <script>
        (function () {
            var saved = localStorage.getItem('theme');
            var system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            if ((saved || system) === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900" x-data>

    {{-- ═══════════════════════════════════════════════════════════
         FULLSCREEN TOP BAR
    ═══════════════════════════════════════════════════════════ --}}
    @php
        use App\Services\EntitlementService;
        use App\Support\SubscriptionFeatureKeys;

        $fsGami    = auth()->user()?->gamification;
        $fsShields = \App\Models\UserDailyShield::getShields(auth()->user());
        $fsHasUnlimitedShields = app(EntitlementService::class)->canAccessFeature(auth()->user(), SubscriptionFeatureKeys::UNLIMITED_SHIELDS);
    @endphp

    <div class="flex-shrink-0 h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 gap-3">

        {{-- Left: close button + breadcrumb --}}
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <a href="@yield('back-url', route('learner.modules.index'))"
               class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-200 dark:hover:border-red-800 hover:text-red-500 dark:hover:text-red-400 transition-colors"
               title="Exit lesson">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </a>
            <div class="min-w-0">
                <p class="text-xs font-medium text-gray-400 dark:text-gray-500 leading-none truncate hidden sm:block">
                    @yield('module-title', '')
                </p>
                <p class="text-base font-semibold text-gray-900 dark:text-white truncate leading-snug">
                    @yield('lesson-title', 'Lesson')
                </p>
            </div>
        </div>

        {{-- Center: lesson progress bar (optional slot) --}}
        <div class="hidden md:flex flex-1 max-w-xs items-center gap-2">
            @yield('progress-bar')
        </div>

        {{-- Right: gamification chips --}}
        <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
            {{-- Streak --}}
            <div class="flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800/40">
                <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 23c-4.97 0-9-3.582-9-8 0-3.5 2-6.5 5-8-.5 1.5 0 3 1 4 .5-2 2-4 4-5-.5 2 1 4 2 5 .5-1 .5-2.5 0-3.5 2 1.5 3 4 3 7.5 1-1 1.5-2.5 1.5-4 1.5 1.5 2.5 3.5 2.5 6 0 4.418-4.03 8-9 8z"/>
                </svg>
                <span class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">{{ $fsGami?->current_streak ?? 0 }}</span>
            </div>
            {{-- Shields --}}
            <div class="flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800/40">
                <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                </svg>
                @if($fsHasUnlimitedShields)
                    <span class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">Unlimited Shields</span>
                @else
                    <span class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">{{ $fsShields }}</span>
                @endif
            </div>
            {{-- Points --}}
            <div class="flex items-center gap-1 sm:gap-1.5 px-2 sm:px-2.5 py-1 sm:py-1.5 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800/40">
                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                </svg>
                <span class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white">{{ number_format($fsGami?->score ?? 0) }}</span>
            </div>

            {{-- Dark mode toggle --}}
            <button
                class="hidden sm:inline-flex items-center justify-center w-9 h-9 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors ml-1"
                @click="$store.theme.toggle()"
                aria-label="Toggle dark mode"
            >
                <svg x-show="$store.theme.mode === 'dark'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" x-cloak>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364-.707.707M6.343 17.657l-.707.707m12.728 0-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>
                </svg>
                <svg x-show="$store.theme.mode === 'light'" class="w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         BODY (below fixed top bar)
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-h-0 overflow-hidden">
        @yield('content')
    </div>

    @stack('scripts')

    @auth
        <x-learner.out-of-shields-modal :score="auth()->user()->gamification?->score ?? 0" />
    @endauth

    {{-- Flash toast notifications (mirrors learner-app.blade.php) --}}
    @if(session('success') || session('error') || session('info') || session('warning') || session('status') || session('shield_lost') || session('shield_refilled') || session('points_earned') || session('streak_milestone') || session('streak_saved') || $errors->any())
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
                @if(session('shield_lost'))
                    window.toast.shieldLost({{ (int) session('shield_lost')['shields_left'] }});
                @endif
                @if(session('shield_refilled'))
                    window.toast.shieldRefilled("{{ addslashes(session('shield_refilled')['message']) }}");
                @endif
                @if(session('points_earned'))
                    window.toast.pointsEarned({{ (int) session('points_earned') }});
                @endif
                @if(session('streak_milestone'))
                    window.toast.streakMilestone({{ (int) session('streak_milestone')['bonus'] }});
                @endif
                @if(session('streak_saved'))
                    window.toast.streakSaved({{ (int) session('streak_saved')['savers_left'] }});
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
