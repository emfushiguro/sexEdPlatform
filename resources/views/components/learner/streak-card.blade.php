{{--
    Streak card — shows weekly activity dots, current/longest streak, and streak savers.
    Props: $gamification, $streakActiveDays (array of 0-6 int), $longestStreak, $streakSavers, $score
--}}
@props(['gamification', 'streakActiveDays' => [], 'longestStreak' => 0, 'streakSavers' => 0, 'score' => 0])

@php
    $currentStreak = $gamification?->streak_count ?? 0;
    $days = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
    $todayDow = (int) now()->dayOfWeek; // 0=Sun, 6=Sat
    $canBuySaver = $streakSavers < 3 && $score >= 75;
@endphp

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
    {{-- Header --}}
    <div class="flex items-center gap-2 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-orange-500">
            <path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z" clip-rule="evenodd" />
        </svg>
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Your Streak</h3>
    </div>

    {{-- Weekly dots --}}
    <div class="flex justify-between mb-4">
        @foreach($days as $index => $label)
            @php $isActive = in_array($index, $streakActiveDays); $isToday = $index === $todayDow; @endphp
            <div class="flex flex-col items-center gap-1">
                <div class="w-9 h-9 rounded-full flex items-center justify-center
                    {{ $isToday ? 'ring-2 ring-offset-2 ring-purple-400' : '' }}
                    {{ $isActive ? 'text-white' : 'bg-gray-100 dark:bg-gray-700' }}"
                    @if($isActive) style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);" @endif>
                    @if($isActive)
                        <x-icons.shield state="full" :size="16" />
                    @endif
                </div>
                <span class="text-[10px] font-medium {{ $isToday ? 'text-purple-600 dark:text-purple-400' : 'text-gray-400' }}">
                    {{ $label }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- Current / Longest streak --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
            <div class="flex items-center justify-center gap-1 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-green-500">
                    <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd" />
                </svg>
                <span class="text-[10px] text-gray-500 dark:text-gray-400">Current</span>
            </div>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $currentStreak }}</p>
            <p class="text-[10px] text-gray-400">days</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 text-center">
            <div class="flex items-center justify-center gap-1 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-orange-500">
                    <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd" />
                </svg>
                <span class="text-[10px] text-gray-500 dark:text-gray-400">Longest</span>
            </div>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $longestStreak }}</p>
            <p class="text-[10px] text-gray-400">days</p>
        </div>
    </div>

    {{-- Streak Savers --}}
    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Streak Savers</span>
            <div class="flex items-center gap-1">
                @for($i = 0; $i < 3; $i++)
                    <x-icons.shield :state="$i < $streakSavers ? 'full' : 'empty'" :size="16" />
                @endfor
                <span class="text-xs text-gray-500 ml-1">{{ $streakSavers }}/3</span>
            </div>
        </div>

        <form method="POST" action="{{ route('learner.streak-savers.buy') }}">
            @csrf
            <button type="submit"
                @if(!$canBuySaver) disabled @endif
                class="w-full text-xs font-semibold py-2 rounded-xl transition-all
                    {{ $canBuySaver
                        ? 'text-white hover:opacity-90'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500' }}"
                @if($canBuySaver) style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);" @endif
                title="{{ !$canBuySaver ? ($streakSavers >= 3 ? 'Already at max savers' : 'Not enough points (need 75)') : '' }}"
            >
                Buy Saver — ⭐ 75
            </button>
        </form>
    </div>
</div>
