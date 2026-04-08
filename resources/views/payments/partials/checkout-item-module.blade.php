<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
        <p class="text-xs text-gray-500">Module Name</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $module->title }}</p>
    </div>

    <div class="rounded-xl border border-gray-200 p-3 bg-gray-50/70">
        <p class="text-xs text-gray-500">Instructor</p>
        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $module->creator?->name ?? 'Instructor' }}</p>
    </div>

    @if(!empty($module->description))
        <div class="sm:col-span-2 rounded-xl border border-gray-200 p-3 bg-gray-50/70">
            <p class="text-xs text-gray-500">Description</p>
            <p class="mt-1 text-sm text-gray-700">{{ $module->description }}</p>
        </div>
    @endif
</div>
