<div data-testid="admin-table-pagination-footer" class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
    @if(isset($slot) && trim((string) $slot) !== '')
        {{ $slot }}
    @else
        {{ $paginator ?? '' }}
    @endif
</div>
