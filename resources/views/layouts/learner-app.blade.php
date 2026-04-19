<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $metaTitle = trim($__env->yieldContent('title', 'Dashboard') . ' | Conscious Connections');
        $metaDescription = trim($__env->yieldContent('meta_description', 'Conscious Connections learner dashboard for safe, inclusive, and accessible sexual health education.'));
        $metaImage = trim($__env->yieldContent('meta_image', asset('media/Logo.png')));
    @endphp

    <title>{{ $metaTitle }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('media/Logo.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('media/Logo.png') }}">
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>

    {{-- Apply dark mode immediately to prevent flash --}}
    <script>
        (function () {
            @if(session('force_light_mode_once'))
            try {
                localStorage.setItem('theme', 'light');
            } catch (error) {
                // Ignore storage access errors and still force class removal.
            }
            document.documentElement.classList.remove('dark');
            @else
            var saved = localStorage.getItem('theme');
            var system = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            if ((saved || system) === 'dark') {
                document.documentElement.classList.add('dark');
            }
            @endif
        })();
    </script>

    {{-- Bootstrap translation preference early to minimize default-English flash --}}
    <script>
        (function () {
            var storageKey = 'cc_page_translation_language';
            var preferredLanguage = 'en';

            try {
                preferredLanguage = localStorage.getItem(storageKey) || 'en';
            } catch (error) {
                preferredLanguage = 'en';
            }

            if (!['en', 'tl'].includes(preferredLanguage)) {
                preferredLanguage = 'en';
            }

            window.__ccPreferredTranslationLanguage = preferredLanguage;
            window.__ccPageTranslationBootedAt = Date.now();

            if (preferredLanguage !== 'en') {
                document.documentElement.setAttribute('lang', preferredLanguage);
                document.documentElement.setAttribute('data-cc-translation-pending', '1');
            }
        })();
    </script>

    @stack('head')
</head>
<body
    class="font-sans antialiased bg-gray-50 dark:bg-gray-900 h-full"
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
    "
>
    <div class="min-h-screen xl:flex">

        {{-- Sidebar --}}
        @include('layouts.learner-sidebar')

        {{-- Mobile backdrop --}}
        <div
            x-show="$store.sidebar.isMobileOpen"
            @click="$store.sidebar.setMobileOpen(false)"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] bg-black/50 xl:hidden"
            x-cloak
        ></div>

        {{-- Main content --}}
        <div
            class="flex-1 flex flex-col min-h-screen transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[270px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[80px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered
            }"
        >
            @include('layouts.learner-header')

            <main class="flex-1 p-4 md:p-6">
                @yield('content')
            </main>
        </div>

    </div>

    @stack('scripts')

    @auth
        @php
            $canUseTextTranslator = app(\App\Services\EntitlementService::class)
                ->canAccessFeature(auth()->user(), \App\Support\SubscriptionFeatureKeys::TEXT_TRANSLATOR);
            $canUseVoiceTranslator = app(\App\Services\EntitlementService::class)
                ->canAccessFeature(auth()->user(), \App\Support\SubscriptionFeatureKeys::VOICE_SPEECH_TRANSLATOR);
        @endphp

        <x-learner.out-of-shields-modal :score="auth()->user()->gamification?->score ?? 0" />
        @include('learner.partials.global-page-translator', [
            'canUseTextTranslator' => $canUseTextTranslator,
            'canUseVoiceTranslator' => $canUseVoiceTranslator,
        ])
    @endauth

    {{-- ─── Flash toast notifications ─── --}}
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
