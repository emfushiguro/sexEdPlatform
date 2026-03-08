{{--
    Active module card.
    Props:
      $moduleData = [
        'enrollment', 'module', 'total_lessons',
        'completed_lessons', 'progress_percent', 'next_lesson'
      ]
--}}
@props(['moduleData'])

@php
    $module          = $moduleData['module'];
    $enrollment      = $moduleData['enrollment'];
    $totalLessons    = $moduleData['total_lessons'];
    $completedLessons = $moduleData['completed_lessons'];
    $pct             = $moduleData['progress_percent'];
    $nextLesson      = $moduleData['next_lesson'];
    $thumbnail       = $module->thumbnail ? asset('storage/' . $module->thumbnail) : null;
    $continueUrl     = $nextLesson
        ? route('learner.lessons.show', $nextLesson)
        : route('learner.modules.index');   
    $isCompleted     = !is_null($enrollment->completed_at);
@endphp

<div class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:ring-2 hover:ring-purple-200 dark:hover:ring-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col">

    {{-- Thumbnail --}}
    <div class="relative aspect-video bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/40 dark:to-purple-800/40 overflow-hidden">
        @if($thumbnail)
            <img src="{{ $thumbnail }}" alt="{{ $module->title }}"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
            <div class="w-full h-full flex items-center justify-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-purple-500 dark:text-purple-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
            </div>
        @endif

        {{-- Premium badge --}}
        @if($module->is_premium)
            <span class="absolute top-2 right-2 text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-400 text-amber-900">
                PREMIUM
            </span>
        @endif

        {{-- Completed overlay --}}
        @if($isCompleted)
            <div class="absolute inset-0 bg-green-500/10 flex items-end p-2">
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-500 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                    </svg>
                    Completed
                </span>
            </div>
        @endif
    </div>

    {{-- Body --}}
    <div class="p-4 flex flex-col flex-1 gap-3">

        {{-- Title + Lesson Completed--}}
        <div>
            <div class="flex items-start justify-between gap-2 mb-1">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white leading-snug line-clamp-2">
                    {{ $module->title }}
                </h3>
            </div>
            <p class="text-sm text-gray-">
                {{ $completedLessons }}/{{ $totalLessons }} lessons completed
            </p>

        </div>

        {{-- Progress bar --}}
        <div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                <span>Progress</span>
                <span class="font-medium text-purple-600 dark:text-purple-400">{{ $pct }}%</span>
            </div>
            <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    style="width: {{ $pct }}%; background: linear-gradient(90deg, #A30EB2, #3B0CB1);"
                ></div>
            </div>
        </div>

        {{-- Last studied --}}
        <p class="text-xs text-gray-400 dark:text-gray-500">
            Last studied {{ $enrollment->updated_at->diffForHumans() }}
        </p>

        {{-- CTA button --}}
        <a
            href="{{ $continueUrl }}"
            class="mt-auto block w-full text-center text-sm font-semibold text-white py-2.5 px-4 rounded-lg transition-all duration-150 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
        >
            {{ $isCompleted ? 'Review Module' : ($pct > 0 ? 'Continue Learning' : 'Start Learning') }}
        </a>
    </div>
</div>
