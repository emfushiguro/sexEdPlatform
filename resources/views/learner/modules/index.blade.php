@extends('layouts.learner-app')

@section('title', 'My Modules')

@section('content')
<div x-data="{ query: '' }" class="space-y-8">

    {{-- Page header --}}
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="border-l-4 border-purple-500 pl-4">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">My Modules</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Browse and continue your learning journey</p>
            </div>
        </div>
        {{-- Search bar — left-aligned --}}
        <div class="relative w-full max-w-sm">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z"/>
                </svg>
            </span>
            <input
                type="text"
                x-model="query"
                placeholder="Search modules..."
                autocomplete="off"
                class="w-full pl-9 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-400 dark:focus:ring-purple-600 focus:border-transparent transition-all shadow-sm"
            >
        </div>
    </div>

    @php
        $enrolledModules = $modules->filter(fn($m) => in_array($m->id, $enrolledModuleIds));
        $browseModules   = $modules->filter(fn($m) => !in_array($m->id, $enrolledModuleIds));
    @endphp

    {{-- MY ENROLLED MODULES --}}
    @if($enrolledModules->isNotEmpty())
    <section class="bg-purple-50/40 dark:bg-purple-900/10 rounded-2xl p-5 border border-purple-100/60 dark:border-purple-800/30">
        <div class="flex items-center justify-between mb-5">
            <div class="border-l-4 border-purple-400 pl-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">My Enrolled Modules</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500">Pick up where you left off</p>
            </div>
            <span class="text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/40 px-2.5 py-1 rounded-full">
                {{ $enrolledModules->count() }} {{ Str::plural('module', $enrolledModules->count()) }}
            </span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($enrolledModules as $module)
                @php
                    $prog        = $progress[$module->id];
                    $enrollment  = $enrollments->get($module->id);
                    $thumbnail   = $module->thumbnail ? asset('storage/' . $module->thumbnail) : null;
                    $pct         = $prog->progress_percentage;
                    $isCompleted = $enrollment?->completed_at !== null;
                    $isDeactivated = !$module->is_published;
                    $statusValue = $enrollment?->status?->value;
                    $creator = $module->creator;
                    $instructorName = $creator?->full_name ?: $creator?->name ?: 'Instructor';
                    $priceLabel = $module->display_price ?? 'Free';
                    $approvedCount = (int) ($module->approved_enrollments_count ?? 0);
                    $enrollmentLabel = $module->enrollment_limit !== null
                        ? sprintf('%d / %d Enrolled', $approvedCount, (int) $module->enrollment_limit)
                        : sprintf('%d Enrolled', $approvedCount);
                @endphp
                <div
                    x-show="!query.trim() || '{{ addslashes(strtolower($module->title)) }}'.includes(query.toLowerCase().trim())"
                    class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:ring-2 hover:ring-purple-200 dark:hover:ring-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col"
                >
                    <div class="relative aspect-video bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/40 dark:to-purple-800/40 overflow-hidden">
                        @if($thumbnail)
                            <img src="{{ $thumbnail }}" alt="{{ $module->title }}"
                                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-purple-500 dark:text-purple-400" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </div>
                            </div>
                        @endif
                        @if($isDeactivated)
                            <span class="absolute top-2 left-2 text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-900/80 text-white">DEACTIVATED</span>
                        @endif
                        @if($isCompleted)
                            <div class="absolute inset-0 bg-green-500/10 flex items-end p-2">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-500 text-white">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/></svg>
                                    Completed
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="p-4 flex flex-col flex-1 gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white leading-snug line-clamp-2">{{ $module->title }}</h3>
                            @if($statusValue === 'pending' || $statusValue === 'pending_parent_approval')
                                <p class="mt-1 text-[11px] font-semibold text-amber-700 dark:text-amber-300">
                                    Enrollment request pending approval
                                </p>
                            @endif
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $prog->completed_lessons }}/{{ $prog->total_lessons }} {{ Str::plural('lesson', $prog->total_lessons) }} completed
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $instructorName }} · {{ $priceLabel }} · {{ $enrollmentLabel }}
                            </p>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>Progress</span>
                                <span class="font-medium text-purple-600 dark:text-purple-400">{{ $pct }}%</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                     style="width: {{ $pct }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);"></div>
                            </div>
                        </div>
                        @if($enrollment?->updated_at)
                        <p class="text-xs text-gray-400 dark:text-gray-500">Last studied {{ $enrollment->updated_at->diffForHumans() }}</p>
                        @endif
                                @if($isDeactivated)
                                <p class="text-xs font-medium text-amber-700 dark:text-amber-300">Module is currently deactivated. You can still review content while progression actions are paused.</p>
                                @endif
                        <a href="{{ route('learner.modules.show', $module) }}"
                           class="mt-auto block w-full text-center text-sm font-semibold text-white py-2.5 px-4 rounded-xl transition-all duration-150 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
                           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                            {{ $isCompleted ? 'Review Module' : ($pct > 0 ? 'Continue Learning' : 'Start Learning') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- BROWSE AVAILABLE MODULES --}}
    @if($browseModules->isNotEmpty())
    <section class="bg-indigo-50/30 dark:bg-indigo-900/10 rounded-2xl p-5 border border-indigo-100/50 dark:border-indigo-800/30">
        <div class="flex items-center justify-between mb-5">
            <div class="border-l-4 border-indigo-400 pl-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Browse Modules</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500">Age-appropriate modules available for you</p>
            </div>
            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-100 dark:bg-indigo-900/40 px-2.5 py-1 rounded-full">
                {{ $browseModules->count() }} available
            </span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($browseModules as $module)
                <div x-show="!query.trim() || '{{ addslashes(strtolower($module->title)) }}'.includes(query.toLowerCase().trim())">
                    <x-learner.module-card-recommended :module="$module" />
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Empty state --}}
    @if($modules->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-14 flex flex-col items-center text-center">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background: linear-gradient(135deg,#f3e8ff,#ede9fe);">
            <svg class="w-8 h-8 text-purple-500" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-200">No modules available yet</h3>
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No modules are available for your age group. Check back soon!</p>
    </div>
    @endif

</div>
@endsection