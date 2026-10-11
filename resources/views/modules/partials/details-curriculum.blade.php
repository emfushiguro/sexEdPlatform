<section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="module-details-curriculum">
    <div class="flex items-center justify-between gap-3">
        <h2 id="module-details-curriculum" class="text-lg font-bold text-gray-900">Curriculum</h2>
        @if(!$canOpenLessons)
            <span class="text-xs font-medium text-gray-500">Lesson content unlocks after enrollment.</span>
        @endif
    </div>
    <ol class="mt-4 divide-y divide-gray-100">
        @forelse($lessons as $lesson)
            <li class="flex items-center justify-between gap-4 py-3">
                <span class="text-sm font-medium text-gray-800">{{ $lesson->title }}</span>
                <span class="text-xs text-gray-500">{{ (int) ($lesson->duration ?? 0) }} min</span>
            </li>
        @empty
            <li class="py-3 text-sm text-gray-500">No published lessons yet.</li>
        @endforelse
    </ol>
</section>
