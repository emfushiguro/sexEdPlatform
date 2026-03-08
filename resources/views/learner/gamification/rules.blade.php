@extends('layouts.learner-app')

@section('title', 'Gamification Rules')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">

    {{-- Hero Banner --}}
    <div class="rounded-2xl p-8 text-center mb-8 text-white" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <div class="flex justify-center mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-12 h-12 text-yellow-300">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-2">How Gamification Works</h1>
        <p class="text-white/80 text-sm max-w-lg mx-auto">
            Earn points, maintain your streak, and level up by staying consistent with your learning!
        </p>
        @if($gamification)
        <div class="mt-4 flex items-center justify-center gap-6 text-sm">
            <div>
                <span class="block text-white/60">Your Points</span>
                <span class="text-xl font-bold">⭐ {{ number_format($gamification->score) }}</span>
            </div>
            <div>
                <span class="block text-white/60">Level</span>
                <span class="text-xl font-bold">{{ $gamification->level }}</span>
            </div>
            <div>
                <span class="block text-white/60">Streak</span>
                <span class="text-xl font-bold">🔥 {{ $gamification->streak_count }}d</span>
            </div>
            <div>
                <span class="block text-white/60">Shields</span>
                <span class="text-xl font-bold">🛡 {{ $shieldsRemaining }}/3</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Shields Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <x-icons.shield state="full" :size="24" />
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Quiz Shields</h2>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Shields protect your streak and let you take quizzes. You start each day with <strong>3 shields</strong>.
        </p>
        <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-green-500">✓</span>
                Each day you get <strong>3 quiz shields</strong> — these reset at midnight.
            </li>
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-red-500">✕</span>
                A shield is <strong>drained</strong> when you fail or don't finish a quiz.
            </li>
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-purple-500">★</span>
                Spend <strong>50 points</strong> to refill 1 shield, or <strong>100 points</strong> for a full refill (3 shields).
            </li>
        </ul>
    </div>

    {{-- Points Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⭐ How to Earn Points</h2>
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
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+10</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Complete a lesson (all topics done)</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+15</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Pass a quiz</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">varies</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Complete a full module</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+100</td>
                    </tr>
                    <tr>
                        <td class="py-3 text-gray-700 dark:text-gray-300">Download a certificate</td>
                        <td class="py-3 text-right font-semibold text-purple-600 dark:text-purple-400">+50</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Streak Rules --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-orange-500">
                <path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z" clip-rule="evenodd" />
            </svg>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Daily Streak</h2>
        </div>
        <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300 mb-4">
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-orange-500">🔥</span>
                Complete at least one lesson topic each day to keep your streak alive.
            </li>
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-yellow-500">★</span>
                <strong>7-day streak:</strong> +50 bonus points
            </li>
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-yellow-500">★★</span>
                <strong>30-day streak (and every 30 days):</strong> +200 bonus points
            </li>
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-red-400">⚠</span>
                Miss a day and your streak resets to 0 — unless you have a streak saver!
            </li>
        </ul>
    </div>

    {{-- Streak Savers --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <x-icons.shield state="full" :size="22" />
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Streak Savers</h2>
        </div>
        <ul class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-purple-500">🛡</span>
                Streak savers automatically protect your streak when you miss a day.
            </li>
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-purple-500">★</span>
                Buy a streak saver for <strong>75 points</strong>. You can hold up to <strong>3 savers</strong>.
            </li>
            <li class="flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5 text-gray-400">ℹ</span>
                A saver is consumed automatically — you don't need to do anything.
            </li>
        </ul>
    </div>

    <div class="text-center">
        <a href="{{ route('learner.dashboard') }}" class="inline-flex items-center gap-2 text-sm text-purple-600 dark:text-purple-400 hover:underline">
            ← Back to Dashboard
        </a>
    </div>
</div>
@endsection
