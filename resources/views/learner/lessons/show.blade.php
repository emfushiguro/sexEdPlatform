@extends('layouts.learner-fullscreen')

@section('title', $lesson->title)
@section('back-url', route('learner.modules.show', $module))
@section('module-title', $module->title)
@section('lesson-title', $lesson->title)

@section('progress-bar')
    @php
        $topicCount  = $lessonTopics->count();
        $doneCount   = count($completedTopicIds);
        $progressPct = $topicCount > 0 ? round(($doneCount / $topicCount) * 100) : 0;
    @endphp
    <div class="flex items-center gap-2 w-full">
        <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                 style="width: {{ $progressPct }}%; background: linear-gradient(to right, #A30EB2, #3B0CB1);"></div>
        </div>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
            {{ $doneCount }}/{{ $topicCount }}
        </span>
    </div>
@endsection

@section('content')
<div class="flex h-full overflow-hidden">

    {{-- ═══════════════════════════════════════════
         LEFT SIDEBAR — Lesson Content List
    ═══════════════════════════════════════════ --}}
    <aside class="w-[300px] flex-shrink-0 h-full flex flex-col border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">

        {{-- Panel Header --}}
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex-shrink-0">
            <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Lesson Content</p>
            <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2">{{ $lesson->title }}</p>
        </div>

        {{-- Scrollable Topic List --}}
        <div class="flex-1 overflow-y-auto py-2 px-3 space-y-1">

            @foreach($lessonTopics as $index => $topic)
                @php
                    $isCompleted = in_array($topic->id, $completedTopicIds);
                    $isLocked    = in_array($topic->id, $lockedTopicIds);
                    $isCurrent   = $currentTopicIndex === $index && !request()->has('quiz');
                @endphp

                @php
                    $typeConfig = match($topic->type) {
                        'video'     => ['bg' => 'bg-blue-50 dark:bg-blue-900/20',    'text' => 'text-blue-500',   'icon' => 'video'],
                        'text'      => ['bg' => 'bg-indigo-50 dark:bg-indigo-900/20','text' => 'text-indigo-500', 'icon' => 'text'],
                        'worksheet' => ['bg' => 'bg-green-50 dark:bg-green-900/20',  'text' => 'text-green-600',  'icon' => 'worksheet'],
                        'quiz'      => ['bg' => 'bg-purple-50 dark:bg-purple-900/20','text' => 'text-purple-500', 'icon' => 'quiz'],
                        default     => ['bg' => 'bg-gray-100 dark:bg-gray-800',      'text' => 'text-gray-500',   'icon' => 'default'],
                    };
                @endphp

                @if($isLocked)
                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl opacity-50 cursor-not-allowed select-none">
                        {{-- Type icon --}}
                        <div class="flex-shrink-0 w-7 h-7 rounded-lg {{ $typeConfig['bg'] }} {{ $typeConfig['text'] }} flex items-center justify-center">
                            @include('learner.lessons.partials.topic-type-icon', ['iconType' => $typeConfig['icon']])
                        </div>
                        {{-- Lock icon --}}
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        {{-- Text --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ $topic->title }}</p>
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ $topic->duration }} min{{ $topic->is_prerequisite ? ' · Required' : '' }}
                            </p>
                        </div>
                    </div>

                @else
                    <a href="{{ route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => $index]) }}"
                       class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-colors group
                              {{ $isCurrent
                                  ? 'bg-violet-50 dark:bg-violet-900/10 border-l-2 border-violet-600'
                                  : 'hover:bg-gray-50 dark:hover:bg-gray-800/60 border-l-2 border-transparent' }}">
                        {{-- Type icon --}}
                        <div class="flex-shrink-0 w-7 h-7 rounded-lg {{ $typeConfig['bg'] }} {{ $typeConfig['text'] }} flex items-center justify-center">
                            @include('learner.lessons.partials.topic-type-icon', ['iconType' => $typeConfig['icon']])
                        </div>
                        {{-- Completion state --}}
                        <div class="flex-shrink-0">
                            @if($isCompleted)
                                <svg class="w-4 h-4 text-violet-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($isCurrent)
                                <div class="w-4 h-4 rounded-full border-2 border-violet-500 flex items-center justify-center">
                                    <div class="w-1.5 h-1.5 rounded-full bg-violet-500"></div>
                                </div>
                            @else
                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600"></div>
                            @endif
                        </div>
                        {{-- Text --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium truncate
                                      {{ $isCurrent ? 'text-violet-700 dark:text-violet-300' : 'text-gray-800 dark:text-gray-200' }}">
                                {{ $topic->title }}
                            </p>
                            <p class="text-[11px] mt-0.5
                                      {{ $isCurrent ? 'text-violet-500' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $topic->duration }} min{{ $topic->is_prerequisite ? ' · Required' : '' }}
                            </p>
                        </div>
                    </a>
                @endif
            @endforeach

            {{-- Lesson Quiz Row (only when all topics are done) --}}
            @if($lessonQuiz && count($completedTopicIds) === $lessonTopics->count())
                @php $isQuizActive = request()->has('quiz'); @endphp
                <a href="{{ route('learner.lessons.show', ['lesson' => $lesson->id, 'quiz' => 1]) }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl transition-colors border-l-2
                          {{ $isQuizActive
                              ? 'bg-violet-50 dark:bg-violet-900/10 border-violet-600'
                              : 'hover:bg-gray-50 dark:hover:bg-gray-800/60 border-transparent' }}">
                    {{-- Quiz icon --}}
                    <div class="flex-shrink-0 w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-500 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                        </svg>
                    </div>
                    {{-- Completion --}}
                    <div class="flex-shrink-0">
                        @if($quizAttempt && $quizAttempt->passed)
                            <svg class="w-4 h-4 text-violet-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <div class="w-4 h-4 rounded-full border-2 {{ $isQuizActive ? 'border-violet-500' : 'border-gray-300 dark:border-gray-600' }}"></div>
                        @endif
                    </div>
                    {{-- Text --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium truncate {{ $isQuizActive ? 'text-violet-700 dark:text-violet-300' : 'text-gray-800 dark:text-gray-200' }}">
                            {{ $lessonQuiz->title }}
                        </p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            {{ $lessonQuiz->questions->count() }} questions
                        </p>
                    </div>
                </a>
            @endif

        </div>

        {{-- Panel Footer: Back to Module --}}
        <div class="flex-shrink-0 px-5 py-3 border-t border-gray-100 dark:border-gray-800">
            <a href="{{ route('learner.modules.show', $module) }}"
               class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Module
            </a>
        </div>
    </aside>

    {{-- ═══════════════════════════════════════════
         RIGHT — Main Content
    ═══════════════════════════════════════════ --}}
    <main class="flex-1 h-full overflow-y-auto bg-gray-50 dark:bg-gray-950">
        <div class="max-w-4xl mx-auto px-4 py-6">

            @if(request()->has('quiz') && $lessonQuiz)
                @include('learner.lessons.partials.quiz-page')
            @elseif($currentTopic)
                @include('learner.lessons.partials.topic-page')
            @else
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-white/[0.03] p-12 text-center">
                    <svg class="mx-auto h-14 w-14 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No content available for this lesson.</p>
                </div>
            @endif

            {{-- NOTE: "About this Lesson" block intentionally removed per design --}}

        </div>
    </main>

</div>
@endsection
