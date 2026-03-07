{{-- resources/views/parent/children/show.blade.php --}}
@extends('layouts.learner-app')

@section('title', $child->name . ' — Monitoring')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Back link + child header --}}
    <div>
        <a href="{{ route('parent.children.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to My Children
        </a>

        <div class="flex items-center gap-4 mt-3">
            <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold flex-shrink-0"
                 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                {{ strtoupper(mb_substr($child->name, 0, 2)) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $child->name }}</h1>
                @if($child->learnerProfile && $child->learnerProfile->birthdate)
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500">{{ $child->learnerProfile->getAge() }} years old</span>
                        @php $bracket = $child->learnerProfile->getAgeBracket(); @endphp
                        @if($bracket)
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                                {{ ucfirst($bracket) }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-4 py-3 text-sm">
            {{ session('info') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div x-data="{ tab: 'progress' }">

        {{-- Tab nav --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700 gap-1 overflow-x-auto">
            <button
                @click="tab = 'progress'"
                :style="tab === 'progress' ? 'border-color: #A30EB2; color: #A30EB2;' : ''"
                class="px-4 py-2.5 text-sm whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors"
                :class="tab === 'progress' ? 'font-semibold' : ''">
                Progress
            </button>
            <button
                @click="tab = 'quiz'"
                :style="tab === 'quiz' ? 'border-color: #A30EB2; color: #A30EB2;' : ''"
                class="px-4 py-2.5 text-sm whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors"
                :class="tab === 'quiz' ? 'font-semibold' : ''">
                Quiz Results
            </button>
            <button
                @click="tab = 'achievements'"
                :style="tab === 'achievements' ? 'border-color: #A30EB2; color: #A30EB2;' : ''"
                class="px-4 py-2.5 text-sm whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors"
                :class="tab === 'achievements' ? 'font-semibold' : ''">
                Achievements
            </button>
            @if($canApproveContent)
                <button
                    @click="tab = 'approval'"
                    :style="tab === 'approval' ? 'border-color: #A30EB2; color: #A30EB2;' : ''"
                    class="px-4 py-2.5 text-sm whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors"
                    :class="tab === 'approval' ? 'font-semibold' : ''">
                    Content Approval
                    @if($pendingEnrollments->isNotEmpty())
                        <span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full">
                            {{ $pendingEnrollments->count() }}
                        </span>
                    @endif
                </button>
            @endif
        </div>

        {{-- ── Progress Tab ── --}}
        <div x-show="tab === 'progress'" x-cloak class="pt-6">
            @if($progress->isEmpty())
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5"/>
                    </svg>
                    <p class="text-sm">No modules enrolled yet.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($progress as $enrollment)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $enrollment->module->title }}
                                    </h3>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Enrolled {{ $enrollment->enrolled_at?->diffForHumans() ?? 'recently' }}
                                    </p>
                                </div>
                                <span class="text-lg font-bold flex-shrink-0" style="color: #A30EB2;">
                                    {{ $enrollment->progress_pct }}%
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>{{ $enrollment->completed_lessons }} of {{ $enrollment->total_lessons }} lessons complete</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all"
                                         style="width: {{ $enrollment->progress_pct }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Quiz Results Tab ── --}}
        <div x-show="tab === 'quiz'" x-cloak class="pt-6">
            @if($quizResults->isEmpty())
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-sm">No quizzes taken yet.</p>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-5 py-3 text-left">Quiz</th>
                                <th class="px-5 py-3 text-left">Module</th>
                                <th class="px-5 py-3 text-center">Score</th>
                                <th class="px-5 py-3 text-center">Result</th>
                                <th class="px-5 py-3 text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($quizResults as $attempt)
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $attempt->quiz?->title ?? 'Quiz' }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-500">
                                        {{ $attempt->quiz?->module?->title ?? '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-center font-semibold">{{ $attempt->score }}%</td>
                                    <td class="px-5 py-3 text-center">
                                        @if($attempt->passed)
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Passed</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Failed</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-400">
                                        {{ $attempt->completed_at?->format('M d, Y') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── Achievements Tab ── --}}
        <div x-show="tab === 'achievements'" x-cloak class="pt-6 space-y-6">
            @php
                $gamification = $achievements['gamification'];
                $rewardLogs   = $achievements['rewardLogs'];
            @endphp

            {{-- Gamification summary --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 text-center">
                    <p class="text-3xl font-bold" style="color: #A30EB2;">{{ $gamification?->level ?? 1 }}</p>
                    <p class="text-xs text-gray-400 mt-1">Level</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 text-center">
                    <p class="text-3xl font-bold" style="color: #730DB1;">{{ $gamification?->score ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">XP</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 text-center">
                    <p class="text-3xl font-bold" style="color: #3B0CB1;">{{ $gamification?->streak_count ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-1">Day Streak</p>
                </div>
            </div>

            {{-- Reward log --}}
            @if($rewardLogs->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    <p class="text-sm">No rewards earned yet.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($rewardLogs as $log)
                        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 px-5 py-4 flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl flex-shrink-0"
                                 style="background: linear-gradient(135deg, #f3e8ff, #ede9fe);">
                                {{ $log->achievement?->icon ?? '🏆' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $log->achievement?->title ?? 'Achievement' }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $log->earned_at?->format('M d, Y') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Content Approval Tab (only if can_approve_content) ── --}}
        @if($canApproveContent)
            <div x-show="tab === 'approval'" x-cloak class="pt-6">
                @if($pendingEnrollments->isEmpty())
                    <div class="text-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm">No pending enrollment requests.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($pendingEnrollments as $enrollment)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">
                                            {{ $enrollment->module->title }}
                                        </h3>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            Ages {{ $enrollment->module->min_age }}–{{ $enrollment->module->max_age }}
                                            · Requested {{ $enrollment->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2 flex-shrink-0">
                                        <form method="POST"
                                              action="{{ route('parent.children.enrollments.approve', [$child, $enrollment]) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 text-sm font-semibold text-white rounded-xl hover:opacity-90 transition"
                                                    style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('parent.children.enrollments.reject', [$child, $enrollment]) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

    </div>{{-- /x-data tabs --}}

</div>
@endsection
