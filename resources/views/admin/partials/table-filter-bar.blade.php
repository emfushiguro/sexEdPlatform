<div data-testid="admin-table-filter-bar" class="mb-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500 ">
 <span class="font-semibold text-gray-700 ">{{ $label ?? 'Table Filters' }}</span>
 @if(!empty($hint))
 <span class="ml-2">{{ $hint }}</span>
 @endif
</div>
