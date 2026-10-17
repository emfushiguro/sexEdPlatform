<section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-labelledby="module-details-curriculum">
    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
        <h2 id="module-details-curriculum" class="text-base font-semibold text-gray-900 dark:text-white">Module Curriculum</h2>
        <p class="mt-1 text-xs text-gray-500">{{ $lessons->count() }} published {{ Str::plural('lesson', $lessons->count()) }}</p>
        @if(!$canOpenLessons)
            <p class="mt-2 text-xs text-gray-500">Curriculum preview only. Learning content is available to eligible members after seat assignment.</p>
        @endif
    </div>
    <ol class="divide-y divide-gray-100 dark:divide-gray-700">
        @forelse($lessons as $lesson)
            <li class="flex items-center gap-4 px-5 py-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-500">{{ $loop->iteration }}</span>
                <span class="flex-1 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $lesson->title }}</span>
                <span class="shrink-0 text-xs text-gray-500">{{ (int) ($lesson->duration ?? 0) }} min</span>
            </li>
        @empty
            <li class="py-3 text-sm text-gray-500">No published lessons yet.</li>
        @endforelse
    </ol>
</section>
