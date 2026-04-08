{{--
    Gamification & profile right-panel.
    Props:
      $user, $learnerProfile, $gamification,
      $xpInLevel, $xpPercent,
      $totalEnrolled,
      $shieldsRemaining,
      $recentAchievements
--}}
@props([
    'user',
    'learnerProfile',
    'gamification',
    'xpInLevel',
    'xpPercent',
    'totalEnrolled',
    'shieldsRemaining' => 3,
    'recentAchievements',
])

@php
    use App\Services\EntitlementService;
    use App\Support\SubscriptionFeatureKeys;

    $displayName = $learnerProfile?->username ?? $user->name;
    $avatarUrl   = $learnerProfile?->avatar_path
        ? asset('storage/' . $learnerProfile->avatar_path)
        : null;
    $level       = $gamification?->level ?? 1;
    $streak      = $gamification?->streak_count ?? 0;
    $totalPoints = $gamification?->total_points ?? 0;
    $isPremium   = $user->isPremium();
    $hasUnlimitedShields = app(EntitlementService::class)->canAccessFeature($user, SubscriptionFeatureKeys::UNLIMITED_SHIELDS);
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
    <button
        @click="typeof $store.modals.openEditProfile === 'function' ? $store.modals.openEditProfile() : ($store.modals.editProfile = true)"
        class="block w-full text-center text-sm font-semibold text-white py-2 rounded-xl mb-4 transition-opacity hover:opacity-90"
        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
    >
        Edit Profile
    </button>

    {{-- ─── Stats grid (icon chips) ─── --}}
    <div class="grid grid-cols-2 gap-3 mb-4">

        {{-- Enrolled modules --}}
        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12.676 2.084a1.5 1.5 0 00-1.352 0L5.25 5.12A1.5 1.5 0 004.5 6.454v12.318a1 1 0 001.271.962L12 17.93l6.229 1.804A1 1 0 0019.5 18.77V6.454a1.5 1.5 0 00-.75-1.334l-6.074-3.036z" />
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
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" />
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" />
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                    <path d="M12 23c-4.97 0-9-3.582-9-8 0-3.5 2-6.5 5-8-.5 1.5 0 3 1 4 .5-2 2-4 4-5-.5 2 1 4 2 5 .5-1 .5-2.5 0-3.5 2 1.5 3 4 3 7.5 1-1 1.5-2.5 1.5-4 1.5 1.5 2.5 3.5 2.5 6 0 4.418-4.03 8-9 8z" />
                </svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $streak }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Day Streak</div>
            </div>
        </div>

    </div>

    {{-- ─── Shields today ─── --}}
    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl mb-4">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" />
            </svg>
            <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Shields Today</span>
        </div>
        <div class="flex items-center gap-1" @click="$dispatch('open-shields-modal')" style="cursor:pointer">
            @if($hasUnlimitedShields)
                <span class="text-sm font-bold text-purple-700 dark:text-purple-300">∞</span>
                <span class="text-xs text-purple-600 dark:text-purple-400 ml-1">Unlimited Shields</span>
            @else
                @for($i = 0; $i < 3; $i++)
                    <svg class="w-5 h-5 {{ $i < $shieldsRemaining ? 'text-purple-500' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" />
                    </svg>
                @endfor
                <span class="text-xs text-purple-600 dark:text-purple-400 ml-1">{{ $shieldsRemaining }}/3</span>
            @endif
        </div>
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
