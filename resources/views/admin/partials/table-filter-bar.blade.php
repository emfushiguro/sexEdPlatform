<div data-testid="admin-table-filter-bar" class="space-y-1">
	<div class="inline-flex items-center rounded-full border border-brand-100 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
		{{ $label ?? 'Table Filters' }}
	</div>

	@if(!empty($hint ?? null))
		<p class="text-xs text-gray-500">{{ $hint }}</p>
	@endif
</div>
