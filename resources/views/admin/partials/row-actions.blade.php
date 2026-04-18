<div data-testid="admin-row-actions" class="mb-3 flex flex-wrap items-center gap-2 text-xs">
	@foreach(($actions ?? []) as $action)
		<span class="inline-flex items-center rounded-xl border border-brand-100 bg-white px-2.5 py-1 text-gray-600">
			{{ $action }}
		</span>
	@endforeach
</div>
