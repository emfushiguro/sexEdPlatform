@extends('layouts.learner-app')

@section('title', $module->title)

@section('content')
@php
    $gami    = Auth::user()->gamification;
@endphp

<div class="space-y-5">

    @if(!$module->is_published)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
        This module is currently deactivated. You can review existing content, but lesson and quiz progression is temporarily unavailable.
    </div>
    @endif

    {{-- Back link --}}
    <div>
        <a href="{{ route('learner.modules.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Modules
        </a>
    </div>

    {{-- Gamification strip --}}
    <div class="flex flex-wrap items-center gap-2 sm:gap-3 px-4 py-3 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm">
        {{-- Streak --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800/40">
            <svg class="w-4 h-4 text-orange-500" viewBox="0 0 24 24" fill="none">
                <path d="M12 2C12 2 8.5 6.5 8.5 10C8.5 11.933 9.567 13.6 11 14.5C10.5 13 11 11.5 12 10.5C13 11.5 13.5 13 13 14.5C14.433 13.6 15.5 11.933 15.5 10C15.5 6.5 12 2 12 2Z" fill="currentColor"/>
                <path d="M12 14.5C10.343 14.5 9 15.843 9 17.5C9 19.985 10.791 22 13 22C15.209 22 17 19.985 17 17.5C17 15.843 15.657 14.5 14 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $gami?->current_streak ?? 0 }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Day Streak</span>
        </div>
        {{-- Shields --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800/40">
            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
            </svg>
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $shieldsRemaining }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Shields</span>
        </div>
        {{-- Points --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/40">
            <svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.543 2.746c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($gami?->score ?? 0) }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">Points</span>
        </div>
        <a href="{{ route('learner.gamification') }}"
           class="ml-auto text-xs font-medium text-purple-600 dark:text-purple-400 hover:underline whitespace-nowrap">
            How it works
            <svg class="inline w-3 h-3 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    {{-- 2-column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{ expandedLesson: null }">

        {{--  LEFT: module content (2/3)  --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Module hero --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="relative h-40 sm:h-48 overflow-hidden" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                    @if($module->thumbnail)
                        <img src="{{ asset('storage/' . $module->thumbnail) }}"
                             alt="{{ $module->title }}"
                             class="absolute inset-0 w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                    @else
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                    @endif
                    <div class="absolute bottom-0 left-0 right-0 p-5">
                        <div class="flex flex-wrap gap-2 mb-2">
                            @if($module->difficulty_level)
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full
                                    {{ $module->difficulty_level === 'beginner'
                                        ? 'bg-green-400/90 text-green-900'
                                        : ($module->difficulty_level === 'intermediate'
                                            ? 'bg-amber-400/90 text-amber-900'
                                            : 'bg-red-400/90 text-red-900') }}">
                                    {{ ucfirst($module->difficulty_level) }}
                                </span>
                            @endif
                            @if($module->is_premium)
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-amber-400 text-amber-900">PREMIUM</span>
                            @endif
                        </div>
                        <h1 class="text-2xl font-bold text-white leading-tight">{{ $module->title }}</h1>
                        <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-white/80">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                {{ $lessons->count() }} {{ Str::plural('lesson', $lessons->count()) }}
                            </span>
                            @if($module->duration_minutes)
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                                </svg>
                                {{ $module->duration_minutes }} min
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Module description --}}
                @if($module->description)
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1.5">About this module</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line leading-relaxed">{{ $module->description }}</p>
                </div>
                @endif
            </div>

            {{-- Module Curriculum --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Module Curriculum</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $lessons->count() }} {{ Str::plural('lesson', $lessons->count()) }}</p>
                    </div>
                    @if($isEnrolled && $progress->progress_percentage > 0)
                    <span class="text-xs font-semibold text-purple-600 dark:text-purple-400">
                        {{ round($progress->progress_percentage) }}% done
                    </span>
                    @endif
                </div>

                @if($lessons->isEmpty())
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm text-gray-400 dark:text-gray-500">No lessons available yet.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($lessons as $index => $lesson)
                            @php
                                $isCompleted = in_array($lesson->id, $completedLessonIds);
                                $topics      = $lesson->topics()->ordered()->get();
                                $topicsCount = $topics->count();
                                $completedTopicsCount = 0;
                                if ($topicsCount > 0) {
                                    $completedTopicsCount = \App\Models\LessonTopicProgress::where('user_id', auth()->id())
                                        ->whereIn('lesson_topic_id', $topics->pluck('id'))
                                        ->where('completed', true)
                                        ->count();
                                }
                            @endphp
                            <div>
                                {{-- Lesson row --}}
                                <div
                                    class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors {{ $isEnrolled ? 'cursor-pointer' : '' }}"
                                    @if($topicsCount > 0 && $isEnrolled)
                                        @click="expandedLesson = expandedLesson === {{ $lesson->id }} ? null : {{ $lesson->id }}"
                                    @elseif($isEnrolled)
                                        onclick="window.location='{{ route('learner.lessons.show', $lesson) }}'"
                                    @endif
                                >
                                    {{-- Completion circle --}}
                                    @if($isCompleted)
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                            {{ $isEnrolled ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                            {{ $index + 1 }}
                                        </div>
                                    @endif

                                    {{-- Content type icon + title --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $lesson->title }}</h4>
                                        </div>
                                        <div class="flex items-center gap-3 mt-0.5">
                                            @if($topicsCount > 0)
                                                <span class="text-xs {{ $completedTopicsCount === $topicsCount ? 'text-emerald-600 dark:text-emerald-400' : 'text-purple-600 dark:text-purple-400' }} font-medium">
                                                    {{ $completedTopicsCount }}/{{ $topicsCount }} topics
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $lesson->duration }} min</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Right icon --}}
                                    @if(!$isEnrolled)
                                        <svg class="flex-shrink-0 w-4 h-4 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    @elseif($topicsCount > 0)
                                        <svg class="flex-shrink-0 w-4 h-4 text-gray-400 dark:text-gray-500 transition-transform"
                                             :class="expandedLesson === {{ $lesson->id }} ? 'rotate-180' : ''"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    @else
                                        <svg class="flex-shrink-0 w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    @endif
                                </div>

                                {{-- Expandable topics --}}
                                @if($topicsCount > 0)
                                <div x-show="expandedLesson === {{ $lesson->id }}"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     class="bg-gray-50/70 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700"
                                     style="display: none;">
                                    <div class="py-1">
                                        @foreach($topics as $topic)
                                            @php
                                                $isTopicCompleted = \App\Models\LessonTopicProgress::where('user_id', auth()->id())
                                                    ->where('lesson_topic_id', $topic->id)
                                                    ->where('completed', true)
                                                    ->exists();
                                            @endphp
                                            <div class="flex items-center gap-3 px-6 sm:px-8 py-2.5">
                                                <div class="flex-shrink-0">
                                                    @if($isTopicCompleted)
                                                        <div class="w-4 h-4 rounded-full bg-emerald-500 flex items-center justify-center">
                                                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600"></div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate block">{{ $topic->title }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    @if($topic->duration)
                                                        <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $topic->duration }}m</span>
                                                    @endif
                                                    @if($topic->is_prerequisite)
                                                        <span class="text-[10px] text-gray-400 dark:text-gray-500">· Required</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Module Assessment --}}
            @if($isEnrolled && $moduleQuizzes->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Module Assessment</h3>
                </div>
                <div class="p-5 space-y-3">
                    @foreach($moduleQuizzes as $quiz)
                        @php
                            $attempts     = $quizAttempts->get($quiz->id, collect());
                            $bestAttempt  = $attempts->sortByDesc('score')->first();
                            $allCompleted = $progress->completed_lessons === $progress->total_lessons && $progress->total_lessons > 0;
                        @endphp
                        <div class="rounded-xl border p-4 {{ $allCompleted ? 'bg-purple-50/50 dark:bg-purple-900/10 border-purple-200 dark:border-purple-800/40' : 'bg-gray-50 dark:bg-gray-700/30 border-gray-200 dark:border-gray-700' }}">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $quiz->title }}</h4>
                            @if($quiz->description)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $quiz->description }}</p>
                            @endif
                            <div class="flex flex-wrap gap-3 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $quiz->questions->count() }} questions</span>
                                <span>Pass: {{ $quiz->passing_score }}%</span>
                                @if($quiz->time_limit) <span>{{ $quiz->time_limit }} min limit</span> @endif
                            </div>
                            @if($bestAttempt)
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Best score:</span>
                                    <span class="text-sm font-bold {{ $bestAttempt->passed ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ $bestAttempt->score }}%
                                    </span>
                                    @if($bestAttempt->passed)
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">PASSED</span>
                                    @endif
                                </div>
                            @endif
                            @if($allCompleted)
                                <a href="{{ route('quizzes.start', $quiz) }}"
                                   class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-white px-4 py-2 rounded-lg transition hover:opacity-90"
                                   style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                    {{ $bestAttempt ? 'Retake Quiz' : 'Start Quiz' }}
                                </a>
                            @else
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 italic">Complete all lessons to unlock this assessment.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>{{-- end left col --}}

        {{--  RIGHT: enrollment sidebar (1/3)  --}}
        <div class="space-y-5">

            {{-- Enrollment / progress card --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 sticky top-6 max-h-[calc(100vh-5rem)] overflow-y-auto">

                @if($isEnrolled)
                    {{-- Progress --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300 mb-2 font-medium">
                            <span>Your Progress</span>
                            <span class="text-purple-600 dark:text-purple-400 font-bold">{{ round($progress->progress_percentage) }}%</span>
                        </div>
                        <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700"
                                 style="width: {{ round($progress->progress_percentage) }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);"></div>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">
                            {{ $progress->completed_lessons }}/{{ $progress->total_lessons }} lessons completed
                        </p>
                    </div>

                    @if($lessons->isNotEmpty())
                    <a href="{{ route('learner.lessons.show', $lessons->first()) }}"
                       class="flex items-center justify-center gap-2 w-full text-sm font-semibold text-white py-3 px-4 rounded-xl transition hover:opacity-90"
                       style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>
                        </svg>
                        {{ $progress->progress_percentage > 0 ? 'Continue Learning' : 'Start Learning' }}
                    </a>
                    @endif

                    {{-- Certificate section --}}
                    @if(Auth::user()->isPremium())
                        @php
                            $hasCertificate      = Auth::user()->certificates()->where('module_id', $module->id)->exists();
                            $allLessonsCompleted = $lessons->count() > 0 && count($completedLessonIds) === $lessons->count();
                        @endphp
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            @if($hasCertificate)
                                <a href="{{ route('learner.certificates.index') }}"
                                   class="flex items-center gap-2 w-full text-sm font-semibold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 px-4 py-2.5 rounded-xl hover:bg-amber-100 transition-colors justify-center">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                                    </svg>
                                    View Certificate
                                </a>
                            @elseif($allLessonsCompleted)
                                <form method="POST" action="{{ route('learner.certificates.check', $module) }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex items-center gap-2 w-full text-sm font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 px-4 py-2.5 rounded-xl hover:bg-emerald-100 transition-colors justify-center">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                                        </svg>
                                        Get Certificate
                                    </button>
                                </form>
                            @else
                                <p class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                                    </svg>
                                    Complete all lessons to unlock your certificate
                                </p>
                            @endif
                        </div>
                    @elseif($progress->completed_lessons === $progress->total_lessons && $progress->total_lessons > 0)
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-3">
                            <p class="text-xs text-gray-600 dark:text-gray-400">Upgrade to earn a certificate</p>
                            <a href="{{ route('subscription.upgrade') }}"
                               class="flex-shrink-0 text-xs font-semibold text-white px-3 py-1.5 rounded-lg"
                               style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">Upgrade</a>
                        </div>
                    @endif

                @elseif($enrollmentStatus === 'pending')
                    <div class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 rounded-xl">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Enrollment Pending</p>
                            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">Waiting for instructor approval. You'll be notified once approved.</p>
                        </div>
                    </div>

                @elseif($enrollmentStatus === 'rejected')
                    <div class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 rounded-xl">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-red-800 dark:text-red-300">Enrollment Rejected</p>
                            <p class="text-xs text-red-700 dark:text-red-400 mt-0.5">Your enrollment request was not approved by the instructor.</p>
                        </div>
                    </div>

                @else
                    {{-- Not enrolled yet --}}
                    <div class="text-center mb-5">
                        <p class="text-base font-semibold text-gray-900 dark:text-white mb-1">Enroll in this module</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $module->enrollment_mode === 'manual' ? 'Requires instructor approval' : 'Free access  start immediately' }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('learner.modules.enroll', $module) }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center justify-center gap-2 w-full text-sm font-semibold text-white py-3 px-4 rounded-xl transition hover:opacity-90"
                                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                            @if($module->enrollment_mode === 'manual')
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                </svg>
                                Request Enrollment
                            @else
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                                Enroll Now  Free
                            @endif
                        </button>
                    </form>

                    <ul class="mt-4 space-y-2 text-xs text-gray-500 dark:text-gray-400">
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ $lessons->count() }} {{ Str::plural('lesson', $lessons->count()) }} included
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Track your progress
                        </li>
                        @if($module->is_premium)
                        <li class="flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Premium module  upgrade for certificate
                        </li>
                        @endif
                    </ul>
                @endif
            </div>

            {{-- Module info card --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Module Info</h4>
                <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span>{{ $lessons->count() }} {{ Str::plural('lesson', $lessons->count()) }}</span>
                    </li>
                    @if($module->duration_minutes)
                    <li class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                        </svg>
                        <span>{{ $module->duration_minutes }} minutes</span>
                    </li>
                    @endif
                    @if($module->difficulty_level)
                    <li class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                        </svg>
                        <span>{{ ucfirst($module->difficulty_level) }}</span>
                    </li>
                    @endif
                    <li class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        <span>{{ $module->is_premium ? 'Premium' : 'Free' }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                        <span>{{ $module->enrollment_mode === 'manual' ? 'Approval required' : 'Open enrollment' }}</span>
                    </li>
                </ul>
            </div>

        </div>{{-- end right col --}}

    </div>{{-- end grid --}}

</div>
@endsection