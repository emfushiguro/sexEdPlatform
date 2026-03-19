<div data-testid="admin-table-filter-bar" class="mb-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $label ?? 'Table Filters' }}</span>
    @if(!empty($hint))
        <span class="ml-2">{{ $hint }}</span>
    @endif
</div>
