@extends('layouts.instructor-app')

@section('title', 'Dashboard')

@section('content')
<x-instructor.hero-banner :hero="$dashboardHero" />

<div class="mb-6" x-data="instructorSearch()">
    <div class="relative max-w-2xl">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </span>
        <input
            type="text"
            x-model="query"
            @input.debounce.300ms="search()"
            @focus="open = true"
            @click.away="open = false"
            placeholder="Search modules, lessons, learners..."
            class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-400 transition-all"
            autocomplete="off"
        >

        <div
            x-show="open && (results.modules.length || results.lessons.length || results.learners.length)"
            x-cloak
            class="absolute top-full mt-1 left-0 right-0 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 z-50 overflow-hidden"
        >
            <template x-if="results.modules.length">
                <div class="p-2">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-2 mb-1">Modules</p>
                    <template x-for="item in results.modules" :key="'m-'+item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 transition-colors text-sm text-gray-700 dark:text-gray-200">
                            <span x-text="item.title" class="truncate"></span>
                        </a>
                    </template>
                </div>
            </template>

            <template x-if="results.lessons.length">
                <div class="p-2 border-t border-gray-50 dark:border-gray-800">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-2 mb-1">Lessons</p>
                    <template x-for="item in results.lessons" :key="'l-'+item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 transition-colors text-sm text-gray-700 dark:text-gray-200">
                            <span x-text="item.title" class="truncate"></span>
                        </a>
                    </template>
                </div>
            </template>

            <template x-if="results.learners.length">
                <div class="p-2 border-t border-gray-50 dark:border-gray-800">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 px-2 mb-1">Learners</p>
                    <template x-for="item in results.learners" :key="'u-'+item.id">
                        <a :href="item.url" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-purple-50 transition-colors text-sm text-gray-700 dark:text-gray-200">
                            <span x-text="item.name" class="truncate"></span>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4 mb-6" data-testid="stats-grid">
    @foreach($statCards as $card)
        <x-instructor.stat-card :card="$card" :avg-quiz-score-scopes="$avgQuizScoreScopes" />
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <x-instructor.section-shell
            title="Recent Activities"
            subtitle="Latest enrollment events across your modules"
            tone="purple"
            :action-href="route('instructor.enrollments.index')"
            action-label="View All →">
            @if($recentActivities->isEmpty())
                <div class="bg-white rounded-xl border border-dashed border-gray-200 p-8 text-center">
                    <p class="text-sm text-gray-400">No recent activity yet</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($recentActivities as $activity)
                        <div class="bg-white rounded-xl border border-gray-100 px-4 py-3 flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $activity->user->first_name ?? $activity->user->name }} enrolled in
                                <span class="text-purple-700">{{ $activity->module->title ?? 'Unknown module' }}</span>
                            </p>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-instructor.section-shell>

        @if($pendingEnrollments->isNotEmpty())
            <x-instructor.section-shell
                title="Pending Requests"
                subtitle="Learners waiting for enrollment approval"
                tone="amber"
                :action-href="route('instructor.enrollments.index')"
                action-label="View All →">
                <div class="space-y-2">
                    @foreach($pendingEnrollments as $enrollment)
                        <div class="bg-white rounded-xl border border-amber-100 px-4 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $enrollment->user->first_name ?? $enrollment->user->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $enrollment->module->title ?? 'Unknown module' }}</p>
                            </div>
                            <div class="flex gap-1.5 flex-shrink-0">
                                <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-colors">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">Reject</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-instructor.section-shell>
        @endif

        <x-instructor.section-shell
            title="Top Modules"
            subtitle="Your most popular modules by enrollment"
            tone="indigo"
            :action-href="route('instructor.modules.index')"
            action-label="View All →">
            @if($moduleStats->isEmpty())
                <div class="bg-white rounded-xl border border-dashed border-gray-200 p-6 text-center">
                    <p class="text-sm text-gray-400">No modules yet</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($moduleStats as $index => $mod)
                        <div class="bg-white rounded-xl border border-indigo-50 px-4 py-3 flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $index + 1 }}. {{ $mod->title }}</p>
                            <span class="flex-shrink-0 text-xs font-semibold text-indigo-700 bg-indigo-100 px-2.5 py-0.5 rounded-full">{{ $mod->enrollments_count }} enrolled</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-instructor.section-shell>

        <x-instructor.section-shell
            title="Quiz Performance"
            subtitle="Attempt statistics across your quizzes"
            tone="green"
            :action-href="route('instructor.quizzes.index')"
            action-label="View All →">
            @if($quizStats->isEmpty())
                <div class="bg-white rounded-xl border border-dashed border-gray-200 p-6 text-center">
                    <p class="text-sm text-gray-400">No quiz attempts yet</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl">
                    <table class="w-full text-sm bg-white rounded-xl overflow-hidden">
                        <thead>
                            <tr class="text-xs text-gray-500 bg-gray-50 border-b border-gray-100">
                                <th class="text-left px-4 py-2.5 font-semibold">Quiz</th>
                                <th class="text-left px-4 py-2.5 font-semibold">Module</th>
                                <th class="text-right px-4 py-2.5 font-semibold">Attempts</th>
                                <th class="text-right px-4 py-2.5 font-semibold pr-4">Avg Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($quizStats as $quiz)
                                <tr class="hover:bg-green-50/30 transition-colors">
                                    <td class="px-4 py-2.5"><a href="{{ route('instructor.quizzes.show', $quiz) }}" class="font-medium text-gray-900 hover:text-green-700 truncate block max-w-[160px]">{{ $quiz->title }}</a></td>
                                    <td class="px-4 py-2.5 text-gray-500 truncate max-w-[120px]">{{ $quiz->module->title ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-700 font-medium">{{ $quiz->attempts_count }}</td>
                                    <td class="px-4 py-2.5 text-right pr-4">
                                        @if($quiz->attempts_avg_score !== null)
                                            <span class="font-semibold {{ $quiz->attempts_avg_score >= 75 ? 'text-green-600' : 'text-red-500' }}">{{ round($quiz->attempts_avg_score, 1) }}%</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-instructor.section-shell>
    </div>

    <div class="space-y-4">
        <x-instructor.quick-actions />

        <x-instructor.module-carousel :modules="$instructorModules" :view-all-route="route('instructor.modules.index')" />

        <x-instructor.mini-calendar-shell>
            <div x-data="{
                    today: new Date(),
                    current: new Date(),
                    activityDates: {{ json_encode($calendarDates) }},
                    get monthName() { return this.current.toLocaleString('default', { month: 'long' }); },
                    get year() { return this.current.getFullYear(); },
                    get daysInMonth() { return new Date(this.current.getFullYear(), this.current.getMonth() + 1, 0).getDate(); },
                    get firstDayOfWeek() { return new Date(this.current.getFullYear(), this.current.getMonth(), 1).getDay(); },
                    hasActivity(day) {
                        const d = this.current.getFullYear() + '-' + String(this.current.getMonth() + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                        return this.activityDates.includes(d);
                    },
                    isToday(day) {
                        return this.today.getFullYear() === this.current.getFullYear() && this.today.getMonth() === this.current.getMonth() && this.today.getDate() === day;
                    },
                    prevMonth() { this.current = new Date(this.current.getFullYear(), this.current.getMonth() - 1, 1); },
                    nextMonth() { this.current = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 1); }
                }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-900" x-text="monthName + ' ' + year"></h2>
                    <div class="flex gap-1">
                        <button @click="prevMonth()" aria-label="Previous month" class="w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 transition-colors">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <button @click="nextMonth()" aria-label="Next month" class="w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 transition-colors">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-7 text-center mb-1">
                    @foreach(['S','M','T','W','T','F','S'] as $day)
                        <div class="text-[10px] font-semibold text-gray-400 py-1">{{ $day }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-y-1">
                    <template x-for="i in firstDayOfWeek" :key="'empty-' + i"><div></div></template>
                    <template x-for="day in daysInMonth" :key="day">
                        <div class="flex flex-col items-center">
                            <span class="w-6 h-6 flex items-center justify-center rounded-full text-[11px] font-medium transition-colors" :class="{ 'bg-purple-600 text-white': isToday(day), 'text-gray-700': !isToday(day) }" x-text="day"></span>
                            <span x-show="hasActivity(day)" class="w-1 h-1 rounded-full bg-purple-400 mt-0.5"></span>
                        </div>
                    </template>
                </div>
                <p class="text-[10px] text-gray-400 mt-3 text-center">Purple dots = enrollment activity</p>
            </div>
        </x-instructor.mini-calendar-shell>
    </div>
</div>
@endsection