<div data-testid="admin-row-actions" class="mb-3 flex flex-wrap items-center gap-2 text-xs">
    @foreach(($actions ?? []) as $action)
        <span class="inline-flex rounded-md border border-gray-200 bg-white px-2 py-1 text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $action }}</span>
    @endforeach
</div>
