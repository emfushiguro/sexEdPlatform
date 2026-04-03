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

    $creator = $module->creator;
    $instructorProfile = $creator?->instructorProfile;
    $instructorName = $creator?->full_name ?: $creator?->name ?: 'Instructor';
    $instructorPhoto = $instructorProfile?->profile_photo_path
        ? asset('storage/' . ltrim($instructorProfile->profile_photo_path, '/'))
        : null;

    $approvedCount = (int) ($module->approved_enrollments_count ?? 0);
    $enrollmentLimit = $module->enrollment_limit !== null ? (int) $module->enrollment_limit : null;
    $isFull = $enrollmentLimit !== null && $approvedCount >= $enrollmentLimit;
    $enrollmentLabel = $enrollmentLimit !== null
        ? sprintf('%d / %d Enrolled', $approvedCount, $enrollmentLimit)
        : sprintf('%d Enrolled', $approvedCount);

    $priceLabel = $module->display_price ?? 'Free';
    $enrollUrl = route('learner.modules.show', $module);
@endphp

<div class="group h-full bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:ring-2 hover:ring-purple-200 dark:hover:ring-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col">

    {{-- Thumbnail --}}
    <div class="relative aspect-video bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 overflow-hidden">
        @if($thumbnail)
            <img src="{{ $thumbnail }}" alt="{{ $module->title }}"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
            <div class="w-full h-full flex items-center justify-center">
                <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple-500 dark:text-purple-400" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
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
            @if($isFull)
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300">
                    FULL
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
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                {{ $module->lessons_count }} {{ Str::plural('lesson', $module->lessons_count) }}
            </span>
            @if($durationStr)
                <span class="flex items-center gap-1">
                    <svg width="12" height="12" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6l4 2M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2Z"/>
                    </svg>
                    {{ trim($durationStr) }}
                </span>
            @endif
        </div>

        <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $priceLabel }}</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $enrollmentLabel }}</span>
        </div>

        <div class="flex items-center gap-2 pt-0.5">
            @if($instructorPhoto)
                <img src="{{ $instructorPhoto }}" alt="{{ $instructorName }}" class="w-6 h-6 rounded-full object-cover border border-gray-200 dark:border-gray-600">
            @else
                <div class="w-6 h-6 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 flex items-center justify-center text-[10px] font-bold">
                    {{ strtoupper(substr($instructorName, 0, 1)) }}
                </div>
            @endif
            <p class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ $instructorName }}</p>
        </div>

        {{-- CTA button --}}
        @if($isFull)
            <span class="mt-auto block w-full text-center text-sm font-semibold text-rose-700 bg-rose-50 border border-rose-200 py-2.5 px-4 rounded-lg">
                Enrollment Closed
            </span>
        @else
            <a
                href="{{ $enrollUrl }}"
                class="mt-auto block w-full text-center text-sm font-semibold text-white py-2.5 px-4 rounded-lg transition-all duration-150 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
            >
                Start Learning
            </a>
        @endif
    </div>
</div>
