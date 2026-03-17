@props([
    'card' => [],
    'avgQuizScoreScopes' => [],
])

@php
    $label = $card['label'] ?? '';
    $value = $card['value'] ?? '--';
    $route = $card['route'] ?? '#';
    $icon = $card['icon'] ?? 'chart';
    $trend = $card['trend'] ?? ['direction' => 'flat', 'text' => 'No data'];

    $trendClass = match ($trend['direction'] ?? 'flat') {
        'up' => 'bg-green-100 text-green-700',
        'down' => 'bg-red-100 text-red-700',
        default => 'bg-gray-100 text-gray-600',
    };

    $isAverageQuizScore = $label === 'Average Quiz Score';
    $defaultScope = $avgQuizScoreScopes['defaultScope'] ?? 'all_time';

    $borderClass = 'border-purple-300';

    $hasTrendText = !empty($trend['text']);
@endphp

<div class="bg-white rounded-2xl border-2 shadow-sm p-4 h-full min-h-[170px] hover:shadow-md transition-shadow {{ $borderClass }}">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>

            @if($isAverageQuizScore)
                <div class="mt-2" x-data="{ scope: '{{ $defaultScope }}' }" data-testid="avg-score-scope-toggle">
                    <div class="inline-flex rounded-lg bg-gray-100 p-0.5 mb-2">
                        <button
                            type="button"
                            class="px-2 py-1 text-[11px] font-semibold rounded-md transition-colors"
                            :class="scope === 'all_time' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            @click="scope = 'all_time'"
                        >
                            All Time
                        </button>
                        <button
                            type="button"
                            class="px-2 py-1 text-[11px] font-semibold rounded-md transition-colors"
                            :class="scope === 'last_30_days' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            @click="scope = 'last_30_days'"
                        >
                            30 Days
                        </button>
                    </div>

                    <p class="text-2xl font-bold text-gray-900" x-text="scope === 'all_time' ? '{{ $avgQuizScoreScopes['all_time']['value'] ?? '--' }}' : '{{ $avgQuizScoreScopes['last_30_days']['value'] ?? '--' }}'"></p>

                    <template x-if="(scope === 'all_time' ? '{{ $avgQuizScoreScopes['all_time']['trendText'] ?? '' }}' : '{{ $avgQuizScoreScopes['last_30_days']['trendText'] ?? '' }}') !== ''">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold mt-2"
                            :class="scope === 'all_time' ? '{{ $avgQuizScoreScopes['all_time']['trendClass'] ?? 'bg-gray-100 text-gray-600' }}' : '{{ $avgQuizScoreScopes['last_30_days']['trendClass'] ?? 'bg-gray-100 text-gray-600' }}'"
                            x-text="scope === 'all_time' ? '{{ $avgQuizScoreScopes['all_time']['trendText'] ?? '' }}' : '{{ $avgQuizScoreScopes['last_30_days']['trendText'] ?? '' }}'"
                        ></span>
                    </template>
                </div>
            @else
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ $value }}</p>
                @if($hasTrendText)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold mt-2 {{ $trendClass }}">
                        {{ $trend['text'] }}
                    </span>
                @endif
            @endif
        </div>

        <a href="{{ $route }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 transition-colors" aria-label="View {{ strtolower($label) }} details">
            @if($icon === 'users')
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-5-3.87M17 20H7m10 0v-1c0-.656-.126-1.283-.356-1.857M7 20H2v-1a4 4 0 015-3.87M7 20v-1c0-.656.126-1.283.356-1.857m0 0A5.002 5.002 0 0112 15a5.002 5.002 0 014.644 2.143M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            @elseif($icon === 'book')
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.835 5.477 9.53 5 8 5a7 7 0 00-7 7v7a1 1 0 001 1h6c1.53 0 2.835.477 4 1.253m0-15C13.165 5.477 14.47 5 16 5a7 7 0 017 7v7a1 1 0 01-1 1h-6c-1.53 0-2.835.477-4 1.253" />
                </svg>
            @elseif($icon === 'clipboard')
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            @elseif($icon === 'clock')
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @elseif($icon === 'check-circle')
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2l4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @else
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1021 12h-9V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 3.055A9.005 9.005 0 0121 11h-8V3.055z" />
                </svg>
            @endif
        </a>
    </div>
</div>
