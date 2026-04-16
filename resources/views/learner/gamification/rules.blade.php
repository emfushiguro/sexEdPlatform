@extends('layouts.learner-app')

@section('title', 'Gamification Rules')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    @php
        $pointsRules = data_get($rules ?? [], 'points', []);
        $streakRules = data_get($rules ?? [], 'streak', []);
        $shieldRules = data_get($rules ?? [], 'shield', []);

        $shieldDefault = (int) data_get($shieldRules, 'daily_shields_default', 3);
        $shieldCap = (int) data_get($shieldRules, 'max_shields_per_day_cap', 3);
        $shieldSingleCost = (int) data_get($shieldRules, 'refill_single_cost_points', 50);
        $shieldFullCost = (int) data_get($shieldRules, 'refill_full_cost_points', 100);
        $shieldFullTarget = (int) data_get($shieldRules, 'refill_full_target_shields', 3);

        $streakSaverCost = (int) data_get($streakRules, 'saver_purchase_cost_points', 75);
        $maxStreakSavers = (int) data_get($streakRules, 'max_savers_held', 3);
        $streakMilestones = data_get($streakRules, 'milestones', collect());

        $topicPoints = (int) data_get($pointsRules, 'topic_complete_points', 10);
        $lessonPoints = (int) data_get($pointsRules, 'lesson_complete_points', 15);
        $modulePoints = (int) data_get($pointsRules, 'module_complete_points', 100);
        $certificatePoints = (int) data_get($pointsRules, 'certificate_earned_points', 50);
        $quizPerfectPoints = (int) data_get($pointsRules, 'quiz_perfect_score_points', 30);
        $quizPassPoints = (int) data_get($pointsRules, 'quiz_pass_score_points', 25);
        $quizFailPoints = (int) data_get($pointsRules, 'quiz_fail_attempt_points', 5);
    @endphp

    {{-- Back link --}}
    <div>
        <a href="{{ route('learner.dashboard') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>
    </div>

    {{-- Hero Banner --}}
    <div class="rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-16 -left-16 w-48 h-48 rounded-full bg-black/10 blur-2xl"></div>

        <div class="relative">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 mb-4">
                <svg class="w-7 h-7 text-yellow-300" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l2.8 5.67L21 8.56l-4.5 4.38 1.06 6.2L12 16.3l-5.56 2.84 1.06-6.2L3 8.56l6.2-.89L12 2z"/>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold mb-2">How Gamification Works</h1>
            <p class="text-white/85 text-sm max-w-2xl">
                Earn points, protect your streak, and level up through consistent progress in lessons and quizzes.
            </p>
        </div>

        @if($gamification)
        <div class="relative mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-2xl bg-white/15 border border-white/20 px-4 py-3">
                <div class="flex items-center gap-2 text-white/75 text-xs uppercase tracking-wide">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l2.8 5.67L21 8.56l-4.5 4.38 1.06 6.2L12 16.3l-5.56 2.84 1.06-6.2L3 8.56l6.2-.89L12 2z"/>
                    </svg>
                    Points
                </div>
                <p class="mt-1 text-2xl font-bold">{{ number_format($gamification->score) }}</p>
            </div>
            <div class="rounded-2xl bg-white/15 border border-white/20 px-4 py-3">
                <div class="flex items-center gap-2 text-white/75 text-xs uppercase tracking-wide">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m0 0l-3-3m3 3l3-3M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>
                    Level
                </div>
                <p class="mt-1 text-2xl font-bold">{{ $gamification->level }}</p>
            </div>
            <div class="rounded-2xl bg-white/15 border border-white/20 px-4 py-3">
                <div class="flex items-center gap-2 text-white/75 text-xs uppercase tracking-wide">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2C12 2 8.5 6.5 8.5 10C8.5 11.933 9.567 13.6 11 14.5C10.5 13 11 11.5 12 10.5C13 11.5 13.5 13 13 14.5C14.433 13.6 15.5 11.933 15.5 10C15.5 6.5 12 2 12 2Z" fill="currentColor"/>
                        <path d="M12 14.5C10.343 14.5 9 15.843 9 17.5C9 19.985 10.791 22 13 22C15.209 22 17 19.985 17 17.5C17 15.843 15.657 14.5 14 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Streak
                </div>
                <p class="mt-1 text-2xl font-bold">{{ $gamification->streak_count }}d</p>
            </div>
            <div class="rounded-2xl bg-white/15 border border-white/20 px-4 py-3">
                <div class="flex items-center gap-2 text-white/75 text-xs uppercase tracking-wide">
                    <x-icons.shield state="full" :size="16" />
                    Shields
                </div>
                <p class="mt-1 text-2xl font-bold">{{ $shieldsRemaining }}/{{ $shieldCap }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Rules grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Shields Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex items-center gap-2 mb-4">
            <x-icons.shield state="full" :size="22" />
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Quiz Shields</h2>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Shields protect your streak and let you take quizzes. You start each day with <strong>{{ $shieldDefault }} shields</strong>.
        </p>
        <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>Each day you get <strong>{{ $shieldDefault }} quiz shields</strong> and they reset at midnight.</span>
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>A shield is <strong>drained</strong> when you fail or don&#39;t finish a quiz.</span>
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 text-purple-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l2.8 5.67L21 8.56l-4.5 4.38 1.06 6.2L12 16.3l-5.56 2.84 1.06-6.2L3 8.56l6.2-.89L12 2z"/>
                </svg>
                <span>Spend <strong>{{ $shieldSingleCost }} points</strong> to refill 1 shield, or <strong>{{ $shieldFullCost }} points</strong> for a full refill ({{ $shieldFullTarget }} shields).</span>
            </li>
        </ul>
    </div>

    {{-- Streak Rules --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex items-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-orange-500">
                <path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z" clip-rule="evenodd" />
            </svg>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Daily Streak</h2>
        </div>
        <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300 mb-4">
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 text-orange-500 flex-shrink-0" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2C12 2 8.5 6.5 8.5 10C8.5 11.933 9.567 13.6 11 14.5C10.5 13 11 11.5 12 10.5C13 11.5 13.5 13 13 14.5C14.433 13.6 15.5 11.933 15.5 10C15.5 6.5 12 2 12 2Z" fill="currentColor"/>
                    <path d="M12 14.5C10.343 14.5 9 15.843 9 17.5C9 19.985 10.791 22 13 22C15.209 22 17 19.985 17 17.5C17 15.843 15.657 14.5 14 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span>Complete at least one lesson topic each day to keep your streak alive.</span>
            </li>
            @forelse($streakMilestones as $milestone)
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-yellow-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l2.8 5.67L21 8.56l-4.5 4.38 1.06 6.2L12 16.3l-5.56 2.84 1.06-6.2L3 8.56l6.2-.89L12 2z"/>
                    </svg>
                    <span><strong>{{ (int) data_get($milestone, 'days', 0) }}-day streak:</strong> +{{ (int) data_get($milestone, 'bonus_points', 0) }} bonus points</span>
                </li>
            @empty
                <li class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                    <span>No streak milestone bonuses are currently configured.</span>
                </li>
            @endforelse
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                </svg>
                <span>Miss a day and your streak resets to 0 unless you have a streak saver.</span>
            </li>
        </ul>
    </div>

    {{-- Streak Savers --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 lg:col-span-2">
        <div class="flex items-center gap-2 mb-4">
            <x-icons.shield state="full" :size="22" />
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Streak Savers</h2>
        </div>
        <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
            <li class="flex items-start gap-2">
                <x-icons.shield state="full" :size="16" />
                <span>Streak savers automatically protect your streak when you miss a day.</span>
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 text-purple-500 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l2.8 5.67L21 8.56l-4.5 4.38 1.06 6.2L12 16.3l-5.56 2.84 1.06-6.2L3 8.56l6.2-.89L12 2z"/>
                </svg>
                <span>Buy a streak saver for <strong>{{ $streakSaverCost }} points</strong>. You can hold up to <strong>{{ $maxStreakSavers }} savers</strong>.</span>
            </li>
            <li class="flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                </svg>
                <span>A saver is consumed automatically so no manual action is needed.</span>
            </li>
        </ul>
    </div>

    </div>

    {{-- Points Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l3-3 3 2 4-5" />
            </svg>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">How to Earn Points</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700">
                        <th class="text-left py-2 text-gray-500 dark:text-gray-400 font-medium">Activity</th>
                        <th class="text-right py-2 text-gray-500 dark:text-gray-400 font-medium">Points</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Complete a lesson topic</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+{{ $topicPoints }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Complete a lesson (all topics done)</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+{{ $lessonPoints }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Pass a quiz</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+{{ $quizPassPoints }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Perfect score on a quiz</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+{{ $quizPerfectPoints }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Quiz attempt (not passed)</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+{{ $quizFailPoints }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Complete a full module</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+{{ $modulePoints }}</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Download a certificate</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+{{ $certificatePoints }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
