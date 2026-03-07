{{--
    Gamification & profile right-panel.
    Props:
      $user, $learnerProfile, $gamification,
      $xpInLevel, $xpPercent,
      $totalEnrolled,
      $quizAttemptsUsed, $quizAttemptsRemaining, $maxQuizAttempts,
      $recentAchievements
--}}
@props([
    'user',
    'learnerProfile',
    'gamification',
    'xpInLevel',
    'xpPercent',
    'totalEnrolled',
    'quizAttemptsUsed',
    'quizAttemptsRemaining',
    'maxQuizAttempts',
    'recentAchievements',
])

@php
    $displayName = $learnerProfile?->username ?? $user->name;
    $avatarUrl   = $learnerProfile?->avatar_path
        ? asset('storage/' . $learnerProfile->avatar_path)
        : null;
    $level       = $gamification?->level ?? 1;
    $streak      = $gamification?->streak_count ?? 0;
    $totalPoints = $gamification?->total_points ?? 0;
    $isPremium   = $user->isPremium();
@endphp

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">

    {{-- ─── Profile header ─── --}}
    <div class="flex items-center gap-4 mb-4">
        {{-- Avatar --}}
        @if($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="{{ $displayName }}"
                 class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-200 dark:ring-purple-800">
        @else
            <div
                class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl font-bold ring-2 ring-purple-200 dark:ring-purple-800 flex-shrink-0"
                style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);"
            >
                {{ strtoupper(mb_substr($displayName, 0, 1)) }}
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                    {{ $user->first_name ?? $displayName }}
                </h3>
                {{-- Subscription badge --}}
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $isPremium ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                    {{ $isPremium ? 'PREMIUM' : 'FREE' }}
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $displayName }}</p>

            {{-- Level + XP bar --}}
            <div class="mt-2">
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-semibold text-purple-600 dark:text-purple-400">Level {{ $level }}</span>
                    <span class="text-gray-400 dark:text-gray-500">{{ $xpInLevel }}/100 XP</span>
                </div>
                <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500 shadow-[0_0_8px_rgba(163,14,178,0.35)]"
                        style="width: {{ $xpPercent }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);"
                    ></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Profile button --}}
    <a
        href="{{ route('profile.learner.edit') }}"
        class="block w-full text-center text-sm font-semibold text-white py-2 rounded-xl mb-4 transition-opacity hover:opacity-90"
        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
    >
        Edit Profile
    </a>

    {{-- ─── Stats grid (icon chips) ─── --}}
    <div class="grid grid-cols-2 gap-3 mb-4">

        {{-- Enrolled modules --}}
        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                </svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $totalEnrolled }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Enrolled Modules</div>
            </div>
        </div>

        {{-- Current level --}}
        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $level }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Current Level</div>
            </div>
        </div>

        {{-- Total points --}}
        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                </svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalPoints) }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Total Points</div>
            </div>
        </div>

        {{-- Streak --}}
        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                </svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $streak }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Day Streak</div>
            </div>
        </div>

    </div>

    {{-- ─── Quiz attempts today ─── --}}
    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl mb-4">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-100 dark:bg-purple-900/40">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-purple-600 dark:text-purple-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" />
                </svg>
            </div>
            <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Quiz Attempts Today</span>
        </div>
        <span class="text-sm font-bold text-purple-700 dark:text-purple-300">
            @if($isPremium)
                ∞
            @else
                {{ $quizAttemptsRemaining }}/{{ $maxQuizAttempts }}
            @endif
        </span>
    </div>

    {{-- ─── Recent achievements ─── --}}
    @if($recentAchievements->isNotEmpty())
        <div>
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                Recent Achievements
            </h4>
            <div class="flex gap-2 flex-wrap">
                @foreach($recentAchievements as $achievement)
                    <div class="group relative">
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center bg-amber-100 dark:bg-amber-900/40 text-lg"
                            title="{{ $achievement->title }}"
                        >
                            @if($achievement->icon)
                                {{ $achievement->icon }}
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-600 dark:text-amber-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                                </svg>
                            @endif
                        </div>
                        {{-- Tooltip --}}
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 opacity-0 group-hover:opacity-100 transition-opacity px-2 py-1 bg-gray-800 text-white text-[10px] rounded whitespace-nowrap pointer-events-none z-10">
                            {{ $achievement->title }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
