@extends('layouts.instructor-app')

@section('title', 'Dashboard')

@section('content')

{{--
    ╔══════════════════════════════════════════════════╗
    ║          INSTRUCTOR DASHBOARD                    ║
    ╚══════════════════════════════════════════════════╝
--}}

{{-- ── Page heading ── --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Instructor Dashboard</h1>
    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Welcome back, {{ Auth::user()->first_name ?? Auth::user()->name }}. Here's what's happening today.</p>
</div>

{{-- ─────────────────────────────────────────────
     STAT CARDS ROW
───────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">

    @php
    $statCards = [
        ['label' => 'Total Learners',    'value' => $stats['total_learners'],                                               'route' => route('instructor.users.index'),       'icon' => 'users',        'alert' => false],
        ['label' => 'Modules',           'value' => $stats['published_modules'].'/'.$stats['total_modules'],                'route' => route('instructor.modules.index'),     'icon' => 'book',         'alert' => false],
        ['label' => 'Quizzes',           'value' => $stats['total_quizzes'],                                                'route' => route('instructor.quizzes.index'),     'icon' => 'clipboard',    'alert' => false],
        ['label' => 'Pending Requests',  'value' => $stats['pending_enrollments'],                                          'route' => route('instructor.enrollments.index'), 'icon' => 'clock',        'alert' => $stats['pending_enrollments'] > 0],
        ['label' => 'Enrolled Learners', 'value' => $stats['enrolled_learners'],                                            'route' => route('instructor.users.index'),       'icon' => 'check-circle', 'alert' => false],
    ];
    @endphp

    @foreach($statCards as $card)
    <a href="{{ $card['route'] }}"
       class="relative rounded-2xl p-4 text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden {{ $card['alert'] ? 'ring-2 ring-red-400 ring-offset-1' : '' }}"
       style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        <p class="text-2xl font-bold leading-none">{{ $card['value'] }}</p>
        <p class="text-xs text-purple-100 mt-1.5">{{ $card['label'] }}</p>
        {{-- Icon watermark — sized to stay within card bounds --}}
        <div class="absolute -right-2 -bottom-2 opacity-20 pointer-events-none">
            @if($card['icon'] === 'users')
            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            @elseif($card['icon'] === 'book')
            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            @elseif($card['icon'] === 'clipboard')
            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            @elseif($card['icon'] === 'clock')
            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            @elseif($card['icon'] === 'check-circle')
            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            @endif
        </div>
    </a>
    @endforeach

</div>

{{-- ─────────────────────────────────────────────
     TWO-COLUMN CONTENT AREA
───────────────────────────────────────────── --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- ══ LEFT COLUMN ══ --}}
    <div class="xl:col-span-2 space-y-6">

        {{-- A. RECENT ACTIVITIES ─────────────────────────── --}}
        <section class="bg-purple-50/40 rounded-2xl p-5 border border-purple-100/60">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-purple-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900">Recent Activities</h2>
                    <p class="text-xs text-gray-400">Latest enrollment events across your modules</p>
                </div>
                <a href="{{ route('instructor.enrollments.index') }}"
                   class="text-xs font-medium text-purple-600 hover:text-purple-800 bg-purple-100 hover:bg-purple-200 px-3 py-1 rounded-full transition-colors">
                    View All →
                </a>
            </div>

            @if($recentActivities->isEmpty())
            <div class="bg-white rounded-xl border border-dashed border-gray-200 p-8 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-sm text-gray-400">No recent activity yet</p>
            </div>
            @else
            <div class="space-y-2">
                @foreach($recentActivities as $activity)
                <div class="bg-white rounded-xl border border-gray-100 px-4 py-3 flex items-center gap-3 hover:shadow-sm transition-shadow">
                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 text-xs font-bold flex-shrink-0">
                        {{ strtoupper(mb_substr($activity->user->first_name ?? $activity->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ $activity->user->first_name ?? $activity->user->name }} enrolled in
                            <span class="text-purple-700">{{ $activity->module->title ?? 'Unknown module' }}</span>
                        </p>
                        <p class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                    @php
                        $statusClasses = [
                            'pending'  => 'bg-yellow-100 text-yellow-700',
                            'approved' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="flex-shrink-0 text-[11px] font-semibold px-2.5 py-0.5 rounded-full {{ $statusClasses[$activity->status->value] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($activity->status->value) }}
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </section>

        {{-- B. PENDING ENROLLMENT QUICK ACTIONS ─────────── --}}
        @if($pendingEnrollments->isNotEmpty())
        <section class="bg-amber-50/40 rounded-2xl p-5 border border-amber-100/60">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-amber-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900">Pending Requests</h2>
                    <p class="text-xs text-gray-400">Learners waiting for enrollment approval</p>
                </div>
                <a href="{{ route('instructor.enrollments.index') }}"
                   class="text-xs font-medium text-amber-700 hover:text-amber-900 bg-amber-100 hover:bg-amber-200 px-3 py-1 rounded-full transition-colors">
                    View All →
                </a>
            </div>
            <div class="space-y-2">
                @foreach($pendingEnrollments as $enrollment)
                <div class="bg-white rounded-xl border border-amber-100 px-4 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 text-xs font-bold flex-shrink-0">
                        {{ strtoupper(mb_substr($enrollment->user->first_name ?? $enrollment->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ $enrollment->user->first_name ?? $enrollment->user->name }} {{ $enrollment->user->last_name ?? '' }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">{{ $enrollment->module->title ?? 'Unknown module' }}</p>
                    </div>
                    <p class="text-[11px] text-gray-400 flex-shrink-0 hidden sm:block">{{ $enrollment->created_at->diffForHumans() }}</p>
                    <div class="flex gap-1.5 flex-shrink-0">
                        <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition-colors">
                                Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- C. TOP MODULES BY ENROLLMENT ────────────────── --}}
        <section class="bg-indigo-50/30 rounded-2xl p-5 border border-indigo-100/50">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-indigo-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900">Top Modules</h2>
                    <p class="text-xs text-gray-400">Your most popular modules by enrollment</p>
                </div>
                <a href="{{ route('instructor.modules.index') }}"
                   class="text-xs font-medium text-indigo-600 hover:text-indigo-800 bg-indigo-100 hover:bg-indigo-200 px-3 py-1 rounded-full transition-colors">
                    View All →
                </a>
            </div>

            @if($moduleStats->isEmpty())
            <div class="bg-white rounded-xl border border-dashed border-gray-200 p-6 text-center">
                <p class="text-sm text-gray-400">No modules yet — <a href="{{ route('instructor.modules.create') }}" class="text-indigo-600 font-medium">create your first</a></p>
            </div>
            @else
            <div class="space-y-2">
                @foreach($moduleStats as $index => $mod)
                <div class="bg-white rounded-xl border border-indigo-50 px-4 py-3 flex items-center gap-3 hover:shadow-sm transition-shadow">
                    <span class="w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold flex-shrink-0
                        {{ $index === 0 ? 'bg-yellow-400 text-white' : ($index === 1 ? 'bg-gray-300 text-white' : ($index === 2 ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-500')) }}">
                        {{ $index + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $mod->title }}</p>
                    </div>
                    <span class="flex-shrink-0 text-xs font-semibold text-indigo-700 bg-indigo-100 px-2.5 py-0.5 rounded-full">
                        {{ $mod->enrollments_count }} enrolled
                    </span>
                    <a href="{{ route('instructor.modules.edit', $mod) }}" class="flex-shrink-0 text-gray-400 hover:text-indigo-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </a>
                </div>
                @endforeach
            </div>
            @endif
        </section>

        {{-- D. QUIZ PERFORMANCE SUMMARY ──────────────────── --}}
        <section class="bg-green-50/30 rounded-2xl p-5 border border-green-100/50">
            <div class="flex items-center justify-between mb-4">
                <div class="border-l-4 border-green-400 pl-3">
                    <h2 class="text-base font-semibold text-gray-900">Quiz Performance</h2>
                    <p class="text-xs text-gray-400">Attempt statistics across your quizzes</p>
                </div>
                <a href="{{ route('instructor.quizzes.index') }}"
                   class="text-xs font-medium text-green-700 hover:text-green-900 bg-green-100 hover:bg-green-200 px-3 py-1 rounded-full transition-colors">
                    View All →
                </a>
            </div>

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
                            <td class="px-4 py-2.5">
                                <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="font-medium text-gray-900 hover:text-green-700 truncate block max-w-[160px]">
                                    {{ $quiz->title }}
                                </a>
                            </td>
                            <td class="px-4 py-2.5 text-gray-500 truncate max-w-[120px]">{{ $quiz->module->title ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-700 font-medium">{{ $quiz->attempts_count }}</td>
                            <td class="px-4 py-2.5 text-right pr-4">
                                @if($quiz->attempts_avg_score !== null)
                                <span class="font-semibold {{ $quiz->attempts_avg_score >= 75 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ round($quiz->attempts_avg_score, 1) }}%
                                </span>
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
        </section>

    </div>{{-- /left column --}}

    {{-- ══ RIGHT COLUMN ══ --}}
    <div class="space-y-4">

        {{-- E. YOUR MODULES CAROUSEL ─────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"
             x-data="{
                 current: 0,
                 get max() { return Math.max(0, {{ $instructorModules->count() }} - 1); },
                 prev() { if (this.current > 0) this.current--; },
                 next() { if (this.current < this.max) this.current++; }
             }">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Your Modules</h2>
                <div class="flex items-center gap-2">
                    @if($instructorModules->count() > 1)
                    <button @click="prev()" :disabled="current === 0"
                            class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-default transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button @click="next()" :disabled="current >= max"
                            class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-default transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    @endif
                    <a href="{{ route('instructor.modules.index') }}" class="text-xs text-purple-600 hover:text-purple-800 font-medium">View all →</a>
                </div>
            </div>

            @if($instructorModules->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center">
                <p class="text-sm text-gray-400 mb-3">No modules yet</p>
                <a href="{{ route('instructor.modules.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-xl text-white transition-all"
                   style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                    + Create Module
                </a>
            </div>
            @else
            <div class="overflow-hidden">
                <div class="flex transition-transform duration-300 ease-in-out"
                     :style="`transform: translateX(-${current * 100}%)`">
                    @foreach($instructorModules as $mod)
                    <div class="w-full flex-shrink-0 pr-1">
                        <div class="relative rounded-xl overflow-hidden">
                            {{-- Thumbnail --}}
                            <div class="aspect-video relative overflow-hidden"
                                 style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                @if($mod->thumbnail)
                                <img src="{{ Storage::url($mod->thumbnail) }}" alt="{{ $mod->title }}"
                                     class="w-full h-full object-cover opacity-80">
                                @else
                                <div class="w-full h-full flex items-center justify-center opacity-30">
                                    <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                @endif
                                {{-- Overlay info --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex flex-col justify-end p-3">
                                    <p class="text-white text-sm font-semibold leading-tight line-clamp-2">{{ $mod->title }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-white/80">{{ $mod->enrollments_count }} enrolled</span>
                                        @if($mod->is_published)
                                        <span class="text-[10px] bg-green-500/80 text-white px-1.5 py-0.5 rounded-full">Published</span>
                                        @else
                                        <span class="text-[10px] bg-gray-500/80 text-white px-1.5 py-0.5 rounded-full">Draft</span>
                                        @endif
                                    </div>
                                </div>
                                {{-- Edit button --}}
                                <a href="{{ route('instructor.modules.edit', $mod) }}"
                                   class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/40 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                            </div>
                            <div class="pt-2 pb-1 text-xs text-gray-400">Updated {{ $mod->updated_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- F. MINI CALENDAR ─────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4"
             x-data="{
                 today: new Date(),
                 current: new Date(),
                 activityDates: {{ json_encode($calendarDates) }},
                 get monthName() { return this.current.toLocaleString('default', { month: 'long' }); },
                 get year() { return this.current.getFullYear(); },
                 get daysInMonth() {
                     return new Date(this.current.getFullYear(), this.current.getMonth() + 1, 0).getDate();
                 },
                 get firstDayOfWeek() {
                     return new Date(this.current.getFullYear(), this.current.getMonth(), 1).getDay();
                 },
                 hasActivity(day) {
                     const d = this.current.getFullYear() + '-' +
                         String(this.current.getMonth() + 1).padStart(2, '0') + '-' +
                         String(day).padStart(2, '0');
                     return this.activityDates.includes(d);
                 },
                 isToday(day) {
                     return this.today.getFullYear() === this.current.getFullYear() &&
                            this.today.getMonth() === this.current.getMonth() &&
                            this.today.getDate() === day;
                 },
                 prevMonth() {
                     this.current = new Date(this.current.getFullYear(), this.current.getMonth() - 1, 1);
                 },
                 nextMonth() {
                     this.current = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 1);
                 }
             }">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-900" x-text="monthName + ' ' + year"></h2>
                <div class="flex gap-1">
                    <button @click="prevMonth()" class="w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 transition-colors">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button @click="nextMonth()" class="w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 transition-colors">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Day headers --}}
            <div class="grid grid-cols-7 text-center mb-1">
                @foreach(['S','M','T','W','T','F','S'] as $day)
                <div class="text-[10px] font-semibold text-gray-400 py-1">{{ $day }}</div>
                @endforeach
            </div>

            {{-- Calendar grid --}}
            <div class="grid grid-cols-7 gap-y-1">
                {{-- Empty cells for first week --}}
                <template x-for="i in firstDayOfWeek" :key="'empty-' + i">
                    <div></div>
                </template>
                {{-- Day cells --}}
                <template x-for="day in daysInMonth" :key="day">
                    <div class="flex flex-col items-center">
                        <span
                            class="w-6 h-6 flex items-center justify-center rounded-full text-[11px] font-medium transition-colors"
                            :class="{
                                'bg-purple-600 text-white': isToday(day),
                                'text-gray-700': !isToday(day)
                            }"
                            x-text="day">
                        </span>
                        <span
                            x-show="hasActivity(day)"
                            class="w-1 h-1 rounded-full bg-purple-400 mt-0.5">
                        </span>
                    </div>
                </template>
            </div>

            <p class="text-[10px] text-gray-400 mt-3 text-center">Purple dots = enrollment activity</p>
        </div>

        {{-- G. QUICK ACTIONS ──────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('instructor.modules.create') }}"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-purple-50 hover:bg-purple-600 hover:text-white text-purple-700 transition-all duration-200 text-center group">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-xs font-medium">Create Module</span>
                </a>
                <a href="{{ route('instructor.lessons.create') }}"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 transition-all duration-200 text-center group">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-xs font-medium">Add Lesson</span>
                </a>
                <a href="{{ route('instructor.quizzes.create') }}"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-green-50 hover:bg-green-600 hover:text-white text-green-700 transition-all duration-200 text-center group">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="text-xs font-medium">Create Quiz</span>
                </a>
                <a href="{{ route('instructor.enrollments.index') }}"
                   class="flex flex-col items-center gap-1.5 p-3 rounded-xl bg-amber-50 hover:bg-amber-600 hover:text-white text-amber-700 transition-all duration-200 text-center group">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span class="text-xs font-medium">Enrollments</span>
                </a>
            </div>
        </div>

    </div>{{-- /right column --}}

</div>{{-- /grid --}}

@endsection