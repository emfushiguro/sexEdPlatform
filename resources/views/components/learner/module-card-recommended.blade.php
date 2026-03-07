{{--
    Recommended module card.
    Props: $module (Module model, with lessons_count loaded)
--}}
@props(['module'])

@php
    $thumbnail   = $module->thumbnail ? asset('storage/' . $module->thumbnail) : null;
    $duration    = $module->duration_minutes;
    $durationStr = $duration
        ? ($duration >= 60
            ? floor($duration / 60) . 'h ' . ($duration % 60 > 0 ? ($duration % 60) . 'm' : '')
            : $duration . 'm')
        : null;
    $enrollUrl = route('learner.modules.show', $module);
@endphp

<div class="group bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:ring-2 hover:ring-purple-200 dark:hover:ring-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col">

    {{-- Thumbnail --}}
    <div class="relative aspect-video bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 overflow-hidden">
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

        {{-- Badges (stacked top-left row) --}}
        <div class="absolute top-2 left-2 flex gap-1 flex-wrap">
            @if($module->is_premium)
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-400 text-amber-900">
                    PREMIUM
                </span>
            @endif
            @if($module->enrollment_mode === 'approval')
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300">
                    Approval
                </span>
            @endif
        </div>
    </div>

    {{-- Body --}}
    <div class="p-4 flex flex-col flex-1 gap-3">

        {{-- Title + difficulty --}}
        <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white leading-snug line-clamp-2">
                {{ $module->title }}
            </h3>
            @if($module->difficulty_level)
                <span class="flex-shrink-0 text-[10px] font-medium px-2 py-0.5 rounded-full
                    {{ $module->difficulty_level === 'beginner'
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                        : ($module->difficulty_level === 'intermediate'
                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                            : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400') }}">
                    {{ ucfirst($module->difficulty_level) }}
                </span>
            @endif
        </div>

        {{-- Meta info --}}
        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                </svg>
                {{ $module->lessons_count }} {{ Str::plural('lesson', $module->lessons_count) }}
            </span>
            @if($durationStr)
                <span class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ trim($durationStr) }}
                </span>
            @endif
        </div>

        {{-- CTA button --}}
        <a
            href="{{ $enrollUrl }}"
            class="mt-auto block w-full text-center text-sm font-semibold text-white py-2 px-4 rounded-xl transition-all duration-150 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
        >
            {{ $module->enrollment_mode === 'approval' ? 'Request to Enroll' : 'Start Learning' }}
        </a>
    </div>
</div>
